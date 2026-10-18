<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conta de um funcionário da loja. O sistema é interno: não existe cadastro
 * aberto — as contas são criadas pelo admin em Configurações > Usuários (a
 * primeira, pelo comando `php artisan loja:criar-admin`). O login é pelo
 * nome de usuário; o e-mail é opcional e a própria pessoa vincula.
 *
 * Papéis: o admin pode tudo; o intermediário usa o dia a dia da loja
 * (vendas, aluguéis, clientes, estoque, campeonatos — inclusive cancelar
 * vendas e ajustar créditos), mas não mexe nas configurações da loja, nos
 * usuários, nas exportações nem no registro de atividades.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const PAPEIS = [
        'admin' => 'Administrador',
        'intermediario' => 'Intermediário',
    ];

    // sem letras/números parecidos (0/O, 1/l/I) — a senha provisória é ditada
    // ou anotada para a pessoa
    private const CARACTERES_SENHA = 'abcdefghjkmnpqrstuvwxyz23456789';

    protected $fillable = [
        'nome',
        'usuario',
        'email',
        'password',
        'papel',
        'ativo',
        'deve_trocar_senha',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'ativo' => 'boolean',
        'deve_trocar_senha' => 'boolean',
        'ultimo_login_em' => 'datetime',
    ];

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function isAdmin(): bool
    {
        return $this->papel === 'admin';
    }

    public function papelRotulo(): string
    {
        return self::PAPEIS[$this->papel] ?? $this->papel;
    }

    /**
     * Primeiro nome, para o menu ("Olá, Maria").
     */
    public function primeiroNome(): string
    {
        return strtok($this->nome, ' ') ?: $this->nome;
    }

    /**
     * Senha provisória no formato "abc-def-ghj" (3 grupos de 3 = 9 letras e
     * números aleatórios). Só é mostrada uma vez, para o admin passar à pessoa.
     */
    public static function gerarSenhaProvisoria(): string
    {
        $grupo = fn () => collect(range(1, 3))
            ->map(fn () => self::CARACTERES_SENHA[random_int(0, strlen(self::CARACTERES_SENHA) - 1)])
            ->implode('');

        return $grupo().'-'.$grupo().'-'.$grupo();
    }

    /**
     * Desconecta a pessoa de todos os aparelhos: apaga as sessões guardadas
     * no banco e troca o token do "Manter conectado", invalidando os cookies.
     */
    public function encerrarSessoes(): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $this->id)->delete();

        $this->setRememberToken(Str::random(60));
        $this->save();
    }

    public function sessoesAbertas(): int
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->id)
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function dadosJson(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'papel' => $this->papel,
            'papel_rotulo' => $this->papelRotulo(),
            'ativo' => $this->ativo,
            'deve_trocar_senha' => $this->deve_trocar_senha,
            'ultimo_login' => $this->ultimo_login_em?->format('d/m/Y \à\s H:i'),
            'sessoes' => $this->sessoesAbertas(),
            'eu' => $this->id === auth()->id(),
        ];
    }
}
