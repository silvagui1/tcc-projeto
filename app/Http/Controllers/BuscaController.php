<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
use App\Models\Carta;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Busca geral (Ctrl+K, ver partials/busca-geral e resources/js/busca-geral.js):
 * um campo só para achar cliente, produto, carta, venda ou campeonato e ir
 * direto para ele.
 */
class BuscaController extends Controller
{
    private const POR_GRUPO = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        if (mb_strlen($termo) < 2 && ! preg_match('/^#?\d+$/', $termo)) {
            return response()->json(['grupos' => []]);
        }

        $like = '%'.addcslashes($termo, '%_\\').'%';
        $grupos = [];

        $clientes = Cliente::cadastrados()->where('nome', 'like', $like)->orderBy('nome')->take(self::POR_GRUPO)->get();
        $grupos[] = ['titulo' => 'Clientes', 'itens' => $clientes->map(fn (Cliente $c) => [
            'titulo' => $c->nome,
            'subtitulo' => 'Créditos: R$ '.number_format((float) $c->creditos, 2, ',', '.'),
            'url' => route('clientes.index', ['abrir' => $c->id]),
            'icone' => 'bi-person',
        ])];

        $produtos = Produto::with('categoria')->where('nome', 'like', $like)->orderBy('nome')->take(self::POR_GRUPO)->get();
        $grupos[] = ['titulo' => 'Produtos', 'itens' => $produtos->map(fn (Produto $p) => [
            'titulo' => $p->nome,
            'subtitulo' => "{$p->quantidade} em estoque · ".mb_strtolower($p->categoria?->nome ?? ''),
            'url' => route('estoque.produtos.show', $p),
            'icone' => 'bi-box-seam',
        ])];

        $cartas = Carta::where('nome', 'like', $like)->orderBy('nome')->take(self::POR_GRUPO)->get();
        $grupos[] = ['titulo' => 'Cartas avulsas', 'itens' => $cartas->map(fn (Carta $c) => [
            'titulo' => $c->nome,
            'subtitulo' => "{$c->quantidade} em estoque · R$ ".number_format((float) $c->preco, 2, ',', '.'),
            'url' => route('estoque.cartas.show', $c),
            'icone' => 'bi-stack',
        ])];

        // vendas: pelo número (#12 ou 12) ou pelo nome do cliente
        $numero = preg_match('/^#?(\d+)$/', $termo, $m) ? (int) $m[1] : null;
        $vendas = Venda::with('cliente')
            ->where(fn ($q) => $q
                ->when($numero, fn ($q) => $q->whereKey($numero))
                ->orWhereHas('cliente', fn ($q) => $q->where('nome', 'like', $like)))
            ->latest('id')
            ->take(self::POR_GRUPO)
            ->get();
        $grupos[] = ['titulo' => 'Vendas', 'itens' => $vendas->map(fn (Venda $v) => [
            'titulo' => "Venda #{$v->id} · R$ ".number_format((float) $v->total, 2, ',', '.'),
            'subtitulo' => $v->created_at->format('d/m/Y H:i').' · '.($v->cliente?->nome ?? 'sem cliente').($v->cancelada ? ' · cancelada' : ''),
            'url' => route('vendas.index', ['venda' => $v->id]),
            'icone' => 'bi-receipt',
        ])];

        $campeonatos = Campeonato::where('nome', 'like', $like)->latest('data')->take(self::POR_GRUPO)->get();
        $grupos[] = ['titulo' => 'Campeonatos', 'itens' => $campeonatos->map(fn (Campeonato $c) => [
            'titulo' => $c->nome,
            'subtitulo' => $c->data?->format('d/m/Y') ?? '',
            'url' => route('campeonatos.index', ['abrir' => $c->id]),
            'icone' => 'bi-trophy',
        ])];

        return response()->json([
            'grupos' => array_values(array_filter($grupos, fn ($g) => $g['itens']->isNotEmpty())),
        ]);
    }
}
