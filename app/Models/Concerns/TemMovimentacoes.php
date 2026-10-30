<?php

namespace App\Models\Concerns;

/**
 * Histórico de quantidade de produtos e cartas (produto_movimentacoes /
 * carta_movimentacoes). Toda mudança de quantidade — cadastro, edição,
 * ajuste rápido, venda e cancelamento — passa por aqui, para o histórico
 * nunca ficar incompleto.
 */
trait TemMovimentacoes
{
    /**
     * Registra no histórico que a quantidade foi de $anterior para $nova
     * (não altera o item). Sem mudança, não registra nada.
     */
    public function registrarMovimentacao(int $anterior, int $nova, string $descricao, ?int $vendaId = null): void
    {
        if ($anterior === $nova) {
            return;
        }

        $this->movimentacoes()->create([
            'tipo' => $nova > $anterior ? 'entrada' : 'saida',
            'quantidade' => abs($nova - $anterior),
            'quantidade_anterior' => $anterior,
            'quantidade_nova' => $nova,
            'descricao' => $descricao,
            'venda_id' => $vendaId,
        ]);
    }

    /**
     * Soma (positivo) ou tira (negativo) do estoque e registra no histórico.
     */
    public function movimentarQuantidade(int $diferenca, string $descricao, ?int $vendaId = null): void
    {
        $anterior = $this->quantidade;

        $this->update(['quantidade' => $anterior + $diferenca]);
        $this->registrarMovimentacao($anterior, $this->quantidade, $descricao, $vendaId);
    }
}
