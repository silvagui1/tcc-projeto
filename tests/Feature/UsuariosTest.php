<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Papéis (admin x intermediário) e Configurações > Usuários.
 */
class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected bool $entrarAutomaticamente = false;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['nome' => 'Dona Ana', 'usuario' => 'ana']);
    }

    private function intermediario(array $dados = []): User
    {
        return User::factory()->create(['nome' => 'Bruno Lima', 'usuario' => 'bruno', ...$dados]);
    }

    // ---------------------------------------------------------------------
    // Permissões
    // ---------------------------------------------------------------------

    public function test_intermediario_usa_o_dia_a_dia_da_loja(): void
    {
        $this->actingAs($this->intermediario());

        foreach (['/inicio', '/estoque', '/estoque/cartas', '/campeonatos', '/clientes', '/vendas'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_intermediario_ve_so_conta_e_aparencia_em_configuracoes(): void
    {
        $this->actingAs($this->intermediario())->get('/config')
            ->assertOk()
            ->assertSee('Sua conta')
            ->assertSee('Aparência')
            ->assertDontSee('id="usuarios"', false)
            ->assertDontSee('id="loja"', false)
            ->assertDontSee('id="dados"', false)
            ->assertDontSee('Exportar vendas');
    }

    public function test_intermediario_nao_mexe_nas_configuracoes_da_loja_nem_nos_usuarios(): void
    {
        $this->actingAs($this->intermediario());

        $this->putJson('/config/loja', ['nome' => 'Invadida'])->assertForbidden();
        $this->putJson('/config/vendas', [])->assertForbidden();
        $this->postJson('/config/categorias', ['nome' => 'X'])->assertForbidden();
        $this->getJson('/config/atividades')->assertForbidden();
        $this->get('/config/exportar/vendas')->assertForbidden();
        $this->get('/config/exportar/estoque')->assertForbidden();
        $this->getJson('/config/usuarios')->assertForbidden();
        $this->postJson('/config/usuarios', ['nome' => 'Novo', 'usuario' => 'novo', 'papel' => 'admin'])->assertForbidden();
        $this->putJson("/config/usuarios/{$this->admin->id}/status", ['ativo' => false])->assertForbidden();

        $this->assertSame(1, User::where('papel', 'admin')->count());
        $this->assertTrue($this->admin->fresh()->ativo);
    }

    public function test_admin_ve_todas_as_secoes(): void
    {
        $this->actingAs($this->admin)->get('/config')
            ->assertOk()
            ->assertSee('id="usuarios"', false)
            ->assertSee('id="loja"', false)
            ->assertSee('id="dados"', false)
            ->assertSee('Dona Ana');
    }

    // ---------------------------------------------------------------------
    // Gestão de usuários
    // ---------------------------------------------------------------------

    public function test_admin_cria_conta_com_senha_provisoria(): void
    {
        $resposta = $this->actingAs($this->admin)->postJson('/config/usuarios', [
            'nome' => ' Carla Dias ', 'usuario' => 'Carla', 'papel' => 'intermediario',
        ])->assertCreated();

        $senha = $resposta->json('senha');
        $this->assertMatchesRegularExpression('/^[a-z2-9]{3}-[a-z2-9]{3}-[a-z2-9]{3}$/', $senha);

        $carla = User::where('usuario', 'carla')->firstOrFail();
        $this->assertSame('Carla Dias', $carla->nome);
        $this->assertTrue($carla->deve_trocar_senha);
        $this->assertFalse($carla->isAdmin());
        $this->assertTrue(Atividade::where('area', 'usuarios')->where('user_id', $this->admin->id)->exists());

        // a senha provisória funciona no login
        auth()->logout();
        $this->post('/login', ['usuario' => 'carla', 'password' => $senha])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($carla);
    }

    public function test_usuario_repetido_ou_invalido_e_recusado(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/config/usuarios', ['nome' => 'Outra Ana', 'usuario' => 'ANA', 'papel' => 'intermediario'])
            ->assertJsonValidationErrors(['usuario' => 'Esse usuário já existe.']);
        $this->postJson('/config/usuarios', ['nome' => 'Zé', 'usuario' => 'zé silva', 'papel' => 'intermediario'])
            ->assertJsonValidationErrors('usuario');
        $this->postJson('/config/usuarios', ['nome' => 'Zé', 'usuario' => 'ze', 'papel' => 'dono'])
            ->assertJsonValidationErrors('papel');
    }

    public function test_admin_edita_e_promove(): void
    {
        $bruno = $this->intermediario();

        $this->actingAs($this->admin)->putJson("/config/usuarios/{$bruno->id}", [
            'nome' => 'Bruno Lima', 'usuario' => 'bruno.lima', 'papel' => 'admin',
        ])->assertOk();

        $this->assertTrue($bruno->fresh()->isAdmin());
        $this->assertSame('bruno.lima', $bruno->fresh()->usuario);
    }

    public function test_admin_nao_rebaixa_nem_desativa_a_si_mesmo(): void
    {
        $this->actingAs($this->admin);

        $this->putJson("/config/usuarios/{$this->admin->id}", ['nome' => 'Dona Ana', 'usuario' => 'ana', 'papel' => 'intermediario'])
            ->assertJsonValidationErrors('papel');
        $this->putJson("/config/usuarios/{$this->admin->id}/status", ['ativo' => false])->assertStatus(422);
        $this->postJson("/config/usuarios/{$this->admin->id}/senha")->assertStatus(422);

        $this->assertTrue($this->admin->fresh()->isAdmin());
        $this->assertTrue($this->admin->fresh()->ativo);
    }

    public function test_desativar_desconecta_e_impede_o_login(): void
    {
        $bruno = $this->intermediario(['password' => 'senha1234']);
        DB::table('sessions')->insert(['id' => 'sessao-do-bruno', 'user_id' => $bruno->id, 'payload' => '', 'last_activity' => now()->timestamp]);
        $tokenAntes = $bruno->remember_token;

        $this->actingAs($this->admin)->putJson("/config/usuarios/{$bruno->id}/status", ['ativo' => false])->assertOk();

        $this->assertFalse($bruno->fresh()->ativo);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $bruno->id)->count());
        $this->assertNotSame($tokenAntes, $bruno->fresh()->remember_token);

        auth()->logout();
        $this->post('/login', ['usuario' => 'bruno', 'password' => 'senha1234'])->assertSessionHasErrors('usuario');
        $this->assertGuest();

        // reativado, volta a entrar
        $this->actingAs($this->admin)->putJson("/config/usuarios/{$bruno->id}/status", ['ativo' => true])->assertOk();
        auth()->logout();
        $this->post('/login', ['usuario' => 'bruno', 'password' => 'senha1234'])->assertRedirect();
        $this->assertAuthenticatedAs($bruno);
    }

    public function test_redefinir_senha_gera_provisoria_e_derruba_a_antiga(): void
    {
        $bruno = $this->intermediario(['password' => 'senha1234']);

        $senha = $this->actingAs($this->admin)->postJson("/config/usuarios/{$bruno->id}/senha")->assertOk()->json('senha');

        $this->assertTrue($bruno->fresh()->deve_trocar_senha);

        auth()->logout();
        $this->post('/login', ['usuario' => 'bruno', 'password' => 'senha1234'])->assertSessionHasErrors('usuario');
        $this->post('/login', ['usuario' => 'bruno', 'password' => $senha])->assertRedirect();
        $this->assertAuthenticatedAs($bruno);
    }

    public function test_encerrar_sessoes(): void
    {
        $bruno = $this->intermediario();
        DB::table('sessions')->insert(['id' => 'sessao-do-bruno', 'user_id' => $bruno->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        $this->actingAs($this->admin)->getJson('/config/usuarios')
            ->assertJsonFragment(['usuario' => 'bruno', 'sessoes' => 1]);

        $this->postJson("/config/usuarios/{$bruno->id}/sessoes")->assertOk();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $bruno->id)->count());
    }

    // ---------------------------------------------------------------------
    // Senha provisória: precisa criar a própria antes de usar o sistema
    // ---------------------------------------------------------------------

    public function test_quem_tem_senha_provisoria_so_cria_a_senha_ou_sai(): void
    {
        $bruno = $this->intermediario(['deve_trocar_senha' => true, 'password' => 'abc-def-ghj']);
        $this->actingAs($bruno);

        $this->get('/inicio')->assertRedirect(route('conta.criar-senha'));
        $this->get('/vendas')->assertRedirect(route('conta.criar-senha'));
        $this->postJson('/vendas', [])->assertForbidden();
        $this->get('/conta/criar-senha')->assertOk()->assertSee('Crie a sua senha');

        // fraca, diferente na confirmação ou igual à provisória: recusada
        $this->put('/conta/criar-senha', ['senha' => 'curta', 'senha_confirmation' => 'curta'])->assertSessionHasErrors('senha');
        $this->put('/conta/criar-senha', ['senha' => 'novaSenha1', 'senha_confirmation' => 'outra123'])->assertSessionHasErrors('senha');

        $this->put('/conta/criar-senha', ['senha' => 'novaSenha1', 'senha_confirmation' => 'novaSenha1'])
            ->assertRedirect(route('home'));

        $this->assertFalse($bruno->fresh()->deve_trocar_senha);
        $this->get('/inicio')->assertOk();
        $this->get('/conta/criar-senha')->assertRedirect(route('home'));
    }

    // ---------------------------------------------------------------------
    // Registro de atividades com "quem fez"
    // ---------------------------------------------------------------------

    public function test_atividade_guarda_quem_fez(): void
    {
        $bruno = $this->intermediario();

        $this->actingAs($bruno)->postJson('/clientes', [
            'nome' => 'Cliente Teste', 'data_nascimento' => '2000-01-01', 'status' => 'ativo',
        ]);

        $atividade = Atividade::where('area', 'clientes')->latest('id')->first();
        $this->assertNotNull($atividade);
        $this->assertSame($bruno->id, $atividade->user_id);

        // outra pessoa no mesmo "navegador": sessão nova (senão o auth.session
        // vê o hash da senha do Bruno e desconecta)
        $this->flushSession();
        $this->actingAs($this->admin)->getJson('/config/atividades')
            ->assertJsonFragment(['usuario' => 'Bruno Lima']);
    }
}
