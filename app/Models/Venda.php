<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    /**
     * Formas de pagamento aceitas, com rótulo e ícone (Bootstrap Icons).
     */
    public const FORMAS_PAGAMENTO = [
        'dinheiro' => ['rotulo' => 'Dinheiro', 'icone' => 'bi-cash-coin'],
        'pix' => ['rotulo' => 'Pix', 'icone' => 'bi-qr-code'],
        'debito' => ['rotulo' => 'Débito', 'icone' => 'bi-credit-card-2-front'],
        'credito' => ['rotulo' => 'Crédito', 'icone' => 'bi-credit-card'],
    ];

    protected $fillable = [
        'cliente_id',
        'total',
        'valor_creditos',
        'valor_restante',
        'forma_pagamento',
        'observacoes',
        'status',
        'cancelada_em',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'valor_creditos' => 'decimal:2',
        'valor_restante' => 'decimal:2',
        'cancelada_em' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function itens()
    {
        return $this->hasMany(VendaItem::class);
    }

    public function scopeConcluidas(Builder $query): Builder
    {
        return $query->where('status', 'concluida');
    }

    /**
     * Total vendido e número de vendas concluídas num dia — usado no resumo
     * da tela de vendas e nos cartões da página inicial.
     *
     * @return array{total: float, quantidade: int}
     */
    public static function resumoDoDia(CarbonInterface $dia): array
    {
        $linha = self::concluidas()
            ->whereBetween('created_at', [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()])
            ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as quantidade')
            ->first();

        return ['total' => (float) $linha->total, 'quantidade' => (int) $linha->quantidade];
    }

    public function getCanceladaAttribute(): bool
    {
        return $this->status === 'cancelada';
    }

    /**
     * Resumo curto dos itens para a linha da lista — ex.: "2× Coca-Cola,
     * Booster Pokémon +1". Usa os itens já carregados (with('itens')).
     */
    public function getResumoItensAttribute(): string
    {
        $itens = $this->itens;
        $texto = $itens->take(2)
            ->map(fn (VendaItem $item) => ($item->quantidade > 1 ? "{$item->quantidade}× " : '').$item->descricao)
            ->implode(', ');

        return $itens->count() > 2 ? $texto.' +'.($itens->count() - 2) : $texto;
    }

    /**
     * Tipos de item presentes na venda (para os ícones da linha).
     *
     * @return array<int, string>
     */
    public function getTiposItensAttribute(): array
    {
        return $this->itens->pluck('tipo')->unique()->values()->all();
    }

    /**
     * Rótulo de como a venda foi paga — "Pix", "Créditos", "Créditos + Pix".
     */
    public function getPagamentoRotuloAttribute(): string
    {
        $forma = self::FORMAS_PAGAMENTO[$this->forma_pagamento]['rotulo'] ?? null;

        if ((float) $this->valor_creditos > 0) {
            return $forma ? "Créditos + {$forma}" : 'Créditos';
        }

        return $forma ?? '—';
    }

    public function getPagamentoIconeAttribute(): string
    {
        if ((float) $this->valor_creditos > 0 && ! $this->forma_pagamento) {
            return 'bi-wallet2';
        }

        return self::FORMAS_PAGAMENTO[$this->forma_pagamento]['icone'] ?? 'bi-wallet2';
    }

    /**
     * Formato usado pelo modal de detalhes da venda.
     *
     * @return array<string, mixed>
     */
    public function dadosJson(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->created_at->format('d/m/Y \à\s H:i'),
            'status' => $this->status,
            'cancelada_em' => $this->cancelada_em?->format('d/m/Y \à\s H:i'),
            'total' => (float) $this->total,
            'valor_creditos' => (float) $this->valor_creditos,
            'valor_restante' => (float) $this->valor_restante,
            'forma_pagamento' => $this->forma_pagamento,
            'forma_pagamento_rotulo' => self::FORMAS_PAGAMENTO[$this->forma_pagamento]['rotulo'] ?? null,
            'pagamento_rotulo' => $this->pagamento_rotulo,
            'observacoes' => $this->observacoes,
            'cliente' => $this->cliente?->dadosResumidos(),
            'itens' => $this->itens->map(fn (VendaItem $item) => [
                'tipo' => $item->tipo,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'subtotal' => (float) $item->subtotal,
            ])->all(),
        ];
    }
}
