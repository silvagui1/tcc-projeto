<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Cria a conta de administrador pelo terminal. É o único jeito de criar a
 * primeira conta: o sistema não tem cadastro aberto, então quem achar o
 * endereço do site não consegue se registrar. A senha é digitada escondida
 * e não fica salva em nenhum arquivo do projeto.
 */
class CriarAdmin extends Command
{
    protected $signature = 'loja:criar-admin {--forcar : Cria mesmo que já exista um administrador}';

    protected $description = 'Cria uma conta de administrador para entrar no sistema da loja';

    public function handle(): int
    {
        if (User::where('papel', 'admin')->exists() && ! $this->option('forcar')) {
            $this->error('Já existe um administrador. Use --forcar para criar outro.');

            return self::FAILURE;
        }

        $nome = trim((string) $this->ask('Nome completo'));
        $usuario = Str::lower(trim((string) $this->ask('Usuário (usado para entrar, ex.: maria)')));
        $senha = (string) $this->secret('Senha (mínimo 8 caracteres, com letras e números)');
        $confirmacao = (string) $this->secret('Repita a senha');

        $validador = validator(
            ['nome' => $nome, 'usuario' => $usuario, 'senha' => $senha, 'senha_confirmation' => $confirmacao],
            [
                'nome' => ['required', 'string', 'max:80'],
                'usuario' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', 'unique:users,usuario'],
                'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ],
            [
                'usuario.regex' => 'O usuário só pode ter letras minúsculas, números, ponto, hífen e sublinhado.',
                'usuario.unique' => 'Esse usuário já existe.',
                'senha.confirmed' => 'As senhas não conferem.',
            ],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        User::create([
            'nome' => $nome,
            'usuario' => $usuario,
            'password' => $senha,
            'papel' => 'admin',
            'ativo' => true,
        ]);

        $this->info("Administrador \"{$usuario}\" criado. Já dá para entrar em ".route('login'));

        return self::SUCCESS;
    }
}
