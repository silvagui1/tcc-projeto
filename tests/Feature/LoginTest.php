<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Login da equipe: sistema interno, sem cadastro aberto.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected bool $entrarAutomaticamente = false;

    private function usuario(array $dados = []): User
    {
        return User::factory()->create(['usuario' => 'maria', 'password' => 'senha1234', ...$dados]);
    }

    public function test_quem_nao_esta_conectado_vai_para_o_login(): void
    {
        foreach (['/', '/inicio', '/estoque', '/campeonatos', '/clientes', '/vendas', '/config', '/config/exportar/vendas'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_chamada_via_fetch_sem_login_recebe_401(): void
    {
        $this->getJson('/clientes/buscar?q=a')->assertUnauthorized();
        $this->postJson('/vendas', [])->assertUnauthorized();
    }

    public function test_nao_existe_cadastro_publico(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['usuario' => 'invasor', 'password' => 'x'])->assertNotFound();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Área restrita')
            ->assertSee('Não existe cadastro pelo site')
            ->assertDontSee('Criar conta');
    }

    public function test_rotas_de_projetos_antigos_nao_existem_mais(): void
    {
        $this->usuario();
        $this->actingAs(User::first());

        $this->get('/aluno')->assertNotFound();
        $this->get('/api/teste')->assertNotFound();
    }

    public function test_entra_com_usuario_e_senha(): void
    {
        $usuario = $this->usuario();

        $this->post('/login', ['usuario' => ' Maria ', 'password' => 'senha1234'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->fresh()->ultimo_login_em);
        $this->assertTrue(Atividade::where('area', 'acesso')->where('descricao', "{$usuario->nome} entrou no sistema")->exists());
    }

    public function test_volta_para_a_pagina_que_tentou_abrir(): void
    {
        $this->usuario();

        $this->get('/estoque');
        $this->post('/login', ['usuario' => 'maria', 'password' => 'senha1234'])
            ->assertRedirect('/estoque');
    }

    public function test_senha_errada_mostra_mensagem_generica(): void
    {
        $this->usuario();

        $this->from('/login')->post('/login', ['usuario' => 'maria', 'password' => 'errada'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['usuario' => 'Usuário ou senha incorretos.']);

        // usuário que não existe: mesma mensagem (não revela quem tem conta)
        $this->from('/login')->post('/login', ['usuario' => 'ninguem', 'password' => 'errada'])
            ->assertSessionHasErrors(['usuario' => 'Usuário ou senha incorretos.']);

        $this->assertGuest();
        $this->assertSame(2, Atividade::where('area', 'acesso')->count());
    }

    public function test_conta_desativada_nao_entra(): void
    {
        $this->usuario(['ativo' => false]);

        $this->post('/login', ['usuario' => 'maria', 'password' => 'senha1234'])
            ->assertSessionHasErrors(['usuario' => 'Usuário ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_quem_e_desativado_conectado_cai_fora(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario)->get('/inicio')->assertOk();

        $usuario->update(['ativo' => false]);

        $this->get('/inicio')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_bloqueia_depois_de_muitas_tentativas(): void
    {
        $this->usuario();
        RateLimiter::clear('login|maria|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['usuario' => 'maria', 'password' => 'errada']);
        }

        // nem a senha certa entra enquanto estiver bloqueado
        $this->post('/login', ['usuario' => 'maria', 'password' => 'senha1234'])
            ->assertSessionHasErrors('usuario');

        $this->assertGuest();
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('usuario'));
    }

    public function test_manter_conectado_cria_cookie_de_lembrar(): void
    {
        $this->usuario();

        $resposta = $this->post('/login', ['usuario' => 'maria', 'password' => 'senha1234', 'lembrar' => '1']);

        $cookie = collect($resposta->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNotNull($cookie);
        // 30 dias (config/auth.php), com folga de um minuto
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 60);
    }

    public function test_quem_ja_esta_conectado_nao_ve_o_login(): void
    {
        $this->actingAs($this->usuario())->get('/login')->assertRedirect('/inicio');
    }

    public function test_sair(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Atividade::where('descricao', "{$usuario->nome} saiu do sistema")->exists());
    }

    public function test_navbar_mostra_quem_esta_conectado(): void
    {
        $usuario = $this->usuario(['nome' => 'Maria Souza']);

        $this->actingAs($usuario)->get('/inicio')
            ->assertSee('Maria')
            ->assertSee(route('logout'));
    }

    public function test_comando_cria_o_primeiro_admin(): void
    {
        $this->artisan('loja:criar-admin')
            ->expectsQuestion('Nome completo', 'Dona da Loja')
            ->expectsQuestion('Usuário (usado para entrar, ex.: maria)', 'Dona')
            ->expectsQuestion('Senha (mínimo 8 caracteres, com letras e números)', 'segura123')
            ->expectsQuestion('Repita a senha', 'segura123')
            ->assertSuccessful();

        $admin = User::where('usuario', 'dona')->firstOrFail();
        $this->assertTrue($admin->isAdmin());

        // segundo admin pelo comando só com --forcar
        $this->artisan('loja:criar-admin')->assertFailed();
    }
}
