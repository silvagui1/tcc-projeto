<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Premiação de uma colocação (1°, 2° ou 3° lugar) de um campeonato. O valor
 * é creditado no saldo do cliente ao finalizar o campeonato.
 */
class Premio extends Model
{
    protected $fillable = [
        'campeonato_id',
        'cliente_id',
        'colocacao',
        'valor',
        'descricao',
    ];

    protected $casts = [
        'colocacao' => 'integer',
        'valor' => 'decimal:2',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
