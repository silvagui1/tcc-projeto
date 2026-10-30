<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AcoesDoEstoque;
use App\Http\Requests\AjustarEstoqueRequest;
use App\Http\Requests\SalvarProdutoRequest;
use App\Models\Atividade;
use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    use AcoesDoEstoque;

    /**
     * Quantos lançamentos do histórico aparecem na página de detalhes.
     */
    private const MOVIMENTACOES_NOS_DETALHES = 15;

    /**
     * Página de detalhes de um produto (aberta ao clicar no card do
     * estoque), com o ajuste rápido de quantidade e as últimas
     * movimentações. Abre também para produtos arquivados.
     */
    public function show(Produto $produto): View
    {
        $produto->load('categoria');

        return view('pages.estoque.produto', [
            'produto' => $produto,
            'movimentacoes' => $produto->movimentacoes()->with('venda')->take(self::MOVIMENTACOES_NOS_DETALHES)->get(),
            'totalMovimentacoes' => $produto->movimentacoes()->count(),
            // o pop-up de edição (partials/produto-form) lista as categorias
            'categorias' => Categoria::orderBy('nome')->get(),
        ]);
    }

    /**
     * Cadastra um novo produto (pop-up "Adicionar produto").
     */
    public function store(SalvarProdutoRequest $request): RedirectResponse
    {
        $produto = Produto::create($request->dadosComImagem('produtos'));

        // Quantidade inicial entra como uma "entrada" a partir de zero, para o
        // histórico já nascer explicando de onde veio o estoque.
        $produto->registrarMovimentacao(0, $produto->quantidade, 'Cadastro do produto');

        Atividade::registrar('estoque', "Produto cadastrado: {$produto->nome} (qtd {$produto->quantidade})");

        return back()->with('aviso', ['texto' => "\"{$produto->nome}\" foi adicionado ao estoque.", 'tipo' => 'sucesso']);
    }

    /**
     * Atualiza um produto existente (pop-up de edição).
     */
    public function update(SalvarProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $quantidadeAnterior = $produto->quantidade;

        $produto->update($request->dadosComImagem('produtos', $produto));
        $produto->registrarMovimentacao($quantidadeAnterior, $produto->quantidade, 'Edição do produto');

        Atividade::registrar('estoque', "Produto editado: {$produto->nome}"
            .($produto->quantidade !== $quantidadeAnterior ? " (qtd {$quantidadeAnterior} → {$produto->quantidade})" : ''));

        return back()->with('aviso', ['texto' => "\"{$produto->nome}\" foi atualizado.", 'tipo' => 'sucesso']);
    }

    /**
     * "Apagar" um produto arquiva (ver AcoesDoEstoque).
     */
    public function destroy(Produto $produto): RedirectResponse
    {
        return $this->arquivarItem($produto);
    }

    public function restaurar(Produto $produto): RedirectResponse
    {
        return $this->restaurarItem($produto);
    }

    public function excluir(Produto $produto): RedirectResponse
    {
        return $this->excluirItem($produto);
    }

    public function ajustar(AjustarEstoqueRequest $request, Produto $produto): RedirectResponse
    {
        return $this->ajustarItem($request, $produto);
    }

    protected function rotulo(): array
    {
        return ['nome' => 'produto', 'o' => 'o', 'rota' => 'estoque.produtos'];
    }

    protected function urlDaLista(Produto|Carta $item): string
    {
        return route('estoque.index', ['situacao' => 'arquivados']);
    }
}
