<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use App\Services\Configuracoes;
use Illuminate\Database\Eloquent\Builder;
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
     * Quantidade a partir da qual o produto entra no alerta de estoque baixo
     * (Configurações > Estoque), ou null com o alerta desligado.
     */
    public static function alertaMinimo(): ?int
    {
        return Configuracoes::valor('estoque.alerta_ativo') ? (int) Configuracoes::valor('estoque.alerta_minimo') : null;
    }

    /**
     * Produtos com quantidade menor ou igual ao mínimo do alerta (nenhum,
     * se o alerta está desligado).
     */
    public function scopeEstoqueBaixo(Builder $query): Builder
    {
        $minimo = self::alertaMinimo();

        return $minimo === null ? $query->whereRaw('1 = 0') : $query->where('quantidade', '<=', $minimo);
    }

    public function getEstoqueBaixoAttribute(): bool
    {
        $minimo = self::alertaMinimo();

        return $minimo !== null && $this->quantidade <= $minimo;
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
