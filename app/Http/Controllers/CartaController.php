<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AcoesDoEstoque;
use App\Http\Requests\AjustarEstoqueRequest;
use App\Http\Requests\SalvarCartaRequest;
use App\Models\Atividade;
use App\Models\Carta;
use App\Models\JogoCarta;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartaController extends Controller
{
    use AcoesDoEstoque;

    /**
     * 3 fileiras no desktop: 3 cartas ao lado dos filtros + 4 + 4.
     */
    private const CARTAS_POR_PAGINA = 11;

    /**
     * Quantos lançamentos do histórico aparecem na página de detalhes.
     */
    private const MOVIMENTACOES_NOS_DETALHES = 15;

    /**
     * Catálogo de cartas de um jogo (aba "Cartas" do estoque), com busca por
     * nome e os filtros laterais (estado, raridade, idioma, foil, faixa de
     * preço, ordenação e as arquivadas).
     */
    public function index(Request $request): View
    {
        $jogoAtual = in_array($request->query('jogo'), $jogos = JogoCarta::chaves(), true) ? $request->query('jogo') : ($jogos[0] ?? '');

        // filtros escolhidos (vêm na url, ex.: ?estado[]=Novo&foil=1&preco_max=10)
        $filtros = [
            'busca' => trim((string) $request->query('busca', '')),
            'estado' => array_values(array_filter((array) $request->query('estado', []), 'is_string')),
            'raridade' => array_values(array_filter((array) $request->query('raridade', []), 'is_string')),
            'idioma' => array_values(array_filter((array) $request->query('idioma', []), 'is_string')),
            'foil' => $request->boolean('foil'),
            'preco_min' => is_numeric($request->query('preco_min')) ? (float) $request->query('preco_min') : null,
            'preco_max' => is_numeric($request->query('preco_max')) ? (float) $request->query('preco_max') : null,
            'ordenar' => $request->query('ordenar', 'recentes'),
            'arquivadas' => $request->boolean('arquivadas'),
        ];

        $cartas = Carta::where('jogo', $jogoAtual)
            ->when($filtros['arquivadas'], fn (Builder $q) => $q->onlyTrashed())
            ->when($filtros['busca'] !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$filtros['busca']}%"))
            ->when($filtros['estado'], fn (Builder $q) => $q->whereIn('estado', $filtros['estado']))
            ->when($filtros['raridade'], fn (Builder $q) => $q->whereIn('raridade', $filtros['raridade']))
            ->when($filtros['idioma'], fn (Builder $q) => $q->whereIn('idioma', $filtros['idioma']))
            ->when($filtros['foil'], fn (Builder $q) => $q->where('foil', true))
            ->when($filtros['preco_min'] !== null, fn (Builder $q) => $q->where('preco', '>=', $filtros['preco_min']))
            ->when($filtros['preco_max'] !== null, fn (Builder $q) => $q->where('preco', '<=', $filtros['preco_max']));

        match ($filtros['ordenar']) {
            'menor_preco' => $cartas->orderBy('preco'),
            'maior_preco' => $cartas->orderByDesc('preco'),
            'nome' => $cartas->orderBy('nome'),
            'menor_qtd' => $cartas->orderBy('quantidade')->orderBy('nome'),
            'maior_qtd' => $cartas->orderByDesc('quantidade')->orderBy('nome'),
            default => $cartas->latest('id'),
        };

        // opções dos filtros laterais: só os valores que existem nas cartas do jogo
        $opcoes = [];
        foreach (['estado', 'raridade', 'idioma'] as $campo) {
            $opcoes[$campo] = Carta::where('jogo', $jogoAtual)
                ->whereNotNull($campo)
                ->distinct()
                ->orderBy($campo)
                ->pluck($campo)
                ->all();
        }

        $filtrosAtivos = count($filtros['estado']) + count($filtros['raridade']) + count($filtros['idioma'])
            + ($filtros['foil'] ? 1 : 0) + ($filtros['preco_min'] !== null ? 1 : 0) + ($filtros['preco_max'] !== null ? 1 : 0)
            + ($filtros['busca'] !== '' ? 1 : 0) + ($filtros['arquivadas'] ? 1 : 0);

        return view('pages.estoque.cartas', [
            'jogosDisponiveis' => JogoCarta::opcoes(),
            'jogoAtual' => $jogoAtual,
            // a paginação mantém jogo, busca e filtros na url
            'cartas' => $cartas->paginate(self::CARTAS_POR_PAGINA)->withQueryString(),
            'opcoes' => $opcoes,
            'filtros' => $filtros,
            'filtrosAtivos' => $filtrosAtivos,
        ]);
    }

    /**
     * Página de detalhes de uma carta, com o ajuste rápido de quantidade e
     * as últimas movimentações. Abre também para cartas arquivadas.
     */
    public function show(Carta $carta): View
    {
        return view('pages.estoque.carta', [
            'carta' => $carta,
            'jogosDisponiveis' => JogoCarta::opcoes(),
            'movimentacoes' => $carta->movimentacoes()->with('venda')->take(self::MOVIMENTACOES_NOS_DETALHES)->get(),
            'totalMovimentacoes' => $carta->movimentacoes()->count(),
        ]);
    }

    /**
     * Cadastra uma nova carta (pop-up "Adicionar carta").
     */
    public function store(SalvarCartaRequest $request): RedirectResponse
    {
        $carta = Carta::create($request->dadosComImagem('cartas'));

        // Quantidade inicial entra como uma "entrada" a partir de zero (ver
        // ProdutoController::store).
        $carta->registrarMovimentacao(0, $carta->quantidade, 'Cadastro da carta');

        Atividade::registrar('estoque', "Carta cadastrada: {$carta->nome} (qtd {$carta->quantidade})");

        return back()->with('aviso', ['texto' => "\"{$carta->nome}\" foi adicionada ao estoque.", 'tipo' => 'sucesso']);
    }

    /**
     * Atualiza uma carta existente (pop-up de edição).
     */
    public function update(SalvarCartaRequest $request, Carta $carta): RedirectResponse
    {
        $quantidadeAnterior = $carta->quantidade;

        $carta->update($request->dadosComImagem('cartas', $carta));
        $carta->registrarMovimentacao($quantidadeAnterior, $carta->quantidade, 'Edição da carta');

        Atividade::registrar('estoque', "Carta editada: {$carta->nome}"
            .($carta->quantidade !== $quantidadeAnterior ? " (qtd {$quantidadeAnterior} → {$carta->quantidade})" : ''));

        return back()->with('aviso', ['texto' => "\"{$carta->nome}\" foi atualizada.", 'tipo' => 'sucesso']);
    }

    /**
     * "Apagar" uma carta arquiva (ver AcoesDoEstoque).
     */
    public function destroy(Carta $carta): RedirectResponse
    {
        return $this->arquivarItem($carta);
    }

    public function restaurar(Carta $carta): RedirectResponse
    {
        return $this->restaurarItem($carta);
    }

    public function excluir(Carta $carta): RedirectResponse
    {
        return $this->excluirItem($carta);
    }

    public function ajustar(AjustarEstoqueRequest $request, Carta $carta): RedirectResponse
    {
        return $this->ajustarItem($request, $carta);
    }

    protected function rotulo(): array
    {
        return ['nome' => 'carta', 'o' => 'a', 'rota' => 'estoque.cartas'];
    }

    protected function urlDaLista(Produto|Carta $item): string
    {
        return route('estoque.cartas', ['jogo' => $item->jogo, 'arquivadas' => 1]);
    }
}
