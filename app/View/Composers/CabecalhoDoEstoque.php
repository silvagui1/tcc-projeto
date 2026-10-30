<?php

namespace App\View\Composers;

use App\Models\Carta;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dados do cabeçalho do estoque (pages.estoque.partials.cabecalho), igual
 * nas abas Produtos e Cartas: números do estoque inteiro (sem filtros e sem
 * os arquivados) e o alerta de estoque baixo.
 */
class CabecalhoDoEstoque
{
    public function compose(View $view): void
    {
        $view->with([
            'resumo' => [
                'valorEstoque' => (float) Produto::sum(DB::raw('preco * quantidade')),
                'quantidadeProdutos' => (int) Produto::sum('quantidade'),
                'cartasAvulsas' => (int) Carta::sum('quantidade'),
                'valorCartasAvulsas' => (float) Carta::sum(DB::raw('preco * quantidade')),
            ],
            'estoqueBaixo' => Produto::estoqueBaixo()->orderBy('quantidade')->get(),
            'alertaMinimo' => Produto::alertaMinimo(),
        ]);
    }
}
