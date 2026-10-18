<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Telas de erro do sistema (resources/views/errors) no lugar das páginas
 * genéricas, e senhas que nunca voltam para a sessão.
 */
class ErrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_que_nao_existe_mostra_a_tela_do_sistema(): void
    {
        $this->get('/nao-existe-esta-pagina')
            ->assertNotFound()
            ->assertSee('Página não encontrada')
            ->assertSee('Sistema interno de gestão')
            ->assertSee('Ir para o início');
    }

    public function test_area_do_admin_mostra_acesso_restrito_para_o_intermediario(): void
    {
        $this->flushSession();
        $this->actingAs(User::factory()->create());

        $this->get('/config/exportar/vendas')
            ->assertForbidden()
            ->assertSee('Acesso restrito')
            ->assertSee('exclusiva do administrador');
    }

    public function test_metodo_errado_mostra_a_tela_do_sistema(): void
    {
        $this->get('/logout')->assertStatus(405)->assertSee('Ação não permitida');
    }

    public function test_erro_inesperado_mostra_a_tela_do_sistema_sem_detalhes(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_teste-erro', fn () => throw new RuntimeException('detalhe interno secreto'));

        $this->get('/_teste-erro')
            ->assertStatus(500)
            ->assertSee('Algo deu errado')
            ->assertDontSee('detalhe interno secreto')
            ->assertDontSee('RuntimeException');
    }

    public function test_tela_de_erro_funciona_mesmo_com_o_banco_fora_do_ar(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_teste-banco', function () {
            // derruba a conexão: a tela de erro não pode depender do banco
            config(['database.connections.sqlite.database' => '/caminho/que/nao/existe.sqlite']);
            app('db')->purge('sqlite');
            throw new RuntimeException('banco caiu');
        });

        $this->get('/_teste-banco')
            ->assertStatus(500)
            ->assertSee('Algo deu errado')
            ->assertSee('Sistema da loja');
    }

    public function test_paginas_de_sessao_expirada_e_muitas_tentativas_existem(): void
    {
        foreach ([419 => 'A sessão expirou', 429 => 'Muitas tentativas', 503 => 'Sistema em manutenção', 401 => 'Entre para continuar'] as $codigo => $titulo) {
            $html = view("errors.{$codigo}")->render();
            $this->assertStringContainsString($titulo, $html);
        }
    }

    public function test_chamada_via_fetch_continua_recebendo_json(): void
    {
        $this->getJson('/nao-existe-esta-pagina')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_senhas_digitadas_nunca_voltam_para_a_sessao(): void
    {
        $this->flushSession();
        $this->actingAs(User::factory()->create(['deve_trocar_senha' => true]));

        $this->from('/conta/criar-senha')
            ->put('/conta/criar-senha', ['senha' => 'fraca', 'senha_confirmation' => 'outra'])
            ->assertSessionHasErrors('senha');

        $this->assertNull(session()->getOldInput('senha'));
        $this->assertNull(session()->getOldInput('senha_confirmation'));
    }
}
