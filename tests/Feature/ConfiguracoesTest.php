<?php

namespace Tests\Feature;

use App\Models\Aluguel;
use App\Models\Atividade;
use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\JogoCarta;
use App\Models\Mesa;
use App\Models\Produto;
use App\Models\Venda;
use App\Services\Configuracoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tela de Configurações e o efeito de cada opção no resto do sistema.
 */
class ConfiguracoesTest extends TestCase
{
    use RefreshDatabase;

    private function horario(array $extra = []): array
    {
        return array_replace(array_fill(0, 7, ['aberto' => '1', 'abre' => '10:00', 'fecha' => '22:00']), $extra);
    }

    private function produto(int $quantidade = 10, float $preco = 10): Produto
    {
        return Produto::create([
            'nome' => 'Coca-Cola',
            'preco' => $preco,
            'quantidade' => $quantidade,
            'categoria_id' => Categoria::firstOrCreate(['nome' => 'Bebida'])->id,
        ]);
    }

    /** Salva e descarta o singleton, como acontece entre duas requisições. */
    private function configurar(array $valores): void
    {
        app(Configuracoes::class)->salvar($valores);
    }

    public function test_pagina_carrega_com_padroes(): void
    {
        $this->get('/config')
            ->assertOk()
            ->assertSee('Horário de funcionamento')
            ->assertSee('Jogos de carta')
            ->assertDontSee('ADM ART PLAY');

        $this->assertSame(20, Configuracoes::valor('vendas.por_pagina'));
    }

    public function test_salvar_dados_da_loja_normaliza_e_valida(): void
    {
        $this->putJson('/config/loja', [
            'nome' => '  Art Play  ',
            'cnpj' => '12.345.678/0001-90',
            'telefone' => '(11) 3333-4444',
            'endereco' => 'Rua A, 10',
            'horario' => $this->horario([0 => ['aberto' => '0', 'abre' => '10:00', 'fecha' => '18:00']]),
        ])->assertOk();

        $this->assertSame('Art Play', Configuracoes::valor('loja.nome'));
        $this->assertSame('12345678000190', Configuracoes::valor('loja.cnpj'));
        $this->assertFalse(Configuracoes::valor('loja.horario')[0]['aberto']);
        $this->assertNull(Configuracoes::faixaDoDia(0));
        $this->assertSame([600, 1320], Configuracoes::faixaDoDia(1));

        // nome aparece no título das páginas
        $this->get('/inicio')->assertSee('Início — Art Play', false);

        $this->putJson('/config/loja', ['nome' => '', 'cnpj' => '123', 'horario' => $this->horario([3 => ['aberto' => '1', 'abre' => '10:00', 'fecha' => '10:00']])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'cnpj', 'horario.3.abre']);
    }

    public function test_horario_que_passa_da_meia_noite(): void
    {
        $this->configurar(['loja.horario' => array_fill(0, 7, ['aberto' => true, 'abre' => '18:00', 'fecha' => '02:00'])]);

        $this->assertSame([1080, 1560], Configuracoes::faixaDoDia(5));
    }

    public function test_logo_enviada_e_removida(): void
    {
        Storage::fake('public');

        // PNG 1x1 de verdade (o PHP do XAMPP não tem GD para gerar imagens fake)
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $resposta = $this->postJson('/config/loja/logo', ['logo' => UploadedFile::fake()->createWithContent('logo.png', $png)])->assertOk();
        $caminho = Configuracoes::valor('loja.logo');
        Storage::disk('public')->assertExists($caminho);
        $this->assertStringContainsString($caminho, $resposta->json('logo_url'));

        $this->deleteJson('/config/loja/logo')->assertOk();
        Storage::disk('public')->assertMissing($caminho);
        $this->assertNull(Configuracoes::valor('loja.logo'));
    }

    public function test_forma_de_pagamento_desligada_e_recusada_na_venda(): void
    {
        $this->putJson('/config/vendas', [
            'formas_pagamento' => ['pix', 'dinheiro'],
            'permitir_creditos' => '1',
            'exigir_motivo_cancelamento' => '0',
            'por_pagina' => 10,
        ])->assertOk();

        $this->assertSame(['dinheiro', 'pix'], array_keys(Venda::formasAtivas()));

        $produto = $this->produto();
        $this->postJson('/vendas', ['itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]], 'forma_pagamento' => 'credito'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('forma_pagamento');
        $this->postJson('/vendas', ['itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]], 'forma_pagamento' => 'pix'])
            ->assertCreated();

        $this->putJson('/config/vendas', ['formas_pagamento' => [], 'permitir_creditos' => '1', 'exigir_motivo_cancelamento' => '0', 'por_pagina' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('formas_pagamento');
    }

    public function test_creditos_desligados_nao_sao_usados(): void
    {
        $this->configurar(['vendas.permitir_creditos' => false]);
        $produto = $this->produto(10, 15);
        $cliente = Cliente::factory()->create(['creditos' => 100]);

        $this->postJson('/vendas', [
            'cliente_id' => $cliente->id,
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]],
            'usar_creditos' => true,
            'forma_pagamento' => 'pix',
        ])->assertCreated();

        $this->assertEquals(0, (float) Venda::sole()->valor_creditos);
        $this->assertEquals(100, (float) $cliente->fresh()->creditos);
    }

    public function test_motivo_de_cancelamento_obrigatorio(): void
    {
        $this->configurar(['vendas.exigir_motivo_cancelamento' => true]);
        $produto = $this->produto();
        $this->postJson('/vendas', ['itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]], 'forma_pagamento' => 'pix'])->assertCreated();
        $venda = Venda::sole();

        $this->postJson("/vendas/{$venda->id}/cancelar")->assertStatus(422)->assertJsonValidationErrors('motivo');
        $this->postJson("/vendas/{$venda->id}/cancelar", ['motivo' => 'Cliente desistiu'])->assertOk();

        $this->assertSame('Cliente desistiu', $venda->fresh()->motivo_cancelamento);
        $this->assertSame('Cliente desistiu', $this->getJson("/vendas/{$venda->id}")->json('motivo_cancelamento'));
    }

    public function test_intervalo_entre_reservas_e_limites_de_aluguel(): void
    {
        $this->configurar(['alugueis.intervalo' => 30, 'alugueis.max_semanas' => 2, 'alugueis.duracao_maxima' => 180]);
        $mesa = Mesa::create(['nome' => 'Mesa 1', 'capacidade' => 4, 'preco_hora' => 10]);
        $dia = today()->addDay()->format('Y-m-d');
        $base = ['mesa_id' => $mesa->id, 'data' => $dia, 'responsavel' => 'Ana', 'valor' => 20, 'tipo_jogo' => 'rpg', 'repetir' => 'nao'];

        $this->postJson('/vendas/alugueis', [...$base, 'hora_inicio' => '18:00', 'hora_fim' => '20:00'])->assertCreated();

        // encostado (20:00) agora bate por causa dos 30 min de intervalo
        $this->postJson('/vendas/alugueis', [...$base, 'hora_inicio' => '20:00', 'hora_fim' => '21:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.mesa_id.0', fn ($m) => str_contains($m, '30 min livres'));
        $this->postJson('/vendas/alugueis', [...$base, 'hora_inicio' => '20:30', 'hora_fim' => '21:30'])->assertCreated();

        // duração acima da máxima (3h)
        $this->postJson('/vendas/alugueis', [...$base, 'data' => today()->addDays(3)->format('Y-m-d'), 'hora_inicio' => '10:00', 'hora_fim' => '14:00'])
            ->assertStatus(422)->assertJsonValidationErrors('hora_fim');

        // mais semanas que o limite (2)
        $this->postJson('/vendas/alugueis', [...$base,
            'data' => today()->addDays(4)->format('Y-m-d'), 'hora_inicio' => '10:00', 'hora_fim' => '11:00',
            'repetir' => 'semanal', 'repetir_ate' => today()->addDays(4 + 21)->format('Y-m-d'),
        ])->assertStatus(422)->assertJsonValidationErrors('repetir_ate');
    }

    public function test_tipos_de_jogo_editaveis_e_protegidos_quando_em_uso(): void
    {
        $mesa = Mesa::create(['nome' => 'Mesa 1', 'capacidade' => 4, 'preco_hora' => 10]);
        Aluguel::create(['mesa_id' => $mesa->id, 'responsavel' => 'Ana', 'inicio' => now()->addDay(), 'fim' => now()->addDay()->addHour(), 'valor' => 10, 'tipo_jogo' => 'rpg']);

        $config = ['duracao_padrao' => 120, 'duracao_maxima' => 480, 'max_semanas' => 10, 'intervalo' => 0];

        // remover "RPG" (em uso) é recusado
        $this->putJson('/config/alugueis', $config + ['tipos' => [['chave' => 'cartas', 'nome' => 'Cartas', 'cor' => 'azul', 'icone' => 'bi-suit-spade']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipos');

        // renomear RPG e criar "Wargame"
        $resposta = $this->putJson('/config/alugueis', $config + ['tipos' => [
            ['chave' => 'rpg', 'nome' => 'RPG de mesa', 'cor' => 'roxo', 'icone' => 'bi-dice-6'],
            ['chave' => '', 'nome' => 'Wargame', 'cor' => 'laranja', 'icone' => 'bi-trophy'],
        ]])->assertOk();

        $this->assertSame('wargame', $resposta->json('tipos.1.chave'));
        $this->assertSame('RPG de mesa', Aluguel::sole()->tipo_jogo_rotulo);
        $this->assertSame(120, Configuracoes::valor('alugueis.duracao_padrao'));

        // a reserva aceita o tipo novo
        $this->postJson('/vendas/alugueis', [
            'mesa_id' => $mesa->id, 'data' => today()->addDays(5)->format('Y-m-d'), 'hora_inicio' => '10:00', 'hora_fim' => '12:00',
            'responsavel' => 'Bia', 'valor' => 20, 'tipo_jogo' => 'wargame', 'repetir' => 'nao',
        ])->assertCreated();

        $this->get('/vendas?aba=alugueis&dia='.today()->addDays(5)->format('Y-m-d'))
            ->assertOk()->assertSee('jogo-chip--laranja', false)->assertSee('Wargame');
    }

    public function test_categorias_crud_e_protecao(): void
    {
        $this->postJson('/config/categorias', ['nome' => 'Dados'])->assertOk()->assertJsonFragment(['nome' => 'Dados']);
        $this->postJson('/config/categorias', ['nome' => 'Dados'])->assertStatus(422);

        $produto = $this->produto();
        $categoria = $produto->categoria;
        $this->deleteJson("/config/categorias/{$categoria->id}")->assertStatus(422);

        $vazia = Categoria::where('nome', 'Dados')->first();
        $this->putJson("/config/categorias/{$vazia->id}", ['nome' => 'Dados e acessórios'])->assertOk();
        $this->deleteJson("/config/categorias/{$vazia->id}")->assertOk();
        $this->assertDatabaseMissing('categorias', ['id' => $vazia->id]);
    }

    public function test_jogos_de_carta_viram_opcoes_do_estoque(): void
    {
        $this->postJson('/config/jogos-carta', ['nome' => 'Yu-Gi-Oh!'])->assertOk();
        $jogo = JogoCarta::where('nome', 'Yu-Gi-Oh!')->sole();
        $this->assertSame('yugioh', $jogo->chave);

        // carta do jogo novo pode ser cadastrada e aparece na aba dele
        $this->post('/estoque/cartas', ['nome' => 'Dark Magician', 'jogo' => 'yugioh', 'estado' => 'Novo', 'idioma' => 'Inglês', 'quantidade' => 2, 'preco' => 30])
            ->assertSessionHasNoErrors();
        $this->get('/estoque/cartas?jogo=yugioh')->assertOk()->assertSee('Dark Magician')->assertSee('Yu-Gi-Oh!');

        // com cartas, não pode excluir; renomear mantém a chave
        $this->deleteJson("/config/jogos-carta/{$jogo->id}")->assertStatus(422);
        $this->putJson("/config/jogos-carta/{$jogo->id}", ['nome' => 'Yu-Gi-Oh! TCG'])->assertOk();
        $this->assertSame('yugioh', $jogo->fresh()->chave);
        $this->assertSame('yugioh', Carta::sole()->jogo);
    }

    public function test_estados_e_idiomas_de_carta_configuraveis(): void
    {
        $this->putJson('/config/estoque', [
            'alerta_ativo' => '1', 'alerta_minimo' => 3,
            'estados' => ['Lacrada', ' Novo ', 'novo', ''],
            'idiomas' => ['Português'],
        ])->assertOk();

        // vazios e repetidos são descartados
        $this->assertSame(['Lacrada', 'Novo'], Configuracoes::valor('estoque.estados_carta'));

        $dados = ['nome' => 'Pikachu', 'jogo' => 'pokemon', 'idioma' => 'Português', 'quantidade' => 1, 'preco' => 5];
        $this->post('/estoque/cartas', $dados + ['estado' => 'Lacrada'])->assertSessionHasNoErrors();
        $this->post('/estoque/cartas', $dados + ['estado' => 'Usado'])->assertSessionHasErrors('estado', null, 'carta');

        // carta antiga com um estado que saiu da lista ainda pode ser editada
        $antiga = Carta::create($dados + ['estado' => 'Danificado']);
        $this->put("/estoque/cartas/{$antiga->id}", $dados + ['estado' => 'Danificado', 'quantidade' => 3])->assertSessionHasNoErrors();
    }

    public function test_alerta_de_estoque_baixo(): void
    {
        $this->produto(2);
        $this->configurar(['estoque.alerta_minimo' => 5]);

        $this->get('/estoque')->assertSee('com estoque baixo')->assertSee('Coca-Cola (2)');
        $this->get('/inicio')->assertSee('1 produto está com estoque baixo');

        $this->configurar(['estoque.alerta_ativo' => false]);
        $this->get('/estoque')->assertDontSee('com estoque baixo');
    }

    public function test_clientes_valores_rapidos_motivos_e_ddi(): void
    {
        $this->putJson('/config/clientes', ['valores_rapidos' => ['50', '5', '50'], 'motivos' => ['Prêmio de campeonato'], 'whatsapp_ddi' => '+351'])
            ->assertOk();

        $this->assertSame([5, 50], Configuracoes::valor('clientes.valores_rapidos'));
        $cliente = Cliente::factory()->create(['whatsapp' => '912345678']);
        $this->assertSame('https://wa.me/351912345678', $cliente->whatsapp_url);

        $this->get('/clientes')->assertSee('data-valor-rapido="5"', false)->assertSee('Prêmio de campeonato');

        $this->putJson('/config/clientes', ['valores_rapidos' => ['1,5'], 'whatsapp_ddi' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['valores_rapidos.0', 'whatsapp_ddi']);
    }

    public function test_atividades_registradas_e_filtradas(): void
    {
        $produto = $this->produto();
        $this->postJson('/vendas', ['itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]], 'forma_pagamento' => 'pix'])->assertCreated();
        $this->postJson('/config/categorias', ['nome' => 'Dados'])->assertOk();

        $this->assertTrue(Atividade::where('area', 'vendas')->where('descricao', 'like', 'Venda #%registrada%')->exists());

        $this->getJson('/config/atividades?area=estoque')
            ->assertOk()
            ->assertJsonCount(1, 'itens')
            ->assertJsonPath('itens.0.descricao', 'Categoria criada: Dados');
    }

    public function test_exportacoes_csv(): void
    {
        $produto = $this->produto(4, 12.5);
        $this->postJson('/vendas', ['itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 2]], 'forma_pagamento' => 'pix'])->assertCreated();

        $vendas = $this->get('/config/exportar/vendas?periodo=mes')->assertOk();
        $this->assertStringContainsString('text/csv', $vendas->headers->get('Content-Type'));
        $this->assertStringContainsString('2x Coca-Cola', $vendas->getContent());
        $this->assertStringContainsString('25,00', $vendas->getContent());

        $this->get('/config/exportar/vendas?periodo=intervalo&de=2026-01-10&ate=2026-01-01')->assertSessionHasErrors('ate');

        $estoque = $this->get('/config/exportar/estoque')->assertOk();
        $this->assertStringContainsString('Produto;Coca-Cola;Bebida', $estoque->getContent());
    }
}
