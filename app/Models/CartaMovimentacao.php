<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Um lançamento de mudança de quantidade de uma carta (ver
 * ProdutoMovimentacao).
 */
class CartaMovimentacao extends Model
{
    const UPDATED_AT = null;

    protected $table = 'carta_movimentacoes';

    protected $fillable = [
        'carta_id',
        'tipo',
        'quantidade',
        'quantidade_anterior',
        'quantidade_nova',
        'descricao',
        'venda_id',
    ];

    public function carta()
    {
        return $this->belongsTo(Carta::class)->withTrashed();
    }

    /**
     * Venda que causou a movimentação (venda ou cancelamento), se houver.
     */
    public function venda()
    {
        return $this->belongsTo(Venda::class);
    }
}
