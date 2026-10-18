<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Campeonato;
use App\Models\Cliente;
use App\Models\JogoCarta;
use App\Models\Venda;
use Database\Seeders\CampeonatoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Campeonatos: lista com pop-ups (criar, ver, editar, premiar), tipos e
 * checklist dos oficiais, campeonato semanal, participantes provisórios,
 * premiação (crédito no saldo, 1° lugar obrigatório) e pagamento da
 * inscrição (vira venda em Vendas, nunca com créditos).
 */
class CampeonatosTest extends TestCase
{
    use RefreshDatabase;

    /** Pedido como o do pop-up (fetch): devolve só o fragmento. */
    private const FRAGMENTO = ['X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CampeonatoSeeder::class);
    }

    private function challenge(): Campeonato
    {
        return Campeonato::where('nome', 'Challenge One Piece')->firstOrFail(); // 4 inscritos, R$ 20,00
    }

    private function liga(): Campeonato
    {
        return Campeonato::where('nome', 'Liguinha Pokémon de quinta')->firstOrFail();
    }

    private function dadosForm(array $extra = []): array
    {
        return $extra + [
            'nome' => 'Copa Teste', 'tipo' => 'cup', 'jogo' => 'magic', 'data' => '2026-11-05',
            'horario' => '18:00', 'deck' => 'livre', 'inscricao' => '10', 'premio_1' => '50',
        ];
    }

    public function test_lista_e_pop_ups_abrem(): void
    {
        $ativo = $this->challenge();
        $finalizado = Campeonato::where('nome', 'Magic')->firstOrFail();

        $this->get('/campeonatos')->assertOk()
            ->assertSee('Challenge One Piece')->assertSee('Magic Commander')
            ->assertSee('data-champ-modal', false)
            ->assertSee('banner.svg', false)
            ->assertDontSee('images/campeonatos/banner', false);

        // sem fetch, as rotas dos pop-ups levam para a lista com o pop-up aberto
        $this->get('/campeonatos/criar')->assertRedirect('/campeonatos?criar=1');
        $this->get("/campeonatos/{$ativo->id}")->assertRedirect("/campeonatos?abrir={$ativo->id}");
        $this->get("/campeonatos/{$ativo->id}/editar")->assertRedirect("/campeonatos?abrir={$ativo->id}");

        $this->get('/campeonatos/criar', self::FRAGMENTO)->assertOk()
            ->assertSee('Tipo de campeonato')->assertSee('Repete toda semana')
            ->assertSee('Rogério Cartinhas')->assertSee('Adicionar provisório')
            ->assertDontSee('<html', false);
        $this->get("/campeonatos/{$ativo->id}", self::FRAGMENTO)->assertOk()
            ->assertSee('Guilherme Soares')->assertSee('Checklist do evento oficial')
            ->assertSee('Premiar e finalizar')->assertSee('Inscrever como provisório');
        $this->get("/campeonatos/{$ativo->id}/editar", self::FRAGMENTO)->assertOk()->assertSee('Salvar alterações');
        $this->get("/campeonatos/{$ativo->id}/premiacoes", self::FRAGMENTO)->assertOk()
            ->assertSee('Finalizar campeonato')->assertSee('100,00', false);

        $this->get("/campeonatos/{$finalizado->id}", self::FRAGMENTO)->assertOk()
            ->assertSee('+R$ 10,00')->assertDontSee('Premiar e finalizar');
        // finalizado não abre edição nem premiação
        $this->get("/campeonatos/{$finalizado->id}/editar", self::FRAGMENTO)->assertRedirect("/campeonatos?abrir={$finalizado->id}");
    }

    public function test_filtros_e_banner_do_sistema(): void
    {
        $this->get('/campeonatos?jogo=magic&status=finalizado')->assertOk()->assertSee('Magic Commander')->assertDontSee('Challenge One Piece');
        $this->get('/campeonatos?tipo=liga')->assertOk()->assertSee('Liguinha Pokémon de quinta')->assertDontSee('Challenge One Piece');
        $this->get('/campeonatos?busca=nao-existe')->assertOk()->assertSee('Nenhum campeonato encontrado com esses filtros.');

        $this->get('/campeonatos/banner.svg?jogo=pokemon&tipo=cup')->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('POKÉMON', false)->assertSee('Cup', false);
    }

    public function test_lista_vazia_sem_filtros_tem_mensagem_propria(): void
    {
        Campeonato::query()->delete();

        $this->get('/campeonatos')->assertOk()->assertSee('Nenhum campeonato cadastrado ainda.')->assertDontSee('com esses filtros');
    }

    public function test_finalizados_sao_paginados(): void
    {
        foreach (range(1, 9) as $i) {
            Campeonato::create(['nome' => "Antigo {$i}", 'jogo' => 'magic', 'deck' => 'd', 'data' => '2025-01-0'.min($i, 9), 'valor_inscricao' => 0, 'status' => 'finalizado']);
        }

        // 3 do seeder + 9 = 12 finalizados, 9 por página
        $this->get('/campeonatos?status=finalizado')->assertOk()->assertSee('page=2', false)->assertSee('12</span>', false);
        $this->get('/campeonatos?status=finalizado&page=2')->assertOk()->assertSee('paginacao__numero--atual', false);
    }

    public function test_criar_valida_premio_em_creditos_e_cria_com_provisorios(): void
    {
        $clientes = Cliente::cadastrados()->orderBy('id')->get();

        // pelo pop-up os erros voltam em JSON; o 1° lugar é obrigatório e em créditos
        $this->postJson('/campeonatos', ['nome' => '', 'premio_1' => '0'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'tipo', 'jogo', 'data', 'deck', 'inscricao', 'premio_1']);

        // semanal precisa de horário
        $this->postJson('/campeonatos', $this->dadosForm(['semanal' => '1', 'horario' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('horario');

        $this->postJson('/campeonatos', $this->dadosForm([
            'inscricao' => 'R$ 1.234,50', 'premio_1' => '100,00', 'premio_2' => '50', 'status' => 'finalizado',
            'codigo_sancao' => 'ABC-1', 'participantes' => [$clientes[0]->id, $clientes[1]->id],
            'provisorios' => [['nome' => 'Fulano Visitante', 'whatsapp' => '(11) 98765-4321'], ['nome' => '']],
        ]))->assertOk()->assertJsonStructure(['mensagem', 'redirecionar']);

        $campeonato = Campeonato::where('nome', 'Copa Teste')->firstOrFail();
        $this->assertSame('ativo', $campeonato->status);
        $this->assertSame('cup', $campeonato->tipo);
        $this->assertSame('ABC-1', $campeonato->codigo_sancao);
        $this->assertSame('1234.50', $campeonato->valor_inscricao);
        $this->assertSame([1 => 100.0, 2 => 50.0], $campeonato->premiacaoPlanejada());
        $this->assertCount(3, $campeonato->participantes);
        $this->assertTrue(Atividade::where('area', 'campeonatos')->where('descricao', 'Campeonato criado: Copa Teste')->exists());

        // o provisório não aparece em Clientes
        $fantasma = Cliente::where('nome', 'Fulano Visitante')->firstOrFail();
        $this->assertSame(Cliente::CADASTRO_PROVISORIO, $fantasma->cadastro);
        $this->assertNull($fantasma->data_nascimento);
        $this->assertSame('11987654321', $fantasma->whatsapp);
        $this->get('/clientes')->assertOk()->assertDontSee('Fulano Visitante');

        // liga não leva código de sanção
        $this->postJson('/campeonatos', $this->dadosForm(['nome' => 'Liga X', 'tipo' => 'liga', 'codigo_sancao' => 'IGNORA']))->assertOk();
        $this->assertNull(Campeonato::where('nome', 'Liga X')->value('codigo_sancao'));
    }

    public function test_editar_banner_por_arquivo_e_apagar(): void
    {
        Storage::fake('public');
        $campeonato = $this->challenge();

        $this->post("/campeonatos/{$campeonato->id}/editar", $this->dadosForm([
            '_method' => 'PUT', 'nome' => 'Challenge OP 2', 'tipo' => 'challenge', 'status' => 'finalizado',
            // PNG 1x1 de verdade (UploadedFile::fake()->image() precisa da extensão GD)
            'imagem_arquivo' => UploadedFile::fake()->createWithContent('banner.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            )),
        ]), ['Accept' => 'application/json'])->assertOk();

        $campeonato->refresh();
        $this->assertSame('Challenge OP 2', $campeonato->nome);
        $this->assertSame('ativo', $campeonato->status);
        // mesmo tipo: o checklist continua
        $this->assertSame(['sancao'], $campeonato->requisitos);
        Storage::disk('public')->assertExists($campeonato->imagem);
        $this->assertStringContainsString('storage/campeonatos/', $campeonato->imagem_url);

        // trocou o tipo: o checklist do tipo antigo zera; remover a imagem apaga o arquivo
        $arquivo = $campeonato->imagem;
        $this->putJson("/campeonatos/{$campeonato->id}/editar", $this->dadosForm(['tipo' => 'cup', 'remover_imagem' => '1']))->assertOk();
        $campeonato->refresh();
        $this->assertSame([], $campeonato->requisitos);
        $this->assertNull($campeonato->imagem);
        Storage::disk('public')->assertMissing($arquivo);
        $this->assertStringContainsString('banner.svg', $campeonato->imagem_url);

        $this->delete("/campeonatos/{$campeonato->id}")->assertRedirect('/campeonatos');
        $this->assertNull(Campeonato::find($campeonato->id));
    }

    public function test_checklist_dos_oficiais(): void
    {
        $campeonato = $this->challenge();

        $this->putJson("/campeonatos/{$campeonato->id}/requisitos", ['requisitos' => ['sancao', 'player_id']])
            ->assertOk()->assertJson(['redirecionar' => null]);
        $this->assertSame(['sancao', 'player_id'], $campeonato->fresh()->requisitos);
        // "resultados" fica de fora das pendências (é feito depois do evento)
        $this->assertSame([
            Campeonato::REQUISITOS['listas'], Campeonato::REQUISITOS['software'],
        ], $campeonato->fresh()->pendenciasAntesDeFinalizar());

        // item de outro tipo não vale
        $this->putJson("/campeonatos/{$campeonato->id}/requisitos", ['requisitos' => ['kits']])->assertStatus(422);
    }

    public function test_inscrever_cliente_e_provisorio_e_tirar(): void
    {
        $campeonato = $this->liga(); // 3 clientes + 1 provisório
        $rogerio = Cliente::where('nome', 'Rogério Cartinhas')->firstOrFail();
        $base = "/campeonatos/{$campeonato->id}/participantes";

        $this->postJson($base, ['cliente_id' => $rogerio->id])->assertOk()->assertJson(['redirecionar' => null]);
        $this->assertTrue($campeonato->participantes()->whereKey($rogerio->id)->exists());

        // provisório não entra pela busca de clientes
        $visitante = Cliente::where('nome', 'Visitante da Liga')->firstOrFail();
        $this->postJson($base, ['cliente_id' => $visitante->id])->assertStatus(422);

        $this->postJson("{$base}/provisorio", ['nome' => ''])->assertStatus(422)->assertJsonValidationErrors('nome');
        $this->postJson("{$base}/provisorio", ['nome' => 'Novo Fantasma'])->assertOk();
        $novo = Cliente::where('nome', 'Novo Fantasma')->firstOrFail();
        $this->assertTrue($novo->provisorio());
        $this->assertCount(6, $campeonato->participantes()->get());

        // tirar um provisório sem histórico apaga o registro dele
        $this->deleteJson("{$base}/{$novo->id}")->assertOk();
        $this->assertNull(Cliente::find($novo->id));

        // tirar um cliente só tira a inscrição
        $this->deleteJson("{$base}/{$rogerio->id}")->assertOk();
        $this->assertNotNull($rogerio->fresh());
        $this->assertFalse($campeonato->participantes()->whereKey($rogerio->id)->exists());
    }

    public function test_finalizar_exige_primeiro_lugar_credita_e_cria_a_proxima_semana(): void
    {
        $campeonato = $this->liga();
        $guilherme = Cliente::where('nome', 'Guilherme Soares')->firstOrFail();
        $maria = Cliente::where('nome', 'Maria Garcez')->firstOrFail();
        $rogerio = Cliente::where('nome', 'Rogério Cartinhas')->firstOrFail(); // não inscrito
        $visitante = Cliente::where('nome', 'Visitante da Liga')->firstOrFail();
        $saldoGuilherme = (float) $guilherme->creditos;
        $url = "/campeonatos/{$campeonato->id}/premiacoes";
        $vazio = ['participante' => '', 'valor' => ''];

        // 1° lugar é obrigatório
        $this->postJson($url, ['premiacoes' => [$vazio, $vazio, $vazio]])
            ->assertStatus(422)->assertJsonValidationErrors('premiacoes.0.participante');
        // e sempre com créditos
        $this->postJson($url, ['premiacoes' => [['participante' => $guilherme->id, 'valor' => '0'], $vazio, $vazio]])
            ->assertStatus(422)->assertJsonValidationErrors('premiacoes.0.valor');
        // quem não está inscrito não pode ser premiado
        $this->postJson($url, ['premiacoes' => [['participante' => $rogerio->id, 'valor' => '5'], $vazio, $vazio]])
            ->assertStatus(422)->assertJsonValidationErrors('premiacoes.0.participante');
        // a mesma pessoa não pode ficar em duas colocações
        $this->postJson($url, ['premiacoes' => [
            ['participante' => $guilherme->id, 'valor' => '5'], ['participante' => $guilherme->id, 'valor' => '5'], $vazio,
        ]])->assertStatus(422)->assertJsonValidationErrors('premiacoes.1.participante');
        $this->assertSame('ativo', $campeonato->fresh()->status);

        // o provisório pode ganhar créditos
        $this->postJson($url, ['premiacoes' => [
            ['participante' => $guilherme->id, 'valor' => '30,00', 'descricao' => 'carta'],
            ['participante' => $visitante->id, 'valor' => '15'],
            $vazio,
        ]])->assertOk()->assertJsonPath('redirecionar', route('campeonatos.index', ['abrir' => $campeonato->id]));

        $this->assertSame('finalizado', $campeonato->fresh()->status);
        $this->assertEquals($saldoGuilherme + 30, (float) $guilherme->fresh()->creditos);
        $this->assertEquals(15, (float) $visitante->fresh()->creditos);
        $this->assertSame('1° lugar - Liguinha Pokémon de quinta', $guilherme->historicoCreditos()->first()->motivo);

        // semanal: a da semana seguinte já nasce pré-cadastrada, sem participantes
        $proxima = Campeonato::ativos()->where('serie', $campeonato->serie)->firstOrFail();
        $this->assertTrue($proxima->data->equalTo($campeonato->data->copy()->addWeek()));
        $this->assertSame('18:00', $proxima->horario_curto);
        $this->assertEquals(30, (float) $proxima->premio_1);
        $this->assertCount(0, $proxima->participantes);
        $this->assertStringStartsWith('Toda quinta-feira', $proxima->recorrencia);

        // finalizado: nova premiação e edição ficam bloqueadas
        $this->postJson($url, ['premiacoes' => [['participante' => $maria->id, 'valor' => '50'], $vazio, $vazio]])->assertStatus(422);
        $this->putJson("/campeonatos/{$campeonato->id}/editar", $this->dadosForm(['nome' => 'X']))->assertStatus(422);
        $this->assertSame('Liguinha Pokémon de quinta', $campeonato->fresh()->nome);

        // a lista avisa que o provisório espera a decisão
        $this->get('/campeonatos')->assertSee('participante provisório espera')->assertSee('Visitante da Liga');
    }

    public function test_provisorio_vira_cliente_com_aviso_de_cadastro_incompleto(): void
    {
        $campeonato = $this->liga();
        $visitante = Cliente::where('nome', 'Visitante da Liga')->firstOrFail();

        $this->get('/clientes')->assertDontSee('Visitante da Liga');

        $this->postJson("/campeonatos/{$campeonato->id}/participantes/{$visitante->id}/cadastro", ['acao' => 'adicionar'])->assertOk();
        $visitante->refresh();
        $this->assertSame(Cliente::CADASTRO_CLIENTE, $visitante->cadastro);
        $this->assertTrue($visitante->incompleto);

        $this->get('/clientes')->assertOk()
            ->assertSee('Visitante da Liga')->assertSee('completar o cadastro')->assertSee('Completar cadastro');
        $this->getJson("/clientes/{$visitante->id}")->assertOk()->assertJson(['data_nascimento' => null, 'incompleto' => true]);

        // completar o cadastro pela tela de Clientes tira o aviso
        $this->putJson("/clientes/{$visitante->id}", ['nome' => 'Visitante da Liga', 'data_nascimento' => '2010-01-01', 'status' => 'ativo'])->assertOk();
        $this->assertFalse($visitante->fresh()->incompleto);
        $this->get('/clientes')->assertDontSee('completar o cadastro');
    }

    public function test_provisorio_nao_adicionado_perde_creditos_mas_fica_no_historico(): void
    {
        $campeonato = $this->liga();
        $visitante = Cliente::where('nome', 'Visitante da Liga')->firstOrFail();
        DB::transaction(fn () => $visitante->lancarCredito(15, '2° lugar - Liguinha'));

        // enquanto provisório, aparece em Vendas para gastar os créditos no dia
        $this->getJson('/vendas/clientes?q=Visitante')->assertOk()->assertJsonFragment(['provisorio' => true]);

        $this->postJson("/campeonatos/{$campeonato->id}/participantes/{$visitante->id}/cadastro", ['acao' => 'descartar'])->assertOk();
        $visitante->refresh();
        $this->assertSame(Cliente::CADASTRO_AVULSO, $visitante->cadastro);
        $this->assertEquals(0, (float) $visitante->creditos);
        $this->assertSame('descontar', $visitante->historicoCreditos()->first()->tipo);
        $this->assertTrue($campeonato->participantes()->whereKey($visitante->id)->exists());

        $this->getJson('/vendas/clientes?q=Visitante')->assertOk()->assertJsonCount(0);
        $this->get('/clientes')->assertDontSee('Visitante da Liga');
    }

    public function test_apagar_semanal_pula_a_semana_ou_para_de_repetir(): void
    {
        $liga = $this->liga();
        $serie = $liga->serie;

        // só esta semana: a seguinte fica pré-cadastrada
        $this->delete("/campeonatos/{$liga->id}")->assertRedirect('/campeonatos')->assertSessionHas('success');
        $proxima = Campeonato::where('serie', $serie)->ativos()->firstOrFail();
        $this->assertTrue($proxima->data->equalTo($liga->data->copy()->addWeek()));
        // o provisório da semana apagada (sem outro histórico) sai junto
        $this->assertNull(Cliente::where('nome', 'Visitante da Liga')->first());

        // parar de repetir: nada é criado
        $this->delete("/campeonatos/{$proxima->id}", ['parar_repeticao' => '1']);
        $this->assertSame(0, Campeonato::where('serie', $serie)->count());
    }

    public function test_pagamento_da_inscricao_vira_venda_e_nao_aceita_creditos(): void
    {
        $campeonato = $this->challenge(); // inscrição R$ 20,00
        $guilherme = Cliente::where('nome', 'Guilherme Soares')->firstOrFail();
        $saldo = (float) $guilherme->creditos;
        $url = "/campeonatos/{$campeonato->id}/participantes/{$guilherme->id}/pagamento";

        // créditos não pagam inscrição, nem com a opção ligada em Configurações
        app(\App\Services\Configuracoes::class)->salvar(['vendas.permitir_creditos' => true]);
        $this->postJson($url, ['forma_pagamento' => 'creditos'])->assertStatus(422)->assertJsonValidationErrors('forma_pagamento');
        $this->assertEquals($saldo, (float) $guilherme->fresh()->creditos);
        $this->get("/campeonatos/{$campeonato->id}", self::FRAGMENTO)->assertDontSee('Créditos do cliente');

        $this->postJson($url, ['forma_pagamento' => 'pix'])->assertOk();

        $pivot = DB::table('campeonato_cliente')->where('campeonato_id', $campeonato->id)->where('cliente_id', $guilherme->id)->first();
        $venda = Venda::with('itens')->findOrFail($pivot->venda_id);
        $this->assertEquals(20, (float) $pivot->valor_pago);
        $this->assertEquals(20, (float) $venda->total);
        $this->assertEquals(0, (float) $venda->valor_creditos);
        $this->assertSame('pix', $venda->forma_pagamento);
        $this->assertSame('inscricao', $venda->itens->first()->tipo);

        // aparece em Vendas, no filtro de inscrições
        $this->get('/vendas?tipo=inscricao')->assertOk()->assertSee('Inscrição: Challenge One Piece');
        $this->get("/campeonatos/{$campeonato->id}", self::FRAGMENTO)->assertSee('champ-pill--pago', false);

        // pagar de novo não duplica; inscrição paga não sai pela lista
        $this->postJson($url, ['forma_pagamento' => 'pix'])->assertStatus(422);
        $this->assertSame(1, Venda::count());
        $this->deleteJson("/campeonatos/{$campeonato->id}/participantes/{$guilherme->id}")->assertStatus(422);
        $this->assertTrue($campeonato->participantes()->whereKey($guilherme->id)->exists());

        // cancelar a venda em Vendas deixa a inscrição em aberto de novo
        $this->postJson("/vendas/{$venda->id}/cancelar", ['motivo' => 'teste'])->assertOk();
        $pivot = DB::table('campeonato_cliente')->where('campeonato_id', $campeonato->id)->where('cliente_id', $guilherme->id)->first();
        $this->assertNull($pivot->venda_id);
        $this->assertEquals(0, (float) $pivot->valor_pago);
    }

    public function test_inscrever_ja_pagando_lanca_a_venda_junto(): void
    {
        $campeonato = $this->liga(); // inscrição R$ 10,00
        $rogerio = Cliente::where('nome', 'Rogério Cartinhas')->firstOrFail();
        $base = "/campeonatos/{$campeonato->id}/participantes";

        $this->postJson($base, ['cliente_id' => $rogerio->id, 'forma_pagamento' => 'pix'])
            ->assertOk()->assertJsonPath('mensagem', 'Rogério Cartinhas inscrito e pago.');
        $pivot = DB::table('campeonato_cliente')->where('campeonato_id', $campeonato->id)->where('cliente_id', $rogerio->id)->first();
        $this->assertNotNull($pivot->venda_id);
        $this->assertEquals(10, (float) $pivot->valor_pago);

        // provisório também pode entrar pagando; créditos continuam recusados
        $this->postJson("{$base}/provisorio", ['nome' => 'Fulano Pagante', 'forma_pagamento' => 'creditos'])->assertStatus(422);
        $this->postJson("{$base}/provisorio", ['nome' => 'Fulano Pagante', 'forma_pagamento' => 'dinheiro'])->assertOk();
        $this->assertSame(2, Venda::count());

        // sem forma: inscreve e fica em aberto
        $this->postJson("{$base}/provisorio", ['nome' => 'Fulano Depois', 'forma_pagamento' => ''])->assertOk();
        $this->assertSame(2, Venda::count());

        // caixa do campeonato no pop-up
        $dados = $campeonato->fresh()->load('participantes', 'premios.cliente')->dadosTela();
        $this->assertSame(2, $dados['pagos']);
        $this->assertEquals(20, $dados['arrecadado']);
        $this->assertEquals(50, $dados['aReceber']); // 7 inscritos - 2 pagos = 5 × R$ 10
        $this->assertEquals(50, $dados['totalPremios']);
    }

    public function test_repetir_campeonato_e_modo_check_in(): void
    {
        $original = Campeonato::where('nome', 'Magic Commander')->firstOrFail();
        $original->update(['imagem' => 'campeonatos/banner-proprio.png']);

        $this->get("/campeonatos/criar?copiar={$original->id}")->assertRedirect("/campeonatos?copiar={$original->id}");
        $this->get("/campeonatos/criar?copiar={$original->id}", self::FRAGMENTO)->assertOk()
            ->assertSee('Repetir campeonato')->assertSee('value="Magic Commander"', false)
            ->assertSee('name="copiar_de"', false);

        // sem trocar o banner, o novo usa o mesmo do original
        $this->postJson('/campeonatos', $this->dadosForm(['nome' => 'Magic Commander 2', 'copiar_de' => $original->id]))->assertOk();
        $this->assertSame('campeonatos/banner-proprio.png', Campeonato::where('nome', 'Magic Commander 2')->value('imagem'));

        // check-in: só no campeonato em andamento
        $liga = $this->liga();
        $this->get("/campeonatos/{$liga->id}?modo=checkin", self::FRAGMENTO)->assertOk()
            ->assertSee('champ-modal__painel--checkin', false)->assertDontSee('Checklist do evento oficial');
        $this->get("/campeonatos/{$original->id}?modo=checkin", self::FRAGMENTO)->assertOk()
            ->assertDontSee('champ-modal__painel--checkin', false);
    }

    public function test_data_relativa(): void
    {
        $liga = $this->liga();

        $liga->update(['data' => today()]);
        $this->assertSame('Hoje', $liga->fresh()->quando);
        $liga->update(['data' => today()->addDay()]);
        $this->assertSame('Amanhã', $liga->fresh()->quando);
        $liga->update(['data' => today()->addDays(5)]);
        $this->assertSame('Em 5 dias', $liga->fresh()->quando);
        $liga->update(['data' => today()->subDays(3)]);
        $this->assertSame('Há 3 dias', $liga->fresh()->quando);

        // o de hoje ganha o atalho de check-in na lista
        $liga->update(['data' => today()]);
        $this->get('/campeonatos')->assertSee('Abrir check-in')->assertSee('champ-card--hoje', false);
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
