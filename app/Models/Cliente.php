<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Paleta usada para gerar a cor de fundo do avatar padrão (iniciais),
     * na mesma escala de cores (tom 400) usada no restante da tela.
     */
    private const CORES_AVATAR = [
        '#68a6e9', // azul-400
        '#e07180', // vermelho-400
        '#87ca9e', // verde-400
        '#d3c37e', // oliva-400
        '#a3a7ae', // cinza-400
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
