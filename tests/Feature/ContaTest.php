<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Configurações > Sua conta: e-mail opcional e troca de senha.
 */
class ContaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $entrarAutomaticamente = false;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['usuario' => 'bruno', 'password' => 'senha1234']);
        $this->actingAs($this->usuario);
    }

    public function test_vincula_e_remove_o_email(): void
    {
        $this->putJson('/conta/email', ['email' => ' Bruno@Loja.com '])->assertOk();
        $this->assertSame('bruno@loja.com', $this->usuario->fresh()->email);

        $this->putJson('/conta/email', ['email' => ''])->assertOk();
        $this->assertNull($this->usuario->fresh()->email);
    }

    public function test_email_invalido_ou_de_outra_conta_e_recusado(): void
    {
        User::factory()->create(['email' => 'ocupado@loja.com']);

        $this->putJson('/conta/email', ['email' => 'nao-e-email'])->assertJsonValidationErrors('email');
        $this->putJson('/conta/email', ['email' => 'ocupado@loja.com'])
            ->assertJsonValidationErrors(['email' => 'Esse e-mail já está vinculado a outra conta.']);
    }

    public function test_login_continua_pelo_usuario_mesmo_com_email(): void
    {
        $this->usuario->update(['email' => 'bruno@loja.com']);
        auth()->logout();

        $this->post('/login', ['usuario' => 'bruno@loja.com', 'password' => 'senha1234'])->assertSessionHasErrors('usuario');
        $this->post('/login', ['usuario' => 'bruno', 'password' => 'senha1234'])->assertRedirect();
    }

    public function test_troca_a_senha(): void
    {
        $this->putJson('/conta/senha', ['senha_atual' => 'senha1234', 'senha' => 'novaSenha9', 'senha_confirmation' => 'novaSenha9'])
            ->assertOk();

        $this->assertTrue(Hash::check('novaSenha9', $this->usuario->fresh()->password));
        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_troca_de_senha_exige_a_atual_e_uma_senha_forte(): void
    {
        $this->putJson('/conta/senha', ['senha_atual' => 'errada', 'senha' => 'novaSenha9', 'senha_confirmation' => 'novaSenha9'])
            ->assertJsonValidationErrors(['senha_atual' => 'A senha atual não confere.']);
        $this->putJson('/conta/senha', ['senha_atual' => 'senha1234', 'senha' => 'somenteletras', 'senha_confirmation' => 'somenteletras'])
            ->assertJsonValidationErrors('senha');
        $this->putJson('/conta/senha', ['senha_atual' => 'senha1234', 'senha' => 'senha1234', 'senha_confirmation' => 'senha1234'])
            ->assertJsonValidationErrors('senha');

        $this->assertTrue(Hash::check('senha1234', $this->usuario->fresh()->password));
    }

    public function test_trocar_a_senha_derruba_os_outros_aparelhos(): void
    {
        // "outro aparelho": uma sessão que guardou o hash da senha antiga
        $hashAntigo = $this->usuario->password;
        $this->usuario->update(['password' => 'novaSenha9']);

        $this->withSession(['password_hash_web' => $hashAntigo])
            ->get('/inicio')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
