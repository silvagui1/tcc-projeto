<?php

namespace Tests\Feature;

use App\Models\Aluguel;
use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Regras da tela de Vendas: baixa de estoque, créditos do cliente,
 * cancelamento com estorno e aluguéis de mesas (conflito de horário,
 * repetição semanal, pagamento via venda).
 */
class VendasTest extends TestCase
{
    use RefreshDatabase;

    private function produto(int $quantidade = 10, float $preco = 10): Produto
    {
        return Produto::create([
            'nome' => 'Coca-Cola',
            'preco' => $preco,
            'quantidade' => $quantidade,
            'categoria_id' => Categoria::firstOrCreate(['nome' => 'Bebida'])->id,
        ]);
    }

    private function mesa(float $precoHora = 15): Mesa
    {
        return Mesa::create(['nome' => 'Mesa '.(Mesa::count() + 1), 'capacidade' => 6, 'preco_hora' => $precoHora]);
    }

    private function dadosAluguel(Mesa $mesa, array $extra = []): array
    {
        return array_merge([
            'mesa_id' => $mesa->id,
            'data' => today()->addDay()->format('Y-m-d'),
            'hora_inicio' => '18:00',
            'hora_fim' => '21:00',
            'responsavel' => 'Grupo do João',
            'valor' => 45,
            'tipo_jogo' => 'rpg',
            'jogo' => 'D&D 5e',
            'repetir' => 'nao',
        ], $extra);
    }

    public function test_telas_carregam(): void
    {
        $this->produto();
        $this->mesa();

        $this->get('/vendas')->assertOk()->assertSee('Nova venda');
        $this->get('/vendas?aba=alugueis')->assertOk()->assertSee('Novo aluguel');
        $this->get('/vendas/listar?periodo=hoje')->assertOk();
        $this->get('/inicio')->assertOk()->assertSee('vendido hoje');
        $this->getJson('/vendas/catalogo?tipo=produto&q=coca')->assertOk()->assertJsonPath('0.nome', 'Coca-Cola');
    }

    public function test_venda_baixa_estoque_e_registra_movimentacao(): void
    {
        $produto = $this->produto(10, 8);
        $carta = Carta::create(['nome' => 'Lightning Bolt', 'jogo' => 'magic', 'estado' => 'Novo', 'idioma' => 'Inglês', 'quantidade' => 3, 'preco' => 6.5]);

        $this->postJson('/vendas', [
            'itens' => [
                ['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 2],
                ['tipo' => 'carta', 'id' => $carta->id, 'quantidade' => 1],
                // repetido: deve somar com a primeira linha
                ['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1],
            ],
            'forma_pagamento' => 'pix',
        ])->assertCreated();

        $venda = Venda::with('itens')->sole();
        $this->assertEquals(30.5, (float) $venda->total);
        $this->assertSame('pix', $venda->forma_pagamento);
        $this->assertCount(2, $venda->itens);
        $this->assertSame(7, $produto->fresh()->quantidade);
        $this->assertSame(2, $carta->fresh()->quantidade);
        $this->assertDatabaseHas('produto_movimentacoes', ['produto_id' => $produto->id, 'tipo' => 'saida', 'quantidade' => 3, 'descricao' => "Venda #{$venda->id}"]);
    }

    public function test_venda_acima_do_estoque_e_recusada_sem_gravar_nada(): void
    {
        $produto = $this->produto(2);

        $this->postJson('/vendas', [
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 3]],
            'forma_pagamento' => 'dinheiro',
        ])->assertStatus(422)->assertJsonValidationErrors('itens');

        $this->assertSame(0, Venda::count());
        $this->assertSame(2, $produto->fresh()->quantidade);
    }

    public function test_creditos_pagam_parte_e_o_resto_vai_na_forma_escolhida(): void
    {
        $produto = $this->produto(10, 15);
        $cliente = Cliente::factory()->create(['creditos' => 20]);

        $this->postJson('/vendas', [
            'cliente_id' => $cliente->id,
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 2]],
            'usar_creditos' => true,
            'forma_pagamento' => 'debito',
        ])->assertCreated();

        $venda = Venda::sole();
        $this->assertEquals(20, (float) $venda->valor_creditos);
        $this->assertEquals(10, (float) $venda->valor_restante);
        $this->assertSame('debito', $venda->forma_pagamento);
        $this->assertEquals(0, (float) $cliente->fresh()->creditos);
        $this->assertDatabaseHas('cliente_creditos_historico', ['cliente_id' => $cliente->id, 'tipo' => 'descontar', 'motivo' => "Venda #{$venda->id}"]);
    }

    public function test_creditos_que_cobrem_tudo_dispensam_forma_de_pagamento(): void
    {
        $produto = $this->produto(10, 15);
        $cliente = Cliente::factory()->create(['creditos' => 100]);

        $this->postJson('/vendas', [
            'cliente_id' => $cliente->id,
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]],
            'usar_creditos' => true,
        ])->assertCreated();

        $this->assertNull(Venda::sole()->forma_pagamento);
        $this->assertEquals(85, (float) $cliente->fresh()->creditos);
    }

    public function test_sem_forma_de_pagamento_e_recusada(): void
    {
        $produto = $this->produto();

        $this->postJson('/vendas', [
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('forma_pagamento');

        $this->assertSame(10, $produto->fresh()->quantidade);
    }

    public function test_cancelar_venda_devolve_estoque_e_creditos(): void
    {
        $produto = $this->produto(10, 15);
        $cliente = Cliente::factory()->create(['creditos' => 10]);

        $this->postJson('/vendas', [
            'cliente_id' => $cliente->id,
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 2]],
            'usar_creditos' => true,
            'forma_pagamento' => 'pix',
        ])->assertCreated();
        $venda = Venda::sole();

        $this->postJson("/vendas/{$venda->id}/cancelar")->assertOk();

        $this->assertSame('cancelada', $venda->fresh()->status);
        $this->assertSame(10, $produto->fresh()->quantidade);
        $this->assertEquals(10, (float) $cliente->fresh()->creditos);
        $this->assertDatabaseHas('cliente_creditos_historico', ['cliente_id' => $cliente->id, 'tipo' => 'adicionar', 'motivo' => "Estorno da venda #{$venda->id}"]);

        // cancelada não entra nos totais e não pode ser cancelada de novo
        $this->assertSame(0, Venda::resumoDoDia(today())['quantidade']);
        $this->postJson("/vendas/{$venda->id}/cancelar")->assertStatus(422);
    }

    public function test_aluguel_semanal_cria_uma_reserva_por_semana(): void
    {
        $mesa = $this->mesa();
        $primeira = today()->addDay();

        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, [
            'data' => $primeira->format('Y-m-d'),
            'repetir' => 'semanal',
            'repetir_ate' => $primeira->copy()->addWeeks(3)->format('Y-m-d'),
        ]))->assertCreated();

        $reservas = Aluguel::orderBy('inicio')->get();
        $this->assertCount(4, $reservas);
        $this->assertCount(1, $reservas->pluck('serie')->unique());
        $this->assertNotNull($reservas->first()->serie);
        $this->assertTrue($reservas->last()->inicio->equalTo($primeira->copy()->addWeeks(3)->setTime(18, 0)));
    }

    public function test_aluguel_que_bate_com_outro_na_mesma_mesa_e_recusado(): void
    {
        $mesa = $this->mesa();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa))->assertCreated();

        // 20:00–22:00 sobrepõe 18:00–21:00
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, ['hora_inicio' => '20:00', 'hora_fim' => '22:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mesa_id');

        // encostado (21:00–23:00) pode; outra mesa no mesmo horário também
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, ['hora_inicio' => '21:00', 'hora_fim' => '23:00']))->assertCreated();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($this->mesa()))->assertCreated();

        $this->assertSame(3, Aluguel::count());
    }

    public function test_serie_com_conflito_em_uma_data_nao_cria_nenhuma(): void
    {
        $mesa = $this->mesa();
        $primeira = today()->addDay();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, ['data' => $primeira->copy()->addWeeks(2)->format('Y-m-d')]))->assertCreated();

        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, [
            'data' => $primeira->format('Y-m-d'),
            'repetir' => 'semanal',
            'repetir_ate' => $primeira->copy()->addWeeks(4)->format('Y-m-d'),
        ]))->assertStatus(422);

        $this->assertSame(1, Aluguel::count());
    }

    public function test_aluguel_pago_pela_venda_e_volta_a_agendado_ao_cancelar_a_venda(): void
    {
        $mesa = $this->mesa();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa))->assertCreated();
        $aluguel = Aluguel::sole();

        $this->postJson('/vendas', [
            'itens' => [['tipo' => 'aluguel', 'id' => $aluguel->id]],
            'forma_pagamento' => 'dinheiro',
        ])->assertCreated();

        $this->assertSame('pago', $aluguel->fresh()->status);
        $this->assertEquals(45, (float) Venda::sole()->total);

        // pago não pode ser pago de novo nem cancelado direto
        $this->postJson('/vendas', ['itens' => [['tipo' => 'aluguel', 'id' => $aluguel->id]], 'forma_pagamento' => 'pix'])->assertStatus(422);
        $this->postJson("/vendas/alugueis/{$aluguel->id}/cancelar")->assertStatus(422);

        $this->postJson('/vendas/'.Venda::sole()->id.'/cancelar')->assertOk();
        $this->assertSame('agendado', $aluguel->fresh()->status);
    }

    public function test_cancelar_esta_e_as_proximas_datas_da_serie(): void
    {
        $mesa = $this->mesa();
        $primeira = today()->addDay();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, [
            'data' => $primeira->format('Y-m-d'),
            'repetir' => 'semanal',
            'repetir_ate' => $primeira->copy()->addWeeks(4)->format('Y-m-d'),
        ]))->assertCreated();

        $terceira = Aluguel::orderBy('inicio')->skip(2)->first();
        $this->postJson("/vendas/alugueis/{$terceira->id}/cancelar", ['escopo' => 'proximos'])->assertOk();

        $this->assertSame(['agendado', 'agendado', 'cancelado', 'cancelado', 'cancelado'], Aluguel::orderBy('inicio')->pluck('status')->all());
    }

    public function test_mesa_com_reservas_nao_pode_ser_excluida(): void
    {
        $mesa = $this->mesa();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa))->assertCreated();

        $this->deleteJson("/vendas/mesas/{$mesa->id}")->assertStatus(422);
        $this->putJson("/vendas/mesas/{$mesa->id}", ['nome' => $mesa->nome, 'capacidade' => 6, 'preco_hora' => 15, 'ativa' => false])->assertOk();
        $this->assertFalse($mesa->fresh()->ativa);

        // mesa inativa não aceita novos aluguéis
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, ['data' => today()->addDays(2)->format('Y-m-d')]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mesa_id');
    }

    public function test_aluguel_que_passa_da_meia_noite_termina_no_dia_seguinte(): void
    {
        $mesa = $this->mesa();
        $this->postJson('/vendas/alugueis', $this->dadosAluguel($mesa, ['hora_inicio' => '22:00', 'hora_fim' => '01:00']))->assertCreated();

        $aluguel = Aluguel::sole();
        $this->assertSame(180, $aluguel->duracao_minutos);
        $this->assertTrue($aluguel->fim->isSameDay(Carbon::parse($aluguel->inicio)->addDay()));
    }
}
