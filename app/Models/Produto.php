<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use App\Models\Concerns\TemMovimentacoes;
use App\Services\Configuracoes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    // SoftDeletes: "apagar" arquiva (ver migration add_arquivamento_ao_estoque)
    use SoftDeletes, TemImagemDeEstoque, TemMovimentacoes;

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'quantidade',
        'estoque_minimo',
        'imagem',
        'categoria_id',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'quantidade' => 'integer',
        'estoque_minimo' => 'integer',
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
     * Só produtos que podem ser vendidos (categoria comercializável).
     */
    public function scopeComercializaveis(Builder $query): Builder
    {
        return $query->whereHas('categoria', fn (Builder $q) => $q->where('comercializavel', true));
    }

    /**
     * false para itens que só podem ser dados (categoria não comercializável).
     */
    public function getComercializavelAttribute(): bool
    {
        return $this->categoria?->comercializavel ?? true;
    }

    /**
     * Quantidade padrão a partir da qual um produto entra no alerta de
     * estoque baixo (Configurações > Estoque), ou null com o alerta desligado.
     */
    public static function alertaMinimo(): ?int
    {
        return Configuracoes::valor('estoque.alerta_ativo') ? (int) Configuracoes::valor('estoque.alerta_minimo') : null;
    }

    /**
     * Produtos com quantidade menor ou igual ao seu limite de estoque baixo:
     * o do próprio produto (estoque_minimo) ou, sem ele, o padrão. Nenhum,
     * se o alerta está desligado.
     */
    public function scopeEstoqueBaixo(Builder $query): Builder
    {
        $padrao = self::alertaMinimo();

        return $padrao === null
            ? $query->whereRaw('1 = 0')
            : $query->whereRaw('quantidade <= COALESCE(estoque_minimo, ?)', [$padrao]);
    }

    /**
     * Limite de estoque baixo que vale para este produto (o dele ou o
     * padrão), ou null com o alerta desligado.
     */
    public function getLimiteEstoqueBaixoAttribute(): ?int
    {
        $padrao = self::alertaMinimo();

        return $padrao === null ? null : ($this->estoque_minimo ?? $padrao);
    }

    public function getEstoqueBaixoAttribute(): bool
    {
        $limite = $this->limite_estoque_baixo;

        return $limite !== null && $this->quantidade <= $limite;
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
        return $this->only(['id', 'nome', 'descricao', 'quantidade', 'estoque_minimo', 'categoria_id'])
            + ['preco' => (float) $this->preco]
            + $this->dadosDeImagemDoFormulario();
    }
}
