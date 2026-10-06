<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarCartaRequest;
use App\Models\Carta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartaController extends Controller
{
    /**
     * Catálogo de cartas de um jogo, com busca por nome e os filtros
     * laterais (estado, raridade, idioma, foil, faixa de preço, ordenação).
     */
    public function index(Request $request): View
    {
        $jogoAtual = in_array($request->query('jogo'), Carta::JOGOS, true) ? $request->query('jogo') : 'pokemon';

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
        ];

        $cartas = Carta::where('jogo', $jogoAtual)
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
            + ($filtros['busca'] !== '' ? 1 : 0);

        return view('pages.estoque.cartas', [
            'jogosDisponiveis' => Carta::JOGOS,
            'jogoAtual' => $jogoAtual,
            'cartas' => $cartas->get(),
            'opcoes' => $opcoes,
            'filtros' => $filtros,
            'filtrosAtivos' => $filtrosAtivos,
        ]);
    }

    /**
     * Página de detalhes de uma carta.
     */
    public function show(Carta $carta): View
    {
        return view('pages.estoque.carta', [
            'carta' => $carta,
            'jogosDisponiveis' => Carta::JOGOS,
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
        if ($carta->quantidade > 0) {
            $this->registrarMovimentacao($carta, 0, $carta->quantidade, 'Cadastro da carta');
        }

        return back()->with('estoqueMensagem', "\"{$carta->nome}\" foi adicionada ao estoque.");
    }

    /**
     * Atualiza uma carta existente (pop-up de edição).
     */
    public function update(SalvarCartaRequest $request, Carta $carta): RedirectResponse
    {
        $quantidadeAnterior = $carta->quantidade;

        $carta->update($request->dadosComImagem('cartas', $carta));

        if ($carta->quantidade !== $quantidadeAnterior) {
            $this->registrarMovimentacao($carta, $quantidadeAnterior, $carta->quantidade, 'Edição da carta');
        }

        return back()->with('estoqueMensagem', "\"{$carta->nome}\" foi atualizada.");
    }

    /**
     * Remove definitivamente uma carta, o arquivo da imagem (se houver) e o
     * histórico (por causa do cascadeOnDelete). Se o pedido veio da própria página de detalhes — que
     * deixa de existir —, volta para o catálogo do jogo.
     */
    public function destroy(Carta $carta): RedirectResponse
    {
        $nome = $carta->nome;
        $veioDosDetalhes = url()->previous() === route('estoque.cartas.show', $carta);

        $carta->apagarArquivoDeImagem();
        $carta->delete();

        $resposta = $veioDosDetalhes
            ? redirect()->route('estoque.cartas', ['jogo' => $carta->jogo])
            : back();

        return $resposta->with('estoqueMensagem', "\"{$nome}\" foi removida do estoque.");
    }

    /**
     * Registra no histórico uma mudança de quantidade da carta.
     */
    private function registrarMovimentacao(Carta $carta, int $anterior, int $nova, string $descricao): void
    {
        $carta->movimentacoes()->create([
            'tipo' => $nova > $anterior ? 'entrada' : 'saida',
            'quantidade' => abs($nova - $anterior),
            'quantidade_anterior' => $anterior,
            'quantidade_nova' => $nova,
            'descricao' => $descricao,
        ]);
    }
}
