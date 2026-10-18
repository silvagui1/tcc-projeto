<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Configurações > Usuários (só admin). É o único lugar onde contas são
 * criadas. Quem é criado (ou tem a senha redefinida) recebe uma senha
 * provisória, mostrada uma única vez ao admin, e cria a própria no primeiro
 * acesso. Contas não são apagadas — são desativadas — para o registro de
 * atividades continuar mostrando quem fez o quê.
 *
 * O admin não mexe no próprio papel, status ou senha por aqui (a senha ele
 * troca em "Sua conta"): assim sempre sobra pelo menos um admin ativo.
 */
class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['itens' => $this->lista()]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $this->validar($request);
        $senha = User::gerarSenhaProvisoria();

        $usuario = User::create([
            ...$dados,
            'password' => $senha,
            'ativo' => true,
            'deve_trocar_senha' => true,
        ]);

        Atividade::registrar('usuarios', "Usuário criado: {$usuario->nome} ({$usuario->usuario}) · {$usuario->papelRotulo()}");

        return response()->json([
            'message' => "Conta de {$usuario->primeiroNome()} criada.",
            'senha' => $senha,
            'usuario' => $usuario->dadosJson(),
            'itens' => $this->lista(),
        ], 201);
    }

    public function update(Request $request, User $usuario): JsonResponse
    {
        $dados = $this->validar($request, $usuario);

        if ($usuario->is($request->user()) && $dados['papel'] !== $usuario->papel) {
            throw ValidationException::withMessages(['papel' => 'Você não pode mudar o seu próprio papel.']);
        }

        $papelAntes = $usuario->papelRotulo();
        $usuario->update($dados);

        Atividade::registrar('usuarios', "Usuário editado: {$usuario->nome} ({$usuario->usuario})"
            .($papelAntes !== $usuario->papelRotulo() ? " · {$papelAntes} → {$usuario->papelRotulo()}" : ''));

        return response()->json(['message' => 'Dados salvos.', 'itens' => $this->lista()]);
    }

    public function status(Request $request, User $usuario): JsonResponse
    {
        $this->naoPodeSerVoceMesmo($request, $usuario, 'Você não pode desativar a sua própria conta.');

        $ativo = $request->boolean('ativo');
        $usuario->update(['ativo' => $ativo]);

        if (! $ativo) {
            $usuario->encerrarSessoes();
        }

        Atividade::registrar('usuarios', ($ativo ? 'Usuário reativado: ' : 'Usuário desativado: ')."{$usuario->nome} ({$usuario->usuario})");

        return response()->json([
            'message' => $ativo ? "{$usuario->primeiroNome()} pode entrar de novo." : "{$usuario->primeiroNome()} foi desativado e desconectado.",
            'itens' => $this->lista(),
        ]);
    }

    public function redefinirSenha(Request $request, User $usuario): JsonResponse
    {
        $this->naoPodeSerVoceMesmo($request, $usuario, 'Para trocar a sua senha, use "Sua conta".');

        $senha = User::gerarSenhaProvisoria();
        $usuario->update(['password' => $senha, 'deve_trocar_senha' => true]);
        $usuario->encerrarSessoes();

        Atividade::registrar('usuarios', "Senha redefinida: {$usuario->nome} ({$usuario->usuario})");

        return response()->json([
            'message' => 'Senha redefinida.',
            'senha' => $senha,
            'usuario' => $usuario->dadosJson(),
            'itens' => $this->lista(),
        ]);
    }

    public function encerrarSessoes(Request $request, User $usuario): JsonResponse
    {
        $this->naoPodeSerVoceMesmo($request, $usuario, 'Para sair deste aparelho, use o botão "Sair".');

        $usuario->encerrarSessoes();

        Atividade::registrar('usuarios', "Sessões encerradas: {$usuario->nome} ({$usuario->usuario})");

        return response()->json([
            'message' => "{$usuario->primeiroNome()} foi desconectado de todos os aparelhos.",
            'itens' => $this->lista(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lista(): array
    {
        return User::query()
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get()
            ->map(fn (User $u) => $u->dadosJson())
            ->all();
    }

    /**
     * @return array{nome: string, usuario: string, papel: string}
     */
    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge([
            'nome' => trim((string) $request->input('nome')),
            'usuario' => Str::lower(trim((string) $request->input('usuario'))),
        ]);

        return $request->validate([
            'nome' => ['required', 'string', 'max:80'],
            'usuario' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'usuario')->ignore($usuario)],
            'papel' => ['required', Rule::in(array_keys(User::PAPEIS))],
        ], [
            'nome.required' => 'Informe o nome.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
            'usuario.required' => 'Informe o usuário.',
            'usuario.min' => 'O usuário precisa ter pelo menos :min caracteres.',
            'usuario.max' => 'O usuário pode ter no máximo :max caracteres.',
            'usuario.regex' => 'Use só letras minúsculas, números, ponto, hífen ou sublinhado (sem espaços nem acentos).',
            'usuario.unique' => 'Esse usuário já existe.',
            'papel.required' => 'Escolha o papel.',
            'papel.in' => 'Papel inválido.',
        ]);
    }

    private function naoPodeSerVoceMesmo(Request $request, User $usuario, string $mensagem): void
    {
        if ($usuario->is($request->user())) {
            abort(response()->json(['message' => $mensagem], 422));
        }
    }
}
