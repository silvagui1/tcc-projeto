<?php

namespace App\Http\Controllers;

use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    /**
     * Tela principal do estoque: cards de resumo (sobre o estoque inteiro,
     * sem filtros) e uma das abas — produtos (com busca, categoria e
     * ordenação) ou a prévia de cartas avulsas.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'cartas' ? 'cartas' : 'produtos';

        $filtros = [
            'busca' => trim((string) $request->query('busca', '')),
            'categoria' => (int) $request->query('categoria', 0),
            'ordenar' => $request->query('ordenar') === 'antigos' ? 'antigos' : 'recentes',
        ];

        $produtos = Produto::with('categoria')
            ->when($filtros['busca'] !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$filtros['busca']}%"))
            ->when($filtros['categoria'], fn (Builder $q) => $q->where('categoria_id', $filtros['categoria']))
            ->orderBy('id', $filtros['ordenar'] === 'antigos' ? 'asc' : 'desc')
            ->get();

        // prévia da aba "estoque cartas": as últimas cartas cadastradas
        $cartasDestaque = Carta::latest('id')->take(4)->get();

        return view('pages.estoque.index', [
            'tab' => $tab,
            'filtros' => $filtros,
            'resumo' => $this->resumo(),
            'categorias' => Categoria::orderBy('nome')->get(),
            'produtos' => $produtos,
            'cartasDestaque' => $cartasDestaque,
            'jogosDisponiveis' => Carta::JOGOS,
        ]);
    }

    /**
     * Números dos cards no topo da tela — panorama do estoque inteiro.
     *
     * @return array{valorEstoque: float, quantidadeProdutos: int, cartasAvulsas: int, valorCartasAvulsas: float}
     */
    private function resumo(): array
    {
        return [
            'valorEstoque' => (float) Produto::sum(DB::raw('preco * quantidade')),
            'quantidadeProdutos' => (int) Produto::sum('quantidade'),
            'cartasAvulsas' => (int) Carta::sum('quantidade'),
            'valorCartasAvulsas' => (float) Carta::sum(DB::raw('preco * quantidade')),
        ];
    }
}
