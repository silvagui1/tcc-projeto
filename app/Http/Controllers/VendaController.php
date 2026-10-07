<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendaRequest;
use App\Models\Aluguel;
use App\Models\Carta;
use App\Models\Cliente;
use App\Models\JogoCarta;
use App\Models\Mesa;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\VendaItem;
use App\Services\Configuracoes;
use App\Services\VendaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VendaController extends Controller
{
    private const PERIODOS = ['hoje', '7dias', 'mes', 'tudo'];

    /**
     * Tela de vendas, com duas abas: o histórico de vendas e a agenda de
     * aluguéis de mesas. O resumo do topo é o panorama geral (não muda com
     * os filtros), igual à tela de Clientes.
     */
    public function index(Request $request): View
    {
        $aba = $request->query('aba') === 'alugueis' ? 'alugueis' : 'vendas';
        $filtros = $this->filtrosAtuais($request);

        $dados = [
            'aba' => $aba,
            'filtros' => $filtros,
            'resumo' => $this->resumo(),
            'mesas' => Mesa::orderBy('id')->get(),
            // todas (filtro do histórico) e só as aceitas hoje (nova venda)
            'formasPagamento' => Venda::FORMAS_PAGAMENTO,
            'formasAtivas' => Venda::formasAtivas(),
            'tiposJogo' => Configuracoes::tiposJogo(),
            // ?nova=1 (atalho "adicionar venda" da página inicial) abre o
            // modal de nova venda assim que a página carrega.
            'abrirNovaVenda' => $request->boolean('nova'),
        ];

        if ($aba === 'vendas') {
            $vendas = $this->vendasFiltradas($filtros);
            $dados += [
                'vendas' => $vendas,
                'totaisPorDia' => $this->totaisPorDia($vendas, $filtros),
            ];
        } else {
            $dados += $this->agendaDoDia($request);
        }

        return view('vendas.index', $dados);
    }

    /**
     * Busca, filtros e paginação da lista via AJAX — devolve só o fragmento
     * HTML da lista (mesmo esquema de ClienteController::buscar).
     */
    public function listar(Request $request): View
    {
        $filtros = $this->filtrosAtuais($request);
        $vendas = $this->vendasFiltradas($filtros);

        return view('vendas.partials._lista', [
            'vendas' => $vendas,
            'filtros' => $filtros,
            'totaisPorDia' => $this->totaisPorDia($vendas, $filtros),
        ]);
    }

    public function show(Venda $venda): JsonResponse
    {
        return response()->json($venda->load('itens', 'cliente')->dadosJson());
    }

    public function store(StoreVendaRequest $request, VendaService $service): JsonResponse
    {
        $venda = $service->registrar($request->validated());

        return response()->json([
            'success' => true,
            'message' => "Venda #{$venda->id} registrada · R$ ".number_format((float) $venda->total, 2, ',', '.'),
            'id' => $venda->id,
        ], 201);
    }

    public function cancelar(Request $request, Venda $venda, VendaService $service): JsonResponse
    {
        $dados = $request->validate([
            // obrigatório só se a loja pede (Configurações > Vendas)
            'motivo' => [Configuracoes::valor('vendas.exigir_motivo_cancelamento') ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Informe o motivo do cancelamento.',
            'motivo.max' => 'O motivo pode ter no máximo :max caracteres.',
        ]);

        $service->cancelar($venda, $dados['motivo'] ?? null);

        return response()->json([
            'success' => true,
            'message' => "Venda #{$venda->id} cancelada. Estoque e créditos foram devolvidos.",
        ]);
    }

    /**
     * Itens que podem entrar numa venda, para a busca do modal "Nova venda":
     * produtos, cartas avulsas ou aluguéis de mesa ainda não pagos.
     */
    public function catalogo(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        $itens = match ($request->query('tipo')) {
            'carta' => $this->catalogoCartas($termo),
            'aluguel' => $this->catalogoAlugueis($termo),
            default => $this->catalogoProdutos($termo),
        };

        return response()->json($itens);
    }

    /**
     * Busca de clientes para vincular à venda ou ao aluguel.
     */
    public function clientes(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        $clientes = Cliente::query()
            ->when($termo !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$termo}%"))
            ->orderByRaw("status = 'inativo'")
            ->orderBy('nome')
            ->limit(8)
            ->get()
            ->map(fn (Cliente $cliente) => $cliente->dadosResumidos());

        return response()->json($clientes);
    }

    // ---------------------------------------------------------------------
    // Lista de vendas
    // ---------------------------------------------------------------------

    /**
     * @return array{busca: string, periodo: string, pagamento: string, tipo: string}
     */
    private function filtrosAtuais(Request $request): array
    {
        $pagamentos = [...array_keys(Venda::FORMAS_PAGAMENTO), 'creditos'];

        return [
            'busca' => trim((string) $request->query('busca', '')),
            'periodo' => in_array($request->query('periodo'), self::PERIODOS, true) ? $request->query('periodo') : 'tudo',
            'pagamento' => in_array($request->query('pagamento'), $pagamentos, true) ? $request->query('pagamento') : 'todos',
            'tipo' => in_array($request->query('tipo'), VendaItem::TIPOS, true) ? $request->query('tipo') : 'todos',
        ];
    }

    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        $busca = $filtros['busca'];
        $numero = ltrim($busca, '#');

        return $query
            ->when($busca !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($busca, $numero) {
                $q->whereHas('cliente', fn (Builder $c) => $c->where('nome', 'like', "%{$busca}%"))
                    ->orWhereHas('itens', fn (Builder $i) => $i->where('descricao', 'like', "%{$busca}%"));

                // "#12" ou "12" também acha a venda pelo número
                if (ctype_digit($numero)) {
                    $q->orWhere('id', (int) $numero);
                }
            }))
            ->when($filtros['periodo'] === 'hoje', fn (Builder $q) => $q->where('created_at', '>=', today()))
            ->when($filtros['periodo'] === '7dias', fn (Builder $q) => $q->where('created_at', '>=', today()->subDays(6)))
            ->when($filtros['periodo'] === 'mes', fn (Builder $q) => $q->where('created_at', '>=', today()->startOfMonth()))
            ->when($filtros['pagamento'] === 'creditos', fn (Builder $q) => $q->where('valor_creditos', '>', 0))
            ->when(! in_array($filtros['pagamento'], ['todos', 'creditos'], true), fn (Builder $q) => $q->where('forma_pagamento', $filtros['pagamento']))
            ->when($filtros['tipo'] !== 'todos', fn (Builder $q) => $q->whereHas('itens', fn (Builder $i) => $i->where('tipo', $filtros['tipo'])));
    }

    private function vendasFiltradas(array $filtros): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Venda::query()->with('itens', 'cliente'), $filtros)
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) Configuracoes::valor('vendas.por_pagina'))
            ->withPath(route('vendas.index'))
            ->withQueryString();
    }

    /**
     * Total e quantidade de vendas concluídas de cada dia que aparece na
     * página atual — vai no cabeçalho de cada grupo ("Hoje · 3 vendas ·
     * R$ 125,00"). Calculado no banco, com os mesmos filtros, para o número
     * do dia estar certo mesmo quando o dia continua na próxima página.
     *
     * @return array<string, array{total: float, quantidade: int}>
     */
    private function totaisPorDia(LengthAwarePaginator $vendas, array $filtros): array
    {
        $dias = collect($vendas->items())->map(fn (Venda $v) => $v->created_at->format('Y-m-d'))->unique();

        if ($dias->isEmpty()) {
            return [];
        }

        $totais = [];

        foreach ($dias as $dia) {
            $data = Carbon::createFromFormat('Y-m-d', $dia);
            $linha = $this->aplicarFiltros(Venda::query()->concluidas(), $filtros)
                ->whereBetween('created_at', [$data->copy()->startOfDay(), $data->copy()->endOfDay()])
                ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as quantidade')
                ->first();

            $totais[$dia] = ['total' => (float) $linha->total, 'quantidade' => (int) $linha->quantidade];
        }

        return $totais;
    }

    /**
     * Números da tira de resumo no topo da tela.
     *
     * @return array<string, mixed>
     */
    private function resumo(): array
    {
        $hoje = Venda::resumoDoDia(today());

        $mes = Venda::concluidas()
            ->where('created_at', '>=', today()->startOfMonth())
            ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as quantidade')
            ->first();

        $proximaReserva = Aluguel::with('mesa')
            ->where('status', 'agendado')
            ->whereBetween('inicio', [now(), today()->endOfDay()])
            ->orderBy('inicio')
            ->first();

        return [
            'hojeTotal' => $hoje['total'],
            'hojeQuantidade' => $hoje['quantidade'],
            'mesTotal' => (float) $mes->total,
            'mesQuantidade' => (int) $mes->quantidade,
            'ticketMedio' => $mes->quantidade ? (float) $mes->total / $mes->quantidade : 0.0,
            'reservasHoje' => Aluguel::ativos()->whereBetween('inicio', [today(), today()->endOfDay()])->count(),
            'proximaReserva' => $proximaReserva,
        ];
    }

    // ---------------------------------------------------------------------
    // Aba de aluguéis
    // ---------------------------------------------------------------------

    /**
     * Agenda de um dia (?dia=AAAA-MM-DD, hoje por padrão): a semana para
     * navegar, as reservas do dia e a faixa de horários do quadro de
     * ocupação das mesas.
     *
     * @return array<string, mixed>
     */
    private function agendaDoDia(Request $request): array
    {
        $dia = today();
        $parametro = (string) $request->query('dia', '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $parametro)) {
            try {
                $dia = Carbon::createFromFormat('Y-m-d', $parametro)->startOfDay();
            } catch (\Throwable) {
                $dia = today();
            }
        }

        $inicioSemana = $dia->copy()->startOfWeek(Carbon::MONDAY);
        $fimSemana = $inicioSemana->copy()->addDays(6)->endOfDay();

        $porDia = Aluguel::ativos()
            ->whereBetween('inicio', [$inicioSemana, $fimSemana])
            ->get(['inicio'])
            ->countBy(fn (Aluguel $a) => $a->inicio->format('Y-m-d'));

        $semana = collect(range(0, 6))->map(function (int $i) use ($inicioSemana, $porDia) {
            $data = $inicioSemana->copy()->addDays($i);

            return ['data' => $data, 'reservas' => $porDia[$data->format('Y-m-d')] ?? 0];
        });

        $alugueis = Aluguel::with('mesa', 'cliente', 'itemVenda')
            ->whereBetween('inicio', [$dia, $dia->copy()->endOfDay()])
            ->orderBy('inicio')
            ->get();

        // Quadro de ocupação: mesas ativas + qualquer mesa (já desativada)
        // que tenha reserva neste dia.
        $mesasDoDia = Mesa::query()
            ->where('ativa', true)
            ->orWhereIn('id', $alugueis->pluck('mesa_id'))
            ->orderBy('id')
            ->get();

        // Faixa de horas do quadro: o horário de funcionamento do dia
        // (Configurações > Loja), alargada se alguma reserva começa antes ou
        // termina depois. Dia fechado usa 10h–22h só para o quadro existir.
        $funcionamento = Configuracoes::faixaDoDia($dia->dayOfWeek);
        $horaInicial = $funcionamento ? intdiv($funcionamento[0], 60) : 10;
        $horaFinal = $funcionamento ? (int) ceil($funcionamento[1] / 60) : 22;

        $ativos = $alugueis->where('status', '!=', 'cancelado');
        $horaInicial = min($horaInicial, (int) ($ativos->min(fn (Aluguel $a) => $a->inicio->hour) ?? $horaInicial));
        $horaFinal = max($horaFinal, $horaInicial + 4, (int) ($ativos->max(fn (Aluguel $a) => $this->horaFinalNoDia($a, $dia)) ?? 0));

        return [
            'dia' => $dia,
            'semana' => $semana,
            'alugueis' => $alugueis,
            'mesasDoDia' => $mesasDoDia,
            'faixaHoras' => [$horaInicial, min($horaFinal, 30)],
            'funcionamento' => $funcionamento,
        ];
    }

    /**
     * Hora final (arredondada para cima) de uma reserva contada a partir do
     * dia exibido — 1h da manhã seguinte vira 25.
     */
    private function horaFinalNoDia(Aluguel $aluguel, Carbon $dia): int
    {
        return (int) ceil($dia->diffInMinutes($aluguel->fim) / 60);
    }

    // ---------------------------------------------------------------------
    // Catálogo do modal "Nova venda"
    // ---------------------------------------------------------------------

    private function catalogoProdutos(string $termo): array
    {
        return Produto::with('categoria')
            ->when($termo !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$termo}%"))
            ->orderByRaw('quantidade = 0')
            ->orderBy('nome')
            ->limit(12)
            ->get()
            ->map(fn (Produto $produto) => [
                'tipo' => 'produto',
                'id' => $produto->id,
                'nome' => $produto->nome,
                'detalhe' => $produto->categoria?->nome,
                'preco' => (float) $produto->preco,
                'estoque' => $produto->quantidade,
                'imagem' => $produto->imagem_url,
            ])->all();
    }

    private function catalogoCartas(string $termo): array
    {
        $jogos = JogoCarta::opcoes();

        return Carta::query()
            ->when($termo !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$termo}%"))
            ->orderByRaw('quantidade = 0')
            ->orderBy('nome')
            ->limit(12)
            ->get()
            ->map(fn (Carta $carta) => [
                'tipo' => 'carta',
                'id' => $carta->id,
                'nome' => $carta->nome,
                'detalhe' => implode(' · ', array_filter([
                    $jogos[$carta->jogo] ?? $carta->jogo,
                    $carta->estado,
                    $carta->foil ? 'Foil' : null,
                ])),
                'preco' => (float) $carta->preco,
                'estoque' => $carta->quantidade,
                'imagem' => $carta->imagem_url,
            ])->all();
    }

    /**
     * Aluguéis ainda não pagos: primeiro os de hoje em diante (mais
     * próximos primeiro), depois os que já passaram e ficaram sem pagar.
     */
    private function catalogoAlugueis(string $termo): array
    {
        $base = fn () => Aluguel::with('mesa', 'cliente')
            ->where('status', 'agendado')
            ->when($termo !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($termo) {
                $q->where('responsavel', 'like', "%{$termo}%")
                    ->orWhereHas('cliente', fn (Builder $c) => $c->where('nome', 'like', "%{$termo}%"))
                    ->orWhereHas('mesa', fn (Builder $m) => $m->where('nome', 'like', "%{$termo}%"));
            }));

        $proximos = $base()->whereBetween('inicio', [today(), today()->addDays(30)])->orderBy('inicio')->limit(12)->get();
        $pendentes = $base()->whereBetween('inicio', [today()->subDays(30), today()])->orderByDesc('inicio')->limit(12)->get();

        return $proximos->concat($pendentes)->take(12)->map(fn (Aluguel $aluguel) => [
            'tipo' => 'aluguel',
            'id' => $aluguel->id,
            'nome' => $aluguel->descricao_venda,
            'detalhe' => $aluguel->nome_exibicao.' · '.$aluguel->tipo_jogo_rotulo.($aluguel->inicio->isPast() && ! $aluguel->inicio->isToday() ? ' · pendente' : ''),
            'preco' => (float) $aluguel->valor,
            'estoque' => null,
            'imagem' => null,
            'cliente' => $aluguel->cliente?->dadosResumidos(),
        ])->values()->all();
    }
}
