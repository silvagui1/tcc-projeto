<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarProdutoRequest;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;

class ProdutoController extends Controller
{
    /**
     * Cadastra um novo produto (pop-up "Adicionar produto").
     */
    public function store(SalvarProdutoRequest $request): RedirectResponse
    {
        $produto = Produto::create($request->dadosComImagem('produtos'));

        // Quantidade inicial entra como uma "entrada" a partir de zero, para o
        // histórico já nascer explicando de onde veio o estoque.
        if ($produto->quantidade > 0) {
            $this->registrarMovimentacao($produto, 0, $produto->quantidade, 'Cadastro do produto');
        }

        return back()->with('estoqueMensagem', "\"{$produto->nome}\" foi adicionado ao estoque.");
    }

    /**
     * Atualiza um produto existente (pop-up de edição).
     */
    public function update(SalvarProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $quantidadeAnterior = $produto->quantidade;

        $produto->update($request->dadosComImagem('produtos', $produto));

        if ($produto->quantidade !== $quantidadeAnterior) {
            $this->registrarMovimentacao($produto, $quantidadeAnterior, $produto->quantidade, 'Edição do produto');
        }

        return back()->with('estoqueMensagem', "\"{$produto->nome}\" foi atualizado.");
    }

    /**
     * Remove definitivamente um produto e o arquivo da imagem, se houver (o
     * histórico de movimentações vai junto, por causa do cascadeOnDelete).
     */
    public function destroy(Produto $produto): RedirectResponse
    {
        $nome = $produto->nome;

        $produto->apagarArquivoDeImagem();
        $produto->delete();

        return back()->with('estoqueMensagem', "\"{$nome}\" foi removido do estoque.");
    }

    /**
     * Registra no histórico uma mudança de quantidade do produto.
     */
    private function registrarMovimentacao(Produto $produto, int $anterior, int $nova, string $descricao): void
    {
        $produto->movimentacoes()->create([
            'tipo' => $nova > $anterior ? 'entrada' : 'saida',
            'quantidade' => abs($nova - $anterior),
            'quantidade_anterior' => $anterior,
            'quantidade_nova' => $nova,
            'descricao' => $descricao,
        ]);
    }
}
