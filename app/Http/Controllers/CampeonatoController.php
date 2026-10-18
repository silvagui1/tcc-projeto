<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarCampeonatoRequest;
use App\Models\Atividade;
use App\Models\Campeonato;
use App\Models\Cliente;
use App\Models\JogoCarta;
use App\Models\Premio;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Campeonatos (resources/views/pages/campeonatos). Como em Estoque e
 * Clientes, tudo acontece na própria lista: criar, ver, editar e premiar
 * abrem num pop-up. O pop-up busca o conteúdo nas rotas GET abaixo (pedido
 * via fetch → devolve só o fragmento) e envia os formulários por fetch
 * (resposta em JSON). Sem JavaScript, as mesmas rotas redirecionam para a
 * lista com ?abrir=ID.
 */
class CampeonatoController extends Controller
{
    /**
     * Campeonatos finalizados por página ("Outras competições").
     */
    private const FINALIZADOS_POR_PAGINA = 9;

    public function index(Request $request): View
    {
        // filtros da barra acima da lista (vêm na url, ex.: ?jogo=magic&status=finalizado)
        $filtros = [
            'busca' => trim((string) $request->query('busca', '')),
            'jogo' => in_array($request->query('jogo'), JogoCarta::chaves(), true) ? $request->query('jogo') : '',
            'tipo' => array_key_exists((string) $request->query('tipo'), Campeonato::TIPOS) ? $request->query('tipo') : '',
            'status' => in_array($request->query('status'), ['ativo', 'finalizado'], true) ? $request->query('status') : '',
            'ordenar' => $request->query('ordenar') === 'antigos' ? 'antigos' : 'recentes',
        ];

        $consulta = fn () => Campeonato::with($this->relacoes())
            ->when($filtros['busca'] !== '', fn ($q) => $q->where('nome', 'like', '%'.$filtros['busca'].'%'))
            ->when($filtros['jogo'] !== '', fn ($q) => $q->where('jogo', $filtros['jogo']))
            ->when($filtros['tipo'] !== '', fn ($q) => $q->where('tipo', $filtros['tipo']));

        // em andamento: o mais próximo primeiro (é o que a loja vai rodar agora)
        $ativos = $filtros['status'] === 'finalizado'
            ? []
            : $consulta()->ativos()->orderBy('data')->orderBy('horario')->get()
                ->map(fn (Campeonato $c) => $c->dadosTela())->all();

        $paginaFinalizados = $consulta()->finalizados()
            ->orderBy('data', $filtros['ordenar'] === 'antigos' ? 'asc' : 'desc')
            ->when($filtros['status'] === 'ativo', fn ($q) => $q->whereRaw('1 = 0'))
            ->paginate(self::FINALIZADOS_POR_PAGINA)
            ->withQueryString();
        $outras = $paginaFinalizados->getCollection()->map(fn (Campeonato $c) => $c->dadosTela())->all();

        $filtrosAtivos = ($filtros['busca'] !== '') + ($filtros['jogo'] !== '') + ($filtros['tipo'] !== '') + ($filtros['status'] !== '');
        $jogosDisponiveis = JogoCarta::opcoes();
        $resumo = $this->resumo();
        $provisoriosPendentes = $this->provisoriosPendentes();

        return view('pages.campeonatos.index', compact(
            'ativos', 'outras', 'paginaFinalizados', 'filtros', 'filtrosAtivos', 'jogosDisponiveis', 'resumo', 'provisoriosPendentes'
        ));
    }

    /**
     * Banner padrão do sistema (SVG nas cores do jogo, com o tipo), usado
     * quando o campeonato não tem imagem própria.
     */
    public function banner(Request $request): Response
    {
        $jogo = (string) $request->query('jogo', '');
        $tipo = array_key_exists((string) $request->query('tipo'), Campeonato::TIPOS) ? $request->query('tipo') : 'liga';

        return response(Campeonato::svgBanner($jogo, $tipo), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // ---------------------------------------------------------------------
    // Pop-ups (fragmentos)
    // ---------------------------------------------------------------------

    public function create(Request $request): View|RedirectResponse
    {
        $copiar = $request->integer('copiar') ?: null;

        if (! $request->ajax()) {
            return redirect()->route('campeonatos.index', $copiar ? ['copiar' => $copiar] : ['criar' => 1]);
        }

        // "Repetir": o formulário já vem com os dados de outro campeonato,
        // na mesma hora e dia da semana, a partir da próxima semana
        $modelo = null;
        if ($copiar && ($original = Campeonato::find($copiar))) {
            $modelo = $this->carregar($original)->dadosTela();
            $data = $original->data->copy()->addWeek();
            while ($data->lt(today())) {
                $data->addWeek();
            }
            $modelo['dataIso'] = $data->format('Y-m-d');
            $modelo['copiaDe'] = $original->nome;
        }

        return view('pages.campeonatos.partials.modal-form', [
            'campeonato' => null,
            'modelo' => $modelo,
            'clientesSugeridos' => $this->clientesParaInscrever(),
        ]);
    }

    public function show(Request $request, Campeonato $campeonato): View|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('campeonatos.index', ['abrir' => $campeonato->id]);
        }

        $this->carregar($campeonato);
        $clientesSugeridos = $campeonato->finalizado()
            ? []
            : $this->clientesParaInscrever($campeonato->participantes->modelKeys());

        return view('pages.campeonatos.partials.modal-detalhes', [
            'campeonato' => $campeonato->dadosTela(),
            'clientesSugeridos' => $clientesSugeridos,
            'formasPagamento' => $this->formasPagamento(),
            'pendencias' => $campeonato->pendenciasAntesDeFinalizar(),
            // modo "dia do campeonato": só inscrição, pagamento e o caixa
            'checkin' => $request->query('modo') === 'checkin' && ! $campeonato->finalizado(),
        ]);
    }

    public function edit(Request $request, Campeonato $campeonato): View|RedirectResponse
    {
        if (! $request->ajax() || $campeonato->finalizado()) {
            return redirect()->route('campeonatos.index', ['abrir' => $campeonato->id]);
        }

        return view('pages.campeonatos.partials.modal-form', [
            'campeonato' => $this->carregar($campeonato)->dadosTela(),
            'clientesSugeridos' => [],
        ]);
    }

    /**
     * Pop-up de premiações: um bloco por colocação, já com os créditos
     * planejados no cadastro. O envio vai para finalizar().
     */
    public function premiacoes(Request $request, Campeonato $campeonato): View|RedirectResponse
    {
        if (! $request->ajax() || $campeonato->finalizado()) {
            return redirect()->route('campeonatos.index', ['abrir' => $campeonato->id]);
        }

        $this->carregar($campeonato);
        $planejado = $campeonato->premiacaoPlanejada();

        $colocacoes = collect([1, 2, 3])->map(fn (int $posicao) => [
            'posicao' => "{$posicao}° Lugar",
            'valor' => $planejado[$posicao] ?? 0.0,
            'descricao' => $posicao === 1 ? (string) $campeonato->premio_extra : '',
        ])->all();

        $dados = $campeonato->dadosTela();

        return view('pages.campeonatos.partials.modal-premiacao', [
            'campeonato' => $dados,
            'colocacoes' => $colocacoes,
            // com "pago", para avisar quem ainda deve a inscrição
            'participantes' => $dados['participantesLista'],
            'pendencias' => $campeonato->pendenciasAntesDeFinalizar(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Cadastro
    // ---------------------------------------------------------------------

    public function store(SalvarCampeonatoRequest $request): JsonResponse|RedirectResponse
    {
        $campeonato = DB::transaction(function () use ($request) {
            $dados = $request->dadosCampeonato() + ['status' => 'ativo', 'requisitos' => []];
            if ($dados['semanal']) {
                $dados['serie'] = Campeonato::novaSerie();
            }

            $campeonato = Campeonato::create($dados);
            $campeonato->participantes()->sync($request->validated('participantes', []));

            foreach ($request->validated('provisorios', []) as $provisorio) {
                $campeonato->participantes()->attach(Cliente::criarProvisorio($provisorio['nome'], $provisorio['whatsapp'])->id);
            }

            Atividade::registrar('campeonatos', "Campeonato criado: {$campeonato->nome}");

            return $campeonato;
        });

        return $this->responder($request, 'Campeonato criado.', $campeonato);
    }

    public function update(SalvarCampeonatoRequest $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        if ($campeonato->finalizado()) {
            return $this->recusar($request, 'Este campeonato já foi finalizado e não pode mais ser alterado.', $campeonato);
        }

        DB::transaction(function () use ($request, $campeonato) {
            $dados = $request->dadosCampeonato($campeonato);

            // ligou "repete toda semana" agora: começa uma série nova
            if ($dados['semanal'] && ! $campeonato->serie) {
                $dados['serie'] = Campeonato::novaSerie();
            }
            // trocou de tipo: o checklist do tipo antigo não vale mais
            if ($dados['tipo'] !== $campeonato->tipo) {
                $dados['requisitos'] = [];
            }

            $campeonato->update($dados);
            Atividade::registrar('campeonatos', "Campeonato editado: {$campeonato->nome}");
        });

        return $this->responder($request, "{$campeonato->nome} atualizado.", $campeonato);
    }

    /**
     * Apaga o campeonato com inscrições e premiações. Os créditos já ganhos
     * continuam no saldo dos clientes, e as inscrições pagas continuam no
     * histórico de Vendas (o histórico de créditos guarda o motivo).
     *
     * Semanal: sem "parar de repetir", o da semana seguinte já fica
     * pré-cadastrado (é só pular esta semana).
     */
    public function destroy(Request $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        $nome = $campeonato->nome;
        $mensagem = "{$nome} apagado.";

        DB::transaction(function () use ($request, $campeonato, &$mensagem) {
            if ($campeonato->semanal && $request->boolean('parar_repeticao')) {
                Campeonato::where('serie', $campeonato->serie)->update(['semanal' => false]);
                $mensagem .= ' Ele não vai mais se repetir.';
            } elseif (! $campeonato->finalizado() && ($proximo = $campeonato->criarProximaSemana())) {
                $mensagem .= " O da semana seguinte ({$proximo->data->format('d/m')}) já está pré-cadastrado.";
            }

            // provisórios que só existiam por causa deste campeonato
            $campeonato->participantes()->provisorios()->get()
                ->each(fn (Cliente $cliente) => $this->tirarProvisorio($cliente, $campeonato));

            $campeonato->apagarArquivoDeImagem();
            $campeonato->delete();
        });

        Atividade::registrar('campeonatos', "Campeonato apagado: {$nome}");

        return $this->responder($request, $mensagem);
    }

    // ---------------------------------------------------------------------
    // Participantes (no pop-up do campeonato)
    // ---------------------------------------------------------------------

    public function inscrever(Request $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        if ($campeonato->finalizado()) {
            return $this->recusar($request, 'Campeonato finalizado não recebe inscrições.', $campeonato);
        }

        $dados = $request->validate([
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('cadastro', Cliente::CADASTRO_CLIENTE)],
        ] + $this->regraPagarJunto(), [
            'cliente_id.*' => 'Cliente não encontrado.',
            'forma_pagamento.in' => 'Forma de pagamento indisponível.',
        ]);

        $cliente = Cliente::findOrFail($dados['cliente_id']);
        $pagou = DB::transaction(function () use ($campeonato, $cliente, $dados) {
            $campeonato->participantes()->syncWithoutDetaching([$cliente->id]);

            return $this->pagarJunto($campeonato, $cliente, $dados['forma_pagamento'] ?? null);
        });

        return $this->responder($request, "{$cliente->nome} inscrito".($pagou ? ' e pago.' : '.'), $campeonato, recarregarPagina: false);
    }

    /**
     * "Inscrever já pagando": a forma de pagamento vem junto com a inscrição
     * (dia do campeonato, fila no balcão).
     *
     * @return array<string, array<int, mixed>>
     */
    private function regraPagarJunto(): array
    {
        return ['forma_pagamento' => ['nullable', Rule::in(array_keys($this->formasPagamento()))]];
    }

    /**
     * Registra o pagamento da inscrição recém-feita, se veio uma forma e o
     * campeonato cobra inscrição. Devolve se pagou.
     */
    private function pagarJunto(Campeonato $campeonato, Cliente $cliente, ?string $forma): bool
    {
        if (! $forma || (float) $campeonato->valor_inscricao <= 0) {
            return false;
        }

        $this->lancarVendaDaInscricao($campeonato, $cliente, $forma);

        return true;
    }

    /**
     * Participante provisório ("fantasma"): quem nunca veio à loja entra só
     * com o nome. Recebe créditos normalmente e, no fim, a loja decide se
     * ele vira cliente (decidirProvisorio).
     */
    public function inscreverProvisorio(Request $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        if ($campeonato->finalizado()) {
            return $this->recusar($request, 'Campeonato finalizado não recebe inscrições.', $campeonato);
        }

        $request->merge([
            'nome' => trim((string) $request->input('nome')),
            'whatsapp' => preg_replace('/\D/', '', (string) $request->input('whatsapp')) ?: null,
        ]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'regex:/^\d{10,11}$/'],
        ] + $this->regraPagarJunto(), [
            'nome.required' => 'Informe pelo menos o nome.',
            'whatsapp.regex' => 'Informe o WhatsApp com DDD (ou deixe em branco).',
            'forma_pagamento.in' => 'Forma de pagamento indisponível.',
        ]);

        $pagou = DB::transaction(function () use ($campeonato, $dados) {
            $cliente = Cliente::criarProvisorio($dados['nome'], $dados['whatsapp']);
            $campeonato->participantes()->attach($cliente->id);

            return $this->pagarJunto($campeonato, $cliente, $dados['forma_pagamento'] ?? null);
        });

        return $this->responder($request, "{$dados['nome']} inscrito como provisório".($pagou ? ' e pago.' : '.'), $campeonato, recarregarPagina: false);
    }

    /**
     * Tira a inscrição. Inscrição paga não sai por aqui: o pagamento virou
     * uma venda, que precisa ser cancelada em Vendas antes.
     */
    public function removerParticipante(Request $request, Campeonato $campeonato, Cliente $cliente): JsonResponse|RedirectResponse
    {
        $inscrito = $campeonato->participantes()->whereKey($cliente->id)->first();

        if ($campeonato->finalizado()) {
            return $this->recusar($request, 'Campeonato finalizado não pode ser alterado.', $campeonato);
        }
        if (! $inscrito) {
            return $this->recusar($request, "{$cliente->nome} não está inscrito neste campeonato.", $campeonato);
        }
        if ($inscrito->pivot->venda_id) {
            return $this->recusar($request, "A inscrição de {$cliente->nome} está paga. Para tirar, cancele a venda em Vendas.", $campeonato);
        }

        DB::transaction(function () use ($campeonato, $cliente) {
            $campeonato->participantes()->detach($cliente->id);
            if ($cliente->provisorio()) {
                $this->tirarProvisorio($cliente, $campeonato);
            }
        });

        return $this->responder($request, "{$cliente->nome} saiu do campeonato.", $campeonato, recarregarPagina: false);
    }

    /**
     * Decisão sobre um participante provisório: "adicionar" (vira cliente,
     * com aviso para completar o cadastro em Clientes) ou "descartar"
     * (fica só no histórico; créditos não usados são zerados).
     */
    public function decidirProvisorio(Request $request, Campeonato $campeonato, Cliente $cliente): JsonResponse|RedirectResponse
    {
        $dados = $request->validate(['acao' => ['required', Rule::in(['adicionar', 'descartar'])]]);

        if (! $cliente->provisorio()) {
            return $this->recusar($request, "{$cliente->nome} não é um participante provisório.", $campeonato);
        }

        if ($dados['acao'] === 'adicionar') {
            $cliente->tornarCliente();
            Atividade::registrar('clientes', "Cliente cadastrado pelo campeonato {$campeonato->nome}: {$cliente->nome}");
            $mensagem = "{$cliente->nome} agora está em Clientes. Complete o cadastro (data de nascimento) por lá.";
        } else {
            DB::transaction(fn () => $cliente->descartarProvisorio("Não cadastrado após {$campeonato->nome}"));
            $mensagem = "{$cliente->nome} não foi adicionado aos clientes.";
        }

        return $this->responder($request, $mensagem, $campeonato, recarregarPagina: false);
    }

    /**
     * Checklist da burocracia dos eventos oficiais (sanção, Player ID...).
     */
    public function requisitos(Request $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        $dados = $request->validate([
            'requisitos' => ['nullable', 'array'],
            'requisitos.*' => [Rule::in(Campeonato::requisitosDoTipo($campeonato->tipo))],
        ]);

        $campeonato->update(['requisitos' => array_values($dados['requisitos'] ?? [])]);
        $feitos = count($campeonato->requisitos);
        $total = count($campeonato->checklist());

        return $this->responder($request, "Checklist: {$feitos} de {$total} feitos.", $campeonato, recarregarPagina: false);
    }

    // ---------------------------------------------------------------------
    // Premiação e pagamento
    // ---------------------------------------------------------------------

    /**
     * Salva as premiações e marca o campeonato como finalizado. O valor de
     * cada colocação vira crédito no saldo do cliente — o 1° lugar é
     * obrigatório e sempre recebe créditos. Depois disso o campeonato fica
     * só para leitura; se for semanal, o da semana seguinte já é criado.
     */
    public function finalizar(Request $request, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        if ($campeonato->finalizado()) {
            return $this->recusar($request, 'A premiação deste campeonato já foi registrada.', $campeonato);
        }

        $participantes = $campeonato->participantes()->pluck('clientes.id')->all();

        $dados = $request->validate([
            'premiacoes' => ['required', 'array', 'size:3'],
            'premiacoes.0.participante' => ['required', 'integer', Rule::in($participantes)],
            'premiacoes.*.participante' => ['nullable', 'integer', Rule::in($participantes)],
            'premiacoes.*.valor' => ['nullable', 'string', 'max:20'],
            'premiacoes.*.descricao' => ['nullable', 'string', 'max:250'],
        ], [
            'premiacoes.0.participante.required' => 'Escolha quem ficou em 1° lugar.',
            'premiacoes.*.participante.in' => 'Escolha um participante deste campeonato.',
            'premiacoes.*.descricao.max' => 'A descrição do prêmio pode ter no máximo :max caracteres.',
        ]);

        $premiacoes = [];
        $escolhidos = [];
        foreach (array_values($dados['premiacoes']) as $indice => $premiacao) {
            if (empty($premiacao['participante'])) {
                continue;
            }

            $colocacao = $indice + 1;
            $clienteId = (int) $premiacao['participante'];

            if (in_array($clienteId, $escolhidos, true)) {
                throw ValidationException::withMessages([
                    "premiacoes.$indice.participante" => "{$colocacao}° lugar: essa pessoa já está em outra colocação.",
                ]);
            }
            $escolhidos[] = $clienteId;

            $valor = (float) SalvarCampeonatoRequest::valorEmReais((string) ($premiacao['valor'] ?? ''));
            if ($valor < 0 || $valor > 99999.99) {
                throw ValidationException::withMessages(["premiacoes.$indice.valor" => "{$colocacao}° lugar: informe um crédito válido."]);
            }
            if ($colocacao === 1 && $valor <= 0) {
                throw ValidationException::withMessages(['premiacoes.0.valor' => 'O prêmio do 1° lugar é obrigatório e sempre em créditos.']);
            }

            $premiacoes[] = [
                'colocacao' => $colocacao,
                'cliente_id' => $clienteId,
                'valor' => round($valor, 2),
                'descricao' => $premiacao['descricao'] ?? null,
            ];
        }

        $proximo = DB::transaction(function () use ($campeonato, $premiacoes) {
            foreach ($premiacoes as $premiacao) {
                $premio = $campeonato->premios()->create($premiacao);
                $campeonato->participantes()->updateExistingPivot($premiacao['cliente_id'], ['colocacao' => $premiacao['colocacao']]);

                if ($premiacao['valor'] > 0) {
                    $premio->cliente->lancarCredito($premiacao['valor'], "{$premiacao['colocacao']}° lugar - {$campeonato->nome}");
                }
            }

            $campeonato->update(['status' => 'finalizado']);
            Atividade::registrar('campeonatos', "Campeonato finalizado: {$campeonato->nome}");

            return $campeonato->criarProximaSemana();
        });

        $mensagem = 'Campeonato finalizado e créditos lançados.';
        if ($proximo) {
            $mensagem .= " O da semana seguinte ({$proximo->data->format('d/m')}) já está pré-cadastrado.";
        }

        return $this->responder($request, $mensagem, $campeonato);
    }

    /**
     * Pagamento da inscrição de um participante: vira uma venda (item do tipo
     * "inscricao") no histórico de Vendas. A inscrição nunca é paga com
     * créditos — crédito é o prêmio do campeonato. Cancelar a venda em
     * Vendas deixa a inscrição em aberto de novo.
     */
    public function registrarPagamento(Request $request, Campeonato $campeonato, Cliente $cliente): JsonResponse|RedirectResponse
    {
        $dados = $request->validate([
            'forma_pagamento' => ['required', Rule::in(array_keys($this->formasPagamento()))],
        ], [
            'forma_pagamento.required' => 'Escolha a forma de pagamento.',
            'forma_pagamento.in' => 'Forma de pagamento indisponível (a inscrição não pode ser paga com créditos).',
        ]);

        $inscrito = $campeonato->participantes()->whereKey($cliente->id)->first();
        $valor = (float) $campeonato->valor_inscricao;

        if (! $inscrito) {
            return $this->recusar($request, "{$cliente->nome} não está inscrito neste campeonato.", $campeonato);
        }
        if ($inscrito->pivot->venda_id) {
            return $this->recusar($request, "A inscrição de {$cliente->nome} já está paga.", $campeonato);
        }
        if ($valor <= 0) {
            return $this->recusar($request, 'Este campeonato não cobra inscrição.', $campeonato);
        }

        DB::transaction(fn () => $this->lancarVendaDaInscricao($campeonato, $cliente, $dados['forma_pagamento']));

        return $this->responder($request, "Inscrição de {$cliente->nome} paga. A venda já aparece em Vendas.", $campeonato, recarregarPagina: false);
    }

    /**
     * Venda da inscrição (item "inscricao") ligada ao participante. Chamar
     * dentro de uma DB::transaction.
     */
    private function lancarVendaDaInscricao(Campeonato $campeonato, Cliente $cliente, string $forma): void
    {
        $valor = (float) $campeonato->valor_inscricao;

        $venda = Venda::create([
            'cliente_id' => $cliente->id,
            'total' => $valor,
            'valor_creditos' => 0,
            'valor_restante' => $valor,
            'forma_pagamento' => $forma,
            'status' => 'concluida',
        ]);

        $venda->itens()->create([
            'tipo' => VendaItem::TIPO_INSCRICAO,
            'campeonato_id' => $campeonato->id,
            'descricao' => mb_substr("Inscrição: {$campeonato->nome}", 0, 190),
            'quantidade' => 1,
            'preco_unitario' => $valor,
            'subtotal' => $valor,
        ]);

        $campeonato->participantes()->updateExistingPivot($cliente->id, ['valor_pago' => $valor, 'venda_id' => $venda->id]);

        Atividade::registrar('vendas', "Venda #{$venda->id}: inscrição de {$cliente->nome} em {$campeonato->nome}");
    }

    // ---------------------------------------------------------------------
    // Apoio
    // ---------------------------------------------------------------------

    /**
     * Resposta de sucesso. Pelo pop-up (fetch): JSON com a mensagem e, se a
     * lista precisa mudar (criou, editou, finalizou...), a url para
     * recarregar. Sem JavaScript: volta para a lista.
     */
    private function responder(Request $request, string $mensagem, ?Campeonato $abrir = null, bool $recarregarPagina = true): JsonResponse|RedirectResponse
    {
        $url = route('campeonatos.index', $abrir ? ['abrir' => $abrir->id] : []);

        if (! $request->expectsJson()) {
            return redirect($url)->with('success', $mensagem);
        }

        if ($recarregarPagina) {
            session()->flash('success', $mensagem);
        }

        return response()->json([
            'mensagem' => $mensagem,
            'redirecionar' => $recarregarPagina ? $url : null,
        ]);
    }

    private function recusar(Request $request, string $mensagem, Campeonato $campeonato): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensagem], 422);
        }

        return redirect()->route('campeonatos.index', ['abrir' => $campeonato->id])->with('error', $mensagem);
    }

    /**
     * Provisório que saiu do (ou perdeu o) campeonato: se não tem mais nada
     * no sistema, é apagado; se tem (outro campeonato, venda paga), vira
     * avulso para o histórico continuar com o nome dele.
     */
    private function tirarProvisorio(Cliente $cliente, Campeonato $campeonato): void
    {
        $temHistorico = $cliente->campeonatos()->where('campeonatos.id', '!=', $campeonato->id)->exists()
            || $cliente->vendas()->exists();

        if ($temHistorico) {
            $cliente->descartarProvisorio("Não cadastrado após {$campeonato->nome}");
        } else {
            $cliente->delete();
        }
    }

    /**
     * Números da tira de resumo — sobre todos os campeonatos (não muda com
     * os filtros), como em Clientes e Vendas.
     *
     * @return array<string, int|float>
     */
    private function resumo(): array
    {
        return [
            'ativos' => Campeonato::ativos()->count(),
            'finalizados' => Campeonato::finalizados()->count(),
            'semanais' => Campeonato::ativos()->where('semanal', true)->count(),
            'inscritos' => DB::table('campeonato_cliente')
                ->join('campeonatos', 'campeonatos.id', '=', 'campeonato_cliente.campeonato_id')
                ->where('campeonatos.status', 'ativo')
                ->count(),
            'creditos' => (float) Premio::sum('valor'),
        ];
    }

    /**
     * Provisórios de campeonatos já finalizados que ainda esperam a decisão
     * "vira cliente ou não" (aviso no topo da lista).
     *
     * @return array<int, array{nome: string, campeonato: int}>
     */
    private function provisoriosPendentes(): array
    {
        return Cliente::provisorios()
            ->whereHas('campeonatos', fn ($q) => $q->where('status', 'finalizado'))
            ->with(['campeonatos' => fn ($q) => $q->where('status', 'finalizado')->latest('data')])
            ->orderBy('nome')
            ->get()
            ->map(fn (Cliente $cliente) => ['nome' => $cliente->nome, 'campeonato' => $cliente->campeonatos->first()->id])
            ->all();
    }

    /**
     * Formas de pagamento ligadas em Configurações > Vendas. Créditos ficam
     * de fora de propósito: a inscrição é sempre paga "de verdade".
     *
     * @return array<string, string>
     */
    private function formasPagamento(): array
    {
        return collect(Venda::formasAtivas())->map(fn (array $forma) => $forma['rotulo'])->all();
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
        return Cliente::cadastrados()
            ->ativos()
            ->whereNotIn('id', $exceto)
            ->orderBy('nome')
            ->get()
            ->map(fn (Cliente $cliente) => $cliente->dadosParticipante())
            ->all();
    }
}
