<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * A própria conta de quem está conectado: vincular e-mail (opcional),
 * trocar a senha e — depois de receber uma senha provisória do admin —
 * criar a senha definitiva antes de usar o sistema.
 *
 * Trocar a senha desconecta os outros aparelhos (middleware auth.session
 * compara o hash da senha guardado em cada sessão).
 */
class ContaController extends Controller
{
    public function salvarEmail(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email'))) ?: null]);

        $dados = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:120', Rule::unique('users', 'email')->ignore($usuario)],
        ], [
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail pode ter no máximo :max caracteres.',
            'email.unique' => 'Esse e-mail já está vinculado a outra conta.',
        ]);

        $antes = $usuario->email;
        $usuario->update(['email' => $dados['email']]);

        if ($antes !== $usuario->email) {
            Atividade::registrar('acesso', $usuario->email ? "{$usuario->nome} vinculou um e-mail à conta" : "{$usuario->nome} removeu o e-mail da conta");
        }

        return response()->json(['message' => $usuario->email ? 'E-mail vinculado à sua conta.' : 'E-mail removido da sua conta.']);
    }

    public function trocarSenha(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'senha_atual' => ['required', 'current_password'],
            'senha' => ['required', 'confirmed', 'different:senha_atual', $this->regraSenha()],
        ], $this->mensagensSenha() + [
            'senha_atual.required' => 'Informe a sua senha atual.',
            'senha_atual.current_password' => 'A senha atual não confere.',
            'senha.different' => 'A nova senha precisa ser diferente da atual.',
        ]);

        $this->gravarSenha($request, $dados['senha']);

        return response()->json(['message' => 'Senha trocada. Os outros aparelhos foram desconectados.']);
    }

    /**
     * Tela de "crie a sua senha", obrigatória para quem entrou com senha
     * provisória (ver middleware SenhaTrocada).
     */
    public function formCriarSenha(Request $request): View|RedirectResponse
    {
        if (! $request->user()->deve_trocar_senha) {
            return redirect()->route('home');
        }

        return view('auth.criar-senha');
    }

    public function criarSenha(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        if (! $usuario->deve_trocar_senha) {
            return redirect()->route('home');
        }

        $dados = $request->validate([
            'senha' => ['required', 'confirmed', $this->regraSenha(), function ($atributo, $valor, $falhar) use ($usuario) {
                if (Auth::getProvider()->validateCredentials($usuario, ['password' => $valor])) {
                    $falhar('Escolha uma senha diferente da provisória.');
                }
            }],
        ], $this->mensagensSenha());

        $this->gravarSenha($request, $dados['senha']);

        return redirect()->intended(route('home'));
    }

    private function gravarSenha(Request $request, string $senha): void
    {
        $usuario = $request->user();
        $usuario->forceFill(['password' => $senha, 'deve_trocar_senha' => false])->save();

        // renova o cookie do "Manter conectado" deste aparelho com a senha
        // nova; as outras sessões caem no próximo acesso (auth.session)
        Auth::guard('web')->logoutOtherDevices($senha);

        Atividade::registrar('acesso', "{$usuario->nome} trocou a senha");
    }

    private function regraSenha(): Password
    {
        return Password::min(8)->letters()->numbers();
    }

    /**
     * @return array<string, string>
     */
    private function mensagensSenha(): array
    {
        return [
            'senha.required' => 'Informe a nova senha.',
            'senha.confirmed' => 'As senhas não conferem.',
            'senha.min' => 'A senha precisa ter pelo menos :min caracteres.',
            'senha.letters' => 'A senha precisa ter pelo menos uma letra.',
            'senha.numbers' => 'A senha precisa ter pelo menos um número.',
        ];
    }
}
