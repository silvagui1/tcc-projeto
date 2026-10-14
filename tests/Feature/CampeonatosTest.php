<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Campeonato;
use App\Models\Cliente;
use App\Models\JogoCarta;
use Database\Seeders\CampeonatoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Campeonatos: telas, cadastro, participantes (clientes) e premiação, que
 * vira crédito no saldo do cliente.
 */
class CampeonatosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CampeonatoSeeder::class);
    }

    public function test_telas_abrem_e_filtros_funcionam(): void
    {
        $ativo = Campeonato::where('nome', 'Torneio Pokemon')->firstOrFail();
        $finalizado = Campeonato::where('nome', 'Magic')->firstOrFail();

        $this->get('/campeonatos')->assertOk()->assertSee('Torneio Pokemon')->assertSee('Magic Commander');
        $this->get('/campeonatos?jogo=magic&status=finalizado')->assertOk()->assertSee('Magic Commander')->assertDontSee('Torneio Pokemon');
        $this->get('/campeonatos?busca=liga')->assertOk()->assertSee('Liga One Piece')->assertDontSee('Magic Commander');
        $this->get('/campeonatos/criar')->assertOk()->assertSee('Rogério Cartinhas')->assertSee('One Piece');
        $this->get("/campeonatos/{$ativo->id}")->assertOk()->assertSee('Guilherme Soares');
        $this->get("/campeonatos/{$ativo->id}/editar")->assertOk()->assertSee('Rogério Cartinhas');
        $this->get("/campeonatos/{$ativo->id}/premiacoes")->assertOk()->assertSee('Finalizar campeonato');
        $this->get("/campeonatos/{$finalizado->id}")->assertOk()->assertSee('+R$ 10,00');
        $this->get("/campeonatos/{$finalizado->id}/editar")->assertOk()->assertSee('não pode mais ser alterado');
    }

    public function test_criar_editar_participantes_e_apagar(): void
    {
        $clientes = Cliente::orderBy('id')->get();

        $this->from('/campeonatos/criar')->post('/campeonatos', ['nome' => ''])
            ->assertRedirect('/campeonatos/criar')
            ->assertSessionHasErrors(['nome', 'jogo', 'data', 'deck', 'inscricao']);

        $this->post('/campeonatos', [
            'nome' => 'Copa Teste', 'jogo' => 'magic', 'data' => '2026-11-01', 'horario' => '09:00',
            'deck' => 'livre', 'inscricao' => 'R$ 1.234,50', 'status' => 'ativo',
            'participantes' => [$clientes[0]->id, $clientes[1]->id],
        ])->assertRedirect('/campeonatos')->assertSessionHas('success');

        $campeonato = Campeonato::where('nome', 'Copa Teste')->firstOrFail();
        $this->assertSame('1234.50', $campeonato->valor_inscricao);
        $this->assertCount(2, $campeonato->participantes);
        $this->assertTrue(Atividade::where('area', 'campeonatos')->where('descricao', 'Campeonato criado: Copa Teste')->exists());

        $this->put("/campeonatos/{$campeonato->id}/editar", [
            'nome' => 'Copa Teste 2', 'jogo' => 'magic', 'data' => '2026-11-01', 'deck' => 'livre',
            'inscricao' => '10', 'status' => 'ativo', 'participantes' => [$clientes[2]->id],
        ])->assertRedirect('/campeonatos');
        $this->assertCount(3, $campeonato->fresh()->participantes);

        $this->delete("/campeonatos/{$campeonato->id}/participantes/{$clientes[2]->id}")->assertRedirect();
        $this->assertCount(2, $campeonato->fresh()->participantes);

        $this->delete("/campeonatos/{$campeonato->id}")->assertRedirect('/campeonatos');
        $this->assertNull(Campeonato::find($campeonato->id));
    }

    public function test_finalizar_credita_e_salvar_de_novo_estorna(): void
    {
        $campeonato = Campeonato::where('nome', 'Torneio Pokemon')->firstOrFail();
        $guilherme = Cliente::where('nome', 'Guilherme Soares')->firstOrFail();
        $maria = Cliente::where('nome', 'Maria Garcez')->firstOrFail();
        $saldoGuilherme = (float) $guilherme->creditos;
        $saldoMaria = (float) $maria->creditos;
        $url = "/campeonatos/{$campeonato->id}/premiacoes";

        // quem não está inscrito não pode ser premiado
        $this->from($url)->post($url, ['premiacoes' => [['participante' => 'Rogério Cartinhas', 'valor' => '5']]])
            ->assertSessionHasErrors('premiacoes.0.participante');

        $this->post($url, ['premiacoes' => [
            ['participante' => 'guilherme soares', 'valor' => '200,00', 'descricao' => 'carta'],
            ['participante' => 'Maria Garcez', 'valor' => '100'],
            ['participante' => '', 'valor' => ''],
        ]])->assertRedirect("/campeonatos/{$campeonato->id}");

        $this->assertSame('finalizado', $campeonato->fresh()->status);
        $this->assertEquals($saldoGuilherme + 200, (float) $guilherme->fresh()->creditos);
        $this->assertEquals($saldoMaria + 100, (float) $maria->fresh()->creditos);
        $this->assertSame('1° lugar - Torneio Pokemon', $guilherme->historicoCreditos()->first()->motivo);

        // salvar de novo estorna a premiação anterior e lança a nova
        $this->post($url, ['premiacoes' => [['participante' => 'Maria Garcez', 'valor' => '50']]])->assertRedirect();
        $this->assertEquals($saldoGuilherme, (float) $guilherme->fresh()->creditos);
        $this->assertEquals($saldoMaria + 50, (float) $maria->fresh()->creditos);
        $this->assertCount(1, $campeonato->fresh()->premios);

        // finalizado fica só para leitura
        $this->put("/campeonatos/{$campeonato->id}/editar", [
            'nome' => 'X', 'jogo' => 'magic', 'data' => '2026-11-01', 'deck' => 'd', 'inscricao' => '1', 'status' => 'ativo',
        ])->assertRedirect("/campeonatos/{$campeonato->id}/editar")->assertSessionHas('error');
        $this->assertSame('Torneio Pokemon', $campeonato->fresh()->nome);

        // o estorno não deixa o saldo negativo (e nada é alterado)
        $maria->update(['creditos' => 0]);
        $this->from($url)->post($url, ['premiacoes' => [['participante' => 'Guilherme Soares', 'valor' => '5']]])
            ->assertSessionHasErrors('creditos');
        $this->assertCount(1, $campeonato->fresh()->premios);
    }

    public function test_jogo_com_campeonato_nao_pode_ser_excluido(): void
    {
        $onepiece = JogoCarta::where('chave', 'onepiece')->firstOrFail();

        $this->deleteJson("/config/jogos-carta/{$onepiece->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '“One Piece” tem 1 campeonato cadastrado e não pode ser excluído.']);
        $this->assertNotNull($onepiece->fresh());
    }
}
