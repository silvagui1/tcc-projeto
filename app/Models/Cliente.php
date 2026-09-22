<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Cliente extends Model
{
    use HasFactory;

    /**
     * Paleta usada para gerar a cor de fundo do avatar padrão (iniciais).
     * Mesmos valores dos tokens --blue-400/--pink-400/--purple-500/
     * --purple-light-800/--navy-900 em resources/css/app.css (portados de
     * mobilenav_atualizado) — se a paleta mudar lá, atualizar aqui e em
     * resources/js/clientes.js (AVATAR_CORES) também, pois CSS não é
     * acessível a partir do PHP/JS.
     */
    private const CORES_AVATAR = [
        '#8bbaed', // --blue-400
        '#b47194', // --pink-400
        '#53577d', // --purple-500
        '#6c6588', // --purple-light-800
        '#2e3045', // --navy-900
    ];

    protected $fillable = [
        'nome',
        'data_nascimento',
        'foto',
        'observacoes',
        'creditos',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'creditos' => 'decimal:2',
    ];

    /**
     * URL pública da foto do cliente, ou null quando ele ainda não tem
     * foto cadastrada (nesse caso a tela usa o avatar de iniciais).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    /**
     * Duas primeiras letras do nome, usadas no avatar padrão quando o
     * cliente não possui foto cadastrada.
     */
    public function getIniciaisAttribute(): string
    {
        return mb_strtoupper(mb_substr(trim($this->nome), 0, 2));
    }

    /**
     * Cor de fundo determinística do avatar de iniciais, com base no id
     * do cliente, para que cada cliente sempre tenha a mesma cor.
     */
    public function getCorAvatarAttribute(): string
    {
        return self::CORES_AVATAR[$this->id % count(self::CORES_AVATAR)];
    }
}
