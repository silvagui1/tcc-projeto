<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Um lançamento de mudança de quantidade de um produto. Assim como
 * ClienteCreditoHistorico, só é criado pelo controller (nunca editado ou
 * apagado) para servir de trilha de auditoria simples.
 */
class ProdutoMovimentacao extends Model
{
    const UPDATED_AT = null;

    protected $table = 'produto_movimentacoes';

    protected $fillable = [
        'produto_id',
        'tipo',
        'quantidade',
        'quantidade_anterior',
        'quantidade_nova',
        'descricao',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
