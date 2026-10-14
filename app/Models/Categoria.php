<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'comercializavel',
    ];

    // false = os produtos só podem ser dados (brindes), não vendidos
    protected $casts = [
        'comercializavel' => 'boolean',
    ];

    public function produtos()
    {
        return $this->hasMany(Produto::class);
    }
}
