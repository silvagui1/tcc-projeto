<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use TemImagemDeEstoque;

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'quantidade',
        'imagem',
        'categoria_id',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'quantidade' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(ProdutoMovimentacao::class)->latest('id');
    }

    /**
     * Imagem genérica (public/images/placeholders) da categoria do produto —
     * usada quando ele não tem imagem e também de reserva no CSS quando a
     * url informada não carrega.
     */
    public function getImagemPadraoAttribute(): string
    {
        $arquivo = match (mb_strtolower($this->categoria?->nome ?? '')) {
            'comida' => 'produto-comida.svg',
            'bebida' => 'produto-bebida.svg',
            'cartas' => 'produto-cartas.svg',
            'acessório', 'acessórios' => 'produto-acessorios.svg',
            default => 'produto-generico.svg',
        };

        return asset('images/placeholders/'.$arquivo);
    }

    /**
     * Dados usados pelo pop-up de edição (preenche o formulário via JS).
     *
     * @return array<string, mixed>
     */
    public function dadosFormulario(): array
    {
        return $this->only(['id', 'nome', 'descricao', 'quantidade', 'categoria_id'])
            + ['preco' => (float) $this->preco]
            + $this->dadosDeImagemDoFormulario();
    }
}
