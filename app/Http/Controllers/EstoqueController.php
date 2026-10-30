<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    /**
     * 4 fileiras de 4 no desktop.
     */
    private const PRODUTOS_POR_PAGINA = 16;

    /**
     * Opções de ordenação da grade de produtos (valor da url => rótulo).
     */
    public const ORDENACOES = [
        'recentes' => 'Últimos adicionados',
        'antigos' => 'Primeiros adicionados',
        'nome' => 'Nome (A–Z)',
        'menor_qtd' => 'Menor quantidade',
        'maior_qtd' => 'Maior quantidade',
        'menor_preco' => 'Menor preço',
        'maior_preco' => 'Maior preço',
    ];

    /**
     * Aba "Produtos" do estoque: grade com busca, categoria, situação
     * (todos / estoque baixo / arquivados), ordenação e paginação. O
     * cabeçalho (resumo, alerta e abas) vem do view composer de
     * pages.estoque.partials.cabecalho. A aba "Cartas" é o catálogo
     * (CartaController::index).
     */
    public function index(Request $request): View|RedirectResponse
    {
        // links antigos da aba de cartas (?tab=cartas) vão para o catálogo
        if ($request->query('tab') === 'cartas') {
            return redirect()->route('estoque.cartas');
        }

        $filtros = [
            'busca' => trim((string) $request->query('busca', '')),
            'categoria' => (int) $request->query('categoria', 0),
            'ordenar' => array_key_exists($request->query('ordenar'), self::ORDENACOES) ? $request->query('ordenar') : 'recentes',
            'situacao' => in_array($request->query('situacao'), ['baixo', 'arquivados'], true) ? $request->query('situacao') : 'todos',
        ];

        $produtos = Produto::with('categoria')
            ->when($filtros['situacao'] === 'arquivados', fn (Builder $q) => $q->onlyTrashed())
            ->when($filtros['situacao'] === 'baixo', fn (Builder $q) => $q->estoqueBaixo())
            ->when($filtros['busca'] !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$filtros['busca']}%"))
            ->when($filtros['categoria'], fn (Builder $q) => $q->where('categoria_id', $filtros['categoria']));

        match ($filtros['ordenar']) {
            'antigos' => $produtos->orderBy('id'),
            'nome' => $produtos->orderBy('nome'),
            'menor_qtd' => $produtos->orderBy('quantidade')->orderBy('nome'),
            'maior_qtd' => $produtos->orderByDesc('quantidade')->orderBy('nome'),
            'menor_preco' => $produtos->orderBy('preco')->orderBy('nome'),
            'maior_preco' => $produtos->orderByDesc('preco')->orderBy('nome'),
            default => $produtos->orderByDesc('id'),
        };

        return view('pages.estoque.index', [
            'filtros' => $filtros,
            'categorias' => Categoria::orderBy('nome')->get(),
            // a paginação mantém busca, filtros e ordenação na url
            'produtos' => $produtos->paginate(self::PRODUTOS_POR_PAGINA)->withQueryString(),
            // contadores dos chips de situação
            'quantidadeEstoqueBaixo' => Produto::estoqueBaixo()->count(),
            'arquivados' => Produto::onlyTrashed()->count(),
        ]);
    }
}
