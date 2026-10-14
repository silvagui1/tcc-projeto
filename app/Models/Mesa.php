<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $fillable = [
        'nome',
        'capacidade',
        'preco_hora',
        'ativa',
    ];

    protected $casts = [
        'capacidade' => 'integer',
        'preco_hora' => 'decimal:2',
        'ativa' => 'boolean',
    ];

    public function alugueis()
    {
        return $this->hasMany(Aluguel::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    /**
     * Formato usado pelo JS da tela de vendas (modal de mesas e formulário
     * de aluguel).
     *
     * @return array<string, mixed>
     */
    public function dadosJson(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'capacidade' => $this->capacidade,
            'preco_hora' => (float) $this->preco_hora,
            'ativa' => $this->ativa,
        ];
    }
}
