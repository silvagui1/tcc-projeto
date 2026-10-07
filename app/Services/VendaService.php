<?php

namespace App\Services;

use App\Models\Aluguel;
use App\Models\Carta;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registra e cancela vendas. Fica fora do controller porque mexe em quatro
 * lugares de uma vez (venda, estoque de produtos/cartas, aluguéis e créditos
 * do cliente) e precisa fazer tudo numa transação só — se qualquer parte
 * falhar (ex.: estoque acabou), nada é gravado.
 *
 * Preço, nome e estoque sempre vêm do banco, nunca do navegador.
 */
class VendaService
{
    /**
     * @param array{
     *     cliente_id?: int|null,
     *     itens: array<int, array{tipo: string, id: int, quantidade?: int|null}>,
     *     usar_creditos?: bool,
     *     forma_pagamento?: string|null,
     *     observacoes?: string|null,
     * } $dados
     */
    public function registrar(array $dados): Venda
    {
        return DB::transaction(function () use ($dados) {
            $cliente = ! empty($dados['cliente_id'])
                ? Cliente::query()->whereKey($dados['cliente_id'])->lockForUpdate()->firstOrFail()
                : null;

            $venda = Venda::create([
                'cliente_id' => $cliente?->id,
                'observacoes' => trim((string) ($dados['observacoes'] ?? '')) ?: null,
            ]);

            $total = 0.0;

            foreach ($this->agruparItens($dados['itens']) as $item) {
                $total += match ($item['tipo']) {
                    'produto' => $this->venderProduto($venda, $item['id'], $item['quantidade']),
                    'carta' => $this->venderCarta($venda, $item['id'], $item['quantidade']),
                    'aluguel' => $this->cobrarAluguel($venda, $item['id']),
                };
            }

            $total = round($total, 2);
            $valorCreditos = 0.0;

            if ($cliente && ! empty($dados['usar_creditos'])) {
                $saldoAnterior = (float) $cliente->creditos;
                $valorCreditos = round(min($saldoAnterior, $total), 2);

                if ($valorCreditos > 0) {
                    $saldoNovo = round($saldoAnterior - $valorCreditos, 2);
                    $cliente->update(['creditos' => $saldoNovo]);
                    $cliente->historicoCreditos()->create([
                        'tipo' => 'descontar',
                        'valor' => $valorCreditos,
                        'saldo_anterior' => $saldoAnterior,
                        'saldo_novo' => $saldoNovo,
                        'motivo' => "Venda #{$venda->id}",
                    ]);
                }
            }

            $restante = round($total - $valorCreditos, 2);
            $forma = $dados['forma_pagamento'] ?? null;

            if ($restante > 0 && ! $forma) {
                throw ValidationException::withMessages([
                    'forma_pagamento' => 'Escolha a forma de pagamento'.($valorCreditos > 0 ? ' do restante.' : '.'),
                ]);
            }

            $venda->update([
                'total' => $total,
                'valor_creditos' => $valorCreditos,
                'valor_restante' => $restante,
                // Se os créditos pagaram tudo, não houve outra forma de pagamento.
                'forma_pagamento' => $restante > 0 ? $forma : null,
            ]);

            return $venda->load('itens', 'cliente');
        });
    }

    /**
     * Cancela a venda devolvendo o que ela tirou: estoque dos produtos e
     * cartas, aluguéis voltam a "agendado" e os créditos usados voltam para
     * o cliente. A venda continua no histórico, marcada como cancelada.
     */
    public function cancelar(Venda $venda): Venda
    {
        return DB::transaction(function () use ($venda) {
            $venda = Venda::query()->whereKey($venda->id)->lockForUpdate()->firstOrFail();

            if ($venda->cancelada) {
                throw ValidationException::withMessages(['venda' => 'Essa venda já foi cancelada.']);
            }

            $descricao = "Cancelamento da venda #{$venda->id}";

            foreach ($venda->itens as $item) {
                if ($item->tipo === 'produto' && $item->produto_id) {
                    $produto = Produto::query()->whereKey($item->produto_id)->lockForUpdate()->first();
                    $produto && $this->movimentarEstoque($produto, $item->quantidade, $descricao);
                } elseif ($item->tipo === 'carta' && $item->carta_id) {
                    $carta = Carta::query()->whereKey($item->carta_id)->lockForUpdate()->first();
                    $carta && $this->movimentarEstoque($carta, $item->quantidade, $descricao);
                } elseif ($item->tipo === 'aluguel' && $item->aluguel_id) {
                    Aluguel::whereKey($item->aluguel_id)->where('status', 'pago')->update(['status' => 'agendado']);
                }
            }

            $valorCreditos = (float) $venda->valor_creditos;

            if ($valorCreditos > 0 && $venda->cliente_id) {
                $cliente = Cliente::query()->whereKey($venda->cliente_id)->lockForUpdate()->first();

                if ($cliente) {
                    $saldoAnterior = (float) $cliente->creditos;
                    $saldoNovo = round($saldoAnterior + $valorCreditos, 2);
                    $cliente->update(['creditos' => $saldoNovo]);
                    $cliente->historicoCreditos()->create([
                        'tipo' => 'adicionar',
                        'valor' => $valorCreditos,
                        'saldo_anterior' => $saldoAnterior,
                        'saldo_novo' => $saldoNovo,
                        'motivo' => "Estorno da venda #{$venda->id}",
                    ]);
                }
            }

            $venda->update(['status' => 'cancelada', 'cancelada_em' => now()]);

            return $venda->load('itens', 'cliente');
        });
    }

    /**
     * Junta linhas repetidas do mesmo item (ex.: o mesmo produto adicionado
     * duas vezes) somando as quantidades. Aluguel sempre conta como 1.
     *
     * @return array<int, array{tipo: string, id: int, quantidade: int}>
     */
    private function agruparItens(array $itens): array
    {
        $agrupados = [];

        foreach ($itens as $item) {
            $chave = $item['tipo'].':'.$item['id'];
            $quantidade = $item['tipo'] === 'aluguel' ? 1 : max(1, (int) ($item['quantidade'] ?? 1));

            if (isset($agrupados[$chave])) {
                if ($item['tipo'] !== 'aluguel') {
                    $agrupados[$chave]['quantidade'] += $quantidade;
                }
            } else {
                $agrupados[$chave] = ['tipo' => $item['tipo'], 'id' => (int) $item['id'], 'quantidade' => $quantidade];
            }
        }

        return array_values($agrupados);
    }

    private function venderProduto(Venda $venda, int $id, int $quantidade): float
    {
        $produto = Produto::query()->whereKey($id)->lockForUpdate()->first();

        if (! $produto) {
            throw ValidationException::withMessages(['itens' => 'Um dos produtos não existe mais no estoque.']);
        }

        $this->garantirEstoque($produto->nome, $produto->quantidade, $quantidade);
        $this->movimentarEstoque($produto, -$quantidade, "Venda #{$venda->id}");

        return $this->criarItem($venda, 'produto', ['produto_id' => $produto->id], $produto->nome, $quantidade, (float) $produto->preco);
    }

    private function venderCarta(Venda $venda, int $id, int $quantidade): float
    {
        $carta = Carta::query()->whereKey($id)->lockForUpdate()->first();

        if (! $carta) {
            throw ValidationException::withMessages(['itens' => 'Uma das cartas não existe mais no estoque.']);
        }

        $this->garantirEstoque($carta->nome, $carta->quantidade, $quantidade);
        $this->movimentarEstoque($carta, -$quantidade, "Venda #{$venda->id}");

        return $this->criarItem($venda, 'carta', ['carta_id' => $carta->id], $carta->nome, $quantidade, (float) $carta->preco);
    }

    private function cobrarAluguel(Venda $venda, int $id): float
    {
        $aluguel = Aluguel::query()->with('mesa')->whereKey($id)->lockForUpdate()->first();

        if (! $aluguel || $aluguel->status !== 'agendado') {
            throw ValidationException::withMessages([
                'itens' => 'Um dos aluguéis já foi pago ou cancelado. Atualize a página e tente de novo.',
            ]);
        }

        $aluguel->update(['status' => 'pago']);

        return $this->criarItem($venda, 'aluguel', ['aluguel_id' => $aluguel->id], $aluguel->descricao_venda, 1, (float) $aluguel->valor);
    }

    private function garantirEstoque(string $nome, int $disponivel, int $pedido): void
    {
        if ($pedido > $disponivel) {
            throw ValidationException::withMessages([
                'itens' => $disponivel === 0
                    ? "\"{$nome}\" está sem estoque."
                    : "Estoque insuficiente de \"{$nome}\": restam {$disponivel}.",
            ]);
        }
    }

    /**
     * Soma (positivo) ou tira (negativo) do estoque de um produto/carta e
     * registra o lançamento no histórico de movimentações do item.
     */
    private function movimentarEstoque(Produto|Carta $item, int $diferenca, string $descricao): void
    {
        $anterior = $item->quantidade;
        $nova = $anterior + $diferenca;

        $item->update(['quantidade' => $nova]);
        $item->movimentacoes()->create([
            'tipo' => $diferenca > 0 ? 'entrada' : 'saida',
            'quantidade' => abs($diferenca),
            'quantidade_anterior' => $anterior,
            'quantidade_nova' => $nova,
            'descricao' => $descricao,
        ]);
    }

    private function criarItem(Venda $venda, string $tipo, array $referencia, string $descricao, int $quantidade, float $preco): float
    {
        $subtotal = round($preco * $quantidade, 2);

        $venda->itens()->create($referencia + [
            'tipo' => $tipo,
            'descricao' => mb_substr($descricao, 0, 190),
            'quantidade' => $quantidade,
            'preco_unitario' => $preco,
            'subtotal' => $subtotal,
        ]);

        return $subtotal;
    }
}
