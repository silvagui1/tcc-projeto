<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jogo de cartas aceito no estoque (Pokémon, Magic...). Cadastrado em
 * Configurações > Estoque. cartas.jogo guarda a chave.
 */
class JogoCarta extends Model
{
    protected $table = 'jogos_carta';

    protected $fillable = ['chave', 'nome'];

    public function cartas()
    {
        return $this->hasMany(Carta::class, 'jogo', 'chave');
    }

    /**
     * chave => nome, na ordem de cadastro.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return self::orderBy('id')->pluck('nome', 'chave')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function chaves(): array
    {
        return array_keys(self::opcoes());
    }

    public static function nomeDe(?string $chave): string
    {
        return self::opcoes()[$chave] ?? ucfirst((string) $chave);
    }
}
