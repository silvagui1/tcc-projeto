<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Um item de uma venda: produto, carta avulsa ou aluguel de mesa. Nome e
 * preço ficam copiados aqui, então o histórico não muda se o item for
 * editado ou apagado do estoque depois.
 */
class VendaItem extends Model
{
    public $timestamps = false;

    protected $table = 'venda_itens';

    public const TIPOS = ['produto', 'carta', 'aluguel'];

    /**
     * Inscrição em campeonato: não entra pelo modal de nova venda (por isso
     * fica fora de TIPOS), é lançada pela tela do campeonato ao registrar o
     * pagamento. Aparece no histórico e no filtro de Vendas.
     */
    public const TIPO_INSCRICAO = 'inscricao';

    public const TIPOS_HISTORICO = [...self::TIPOS, self::TIPO_INSCRICAO];

    protected $fillable = [
        'venda_id',
        'tipo',
        'produto_id',
        'carta_id',
        'aluguel_id',
        'campeonato_id',
        'descricao',
        'quantidade',
        'preco_unitario',
        'subtotal',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function carta()
    {
        return $this->belongsTo(Carta::class);
    }

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function aluguel()
    {
        return $this->belongsTo(Aluguel::class);
    }
}
