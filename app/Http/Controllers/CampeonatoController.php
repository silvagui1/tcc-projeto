<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarCampeonatoRequest;
use App\Models\Campeonato;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Telas de campeonato (resources/views/pages/campeonatos): lista com filtros,
 * criar, acessar, editar, participantes e premiação. As views recebem os
 * campeonatos já no formato de Campeonato::dadosTela().
 */
class CampeonatoController extends Controller
{
    public function index(Request $request): View
    {
        // filtros da barra acima da lista (vêm na url, ex.: ?jogo=magic&status=finalizado)
        $filtros = [
            'busca' => trim((string) $request->query('busca', '')),
            'jogo' => in_array($request->query('jogo'), jogosDeCartas(), true) ? $request->query('jogo') : '',
            'status' => in_array($request->query('status'), ['ativo', 'finalizado'], true) ? $request->query('status') : '',
            'ordenar' => $request->query('ordenar') === 'antigos' ? 'antigos' : 'recentes',
        ];

        $todos = Campeonato::with($this->relacoes())
            ->when($filtros['busca'] !== '', fn ($q) => $q->where('nome', 'like', '%'.$filtros['busca'].'%'))
            ->when($filtros['jogo'] !== '', fn ($q) => $q->where('jogo', $filtros['jogo']))
            ->when($filtros['status'] !== '', fn ($q) => $q->where('status', $filtros['status']))
            ->orderBy('data', $filtros['ordenar'] === 'antigos' ? 'asc' : 'desc')
            ->get()
            ->map(fn (Campeonato $campeonato) => $campeonato->dadosTela());

        $filtrosAtivos = ($filtros['busca'] !== '') + ($filtros['jogo'] !== '') + ($filtros['status'] !== '');

        // vários ativos viram um carrossel na página principal
        $ativos = $todos->where('status', 'ativo')->values()->all();
        $outras = $todos->where('status', '!=', 'ativo')->values()->all();

        $jogosDisponiveis = jogosDeCartas();

        return view('pages.campeonatos.index', compact('ativos', 'outras', 'filtros', 'filtrosAtivos', 'jogosDisponiveis'));
    }

    public function create(): View
    {
        $clientesSugeridos = $this->clientesParaInscrever();

        return view('pages.campeonatos.criar', compact('clientesSugeridos'));
    }

    public function store(SalvarCampeonatoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $campeonato = Campeonato::create($request->dadosCampeonato());
            $campeonato->participantes()->sync($request->validated('participantes', []));
        });

        return redirect()->route('campeonatos.index')->with('success', 'Campeonato criado.');
    }

    public function show(Campeonato $campeonato): View
    {
        $campeonato = $this->carregar($campeonato)->dadosTela();

        return view('pages.campeonatos.show', compact('campeonato'));
    }

    public function edit(Campeonato $campeonato): View
    {
        $this->carregar($campeonato);

        // clientes que ainda não estão no campeonato, para a busca de "adicionar"
        $clientesSugeridos = $this->clientesParaInscrever($campeonato->participantes->modelKeys());
        $campeonato = $campeonato->dadosTela();

        return view('pages.campeonatos.editar', compact('campeonato', 'clientesSugeridos'));
    }

    public function update(SalvarCampeonatoRequest $request, Campeonato $campeonato): RedirectResponse
    {
        if ($resposta = $this->bloqueadoSeFinalizado($campeonato)) {
            return $resposta;
        }

        DB::transaction(function () use ($request, $campeonato) {
            $campeonato->update($request->dadosCampeonato());
            // os marcados na busca somam aos que já existem; para tirar alguém
            // usa-se a lixeira (campeonatos.participantes.destroy)
            $campeonato->participantes()->syncWithoutDetaching($request->validated('participantes', []));
        });

        return redirect()->route('campeonatos.index')->with('success', "{$campeonato->nome} atualizado.");
    }

    /**
     * Apaga o campeonato com inscrições e premiações. Os créditos já ganhos
     * continuam no saldo dos clientes (o histórico de créditos guarda o motivo).
     */
    public function destroy(Campeonato $campeonato): RedirectResponse
    {
        $nome = $campeonato->nome;
        $campeonato->delete();

        return redirect()->route('campeonatos.index')->with('success', "{$nome} apagado.");
    }

    /**
     * Tela de premiações: um bloco por colocação (1°, 2° e 3° lugar).
     * O envio vai para finalizar().
     */
    public function premiacoes(Campeonato $campeonato): View
    {
        $this->carregar($campeonato);

        $colocacoes = collect([1, 2, 3])->map(function (int $posicao) use ($campeonato) {
            $premio = $campeonato->premios->firstWhere('colocacao', $posicao);

            return [
                'posicao' => "{$posicao}° Lugar",
                'participante' => $premio?->cliente->nome ?? '',
                'valor' => (float) ($premio?->valor ?? 0),
                'descricao' => $premio?->descricao ?? '',
            ];
        })->all();

        $participantes = $campeonato->participantes->map(fn (Cliente $cliente) => $cliente->dadosParticipante())->all();
        $campeonatoId = $campeonato->id;
        $finalizado = $campeonato->finalizado();

        return view('pages.campeonatos.premiacoes', compact('colocacoes', 'participantes', 'campeonatoId', 'finalizado'));
    }

    /**
     * Salva as premiações e marca o campeonato como finalizado. O valor de
     * cada colocação vira crédito no saldo do cliente; salvar de novo estorna
     * os créditos da premiação anterior antes de lançar os novos.
     */
    public function finalizar(Request $request, Campeonato $campeonato): RedirectResponse
    {
        $dados = $request->validate([
            'premiacoes' => ['required', 'array', 'max:3'],
            'premiacoes.*.participante' => ['nullable', 'string', 'max:255'],
            'premiacoes.*.valor' => ['nullable', 'string', 'max:20'],
            'premiacoes.*.descricao' => ['nullable', 'string', 'max:250'],
        ], [
            'premiacoes.*.descricao.max' => 'A descrição do prêmio pode ter no máximo :max caracteres.',
        ]);

        // o campo de participante é uma busca por nome (datalist com os
        // participantes do campeonato), então o nome é convertido em cliente aqui
        $participantes = $campeonato->participantes()->get();

        $premiacoes = [];
        foreach (array_values($dados['premiacoes']) as $indice => $premiacao) {
            $nome = mb_strtolower(trim((string) ($premiacao['participante'] ?? '')));
            if ($nome === '') {
                continue;
            }

            $encontrados = $participantes->filter(fn (Cliente $cliente) => mb_strtolower($cliente->nome) === $nome);
            $campo = "premiacoes.$indice.participante";

            if ($encontrados->isEmpty()) {
                throw ValidationException::withMessages([$campo => ($indice + 1).'° lugar: escolha um participante deste campeonato.']);
            }
            if ($encontrados->count() > 1) {
                throw ValidationException::withMessages([$campo => ($indice + 1).'° lugar: há mais de um participante com esse nome.']);
            }

            $valor = (float) SalvarCampeonatoRequest::valorEmReais((string) ($premiacao['valor'] ?? ''));
            if ($valor < 0 || $valor > 99999.99) {
                throw ValidationException::withMessages(["premiacoes.$indice.valor" => ($indice + 1).'° lugar: informe um crédito válido.']);
            }

            $premiacoes[] = [
                'colocacao' => $indice + 1,
                'cliente' => $encontrados->first(),
                'valor' => round($valor, 2),
                'descricao' => $premiacao['descricao'] ?? null,
            ];
        }

        DB::transaction(function () use ($campeonato, $premiacoes) {
            foreach ($campeonato->premios()->with('cliente')->get() as $anterior) {
                if ((float) $anterior->valor > 0) {
                    $anterior->cliente->lancarCredito(-(float) $anterior->valor, "Estorno: {$anterior->colocacao}° lugar - {$campeonato->nome}");
                }
            }
            $campeonato->premios()->delete();
            DB::table('campeonato_cliente')->where('campeonato_id', $campeonato->id)->update(['colocacao' => null]);

            foreach ($premiacoes as $premiacao) {
                $cliente = $premiacao['cliente'];

                $campeonato->premios()->create([
                    'cliente_id' => $cliente->id,
                    'colocacao' => $premiacao['colocacao'],
                    'valor' => $premiacao['valor'],
                    'descricao' => $premiacao['descricao'],
                ]);
                $campeonato->participantes()->updateExistingPivot($cliente->id, ['colocacao' => $premiacao['colocacao']]);

                if ($premiacao['valor'] > 0) {
                    $cliente->lancarCredito($premiacao['valor'], "{$premiacao['colocacao']}° lugar - {$campeonato->nome}");
                }
            }

            $campeonato->update(['status' => 'finalizado']);
        });

        return redirect()->route('campeonatos.show', $campeonato)->with('success', 'Campeonato finalizado.');
    }

    public function adicionarParticipante(Request $request, Campeonato $campeonato): RedirectResponse
    {
        if ($resposta = $this->bloqueadoSeFinalizado($campeonato)) {
            return $resposta;
        }

        $dados = $request->validate([
            'participantes' => ['required', 'array'],
            'participantes.*' => ['integer', 'exists:clientes,id'],
        ], [
            'participantes.required' => 'Escolha pelo menos um cliente.',
        ]);

        $campeonato->participantes()->syncWithoutDetaching($dados['participantes']);

        return back()->with('success', 'Participante adicionado.');
    }

    public function removerParticipante(Campeonato $campeonato, Cliente $cliente): RedirectResponse
    {
        if ($resposta = $this->bloqueadoSeFinalizado($campeonato)) {
            return $resposta;
        }

        $campeonato->participantes()->detach($cliente->id);

        return back()->with('success', "{$cliente->nome} removido do campeonato.");
    }

    /**
     * Campeonatos finalizados ficam só para leitura.
     */
    private function bloqueadoSeFinalizado(Campeonato $campeonato): ?RedirectResponse
    {
        if (! $campeonato->finalizado()) {
            return null;
        }

        return redirect()
            ->route('campeonatos.editar', $campeonato)
            ->with('error', 'Este campeonato já foi finalizado e não pode mais ser alterado.');
    }

    /**
     * Relações que Campeonato::dadosTela() usa.
     */
    private function relacoes(): array
    {
        return [
            'participantes' => fn ($q) => $q->orderBy('nome'),
            'premios.cliente',
        ];
    }

    private function carregar(Campeonato $campeonato): Campeonato
    {
        return $campeonato->load($this->relacoes());
    }

    /**
     * Clientes ativos para a busca de participantes (menos os já inscritos).
     *
     * @param  array<int, int>  $exceto
     * @return array<int, array<string, mixed>>
     */
    private function clientesParaInscrever(array $exceto = []): array
    {
        return Cliente::ativos()
            ->whereNotIn('id', $exceto)
            ->orderBy('nome')
            ->get()
            ->map(fn (Cliente $cliente) => $cliente->dadosParticipante())
            ->all();
    }
}
