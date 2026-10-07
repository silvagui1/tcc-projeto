<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Atividade;
use App\Models\Cliente;
use App\Models\ClienteCreditoHistorico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Quantos clientes carregar por página (lista e busca).
     */
    private const CLIENTES_POR_PAGINA = 20;

    /**
     * Colunas pelas quais a listagem pode ser ordenada (cabeçalho clicável
     * em _lista.blade.php) — qualquer outro valor recebido por querystring
     * cai no padrão ('nome'), nunca é passado direto pro orderBy().
     */
    private const COLUNAS_ORDENAVEIS = ['nome', 'creditos', 'data_nascimento'];

    /**
     * Tela principal: lista de clientes cadastrados (paginada), com os
     * cards de resumo (total, créditos em carteira, aniversariantes do mês,
     * cadastros da semana) calculados sobre a base inteira — não mudam com
     * os filtros da listagem abaixo, pois representam o panorama geral do
     * negócio, não o resultado de uma busca específica.
     */
    public function index(Request $request): View
    {
        $termo = '';
        $filtros = $this->filtrosAtuais($request);

        $clientes = $this->aplicarFiltros(Cliente::query(), $termo, $filtros)
            ->paginate(self::CLIENTES_POR_PAGINA);

        return view('clientes.index', [
            'clientes' => $clientes,
            'termo' => $termo,
            'filtros' => $filtros,
            'resumo' => $this->resumo(),
        ]);
    }

    /**
     * Busca clientes pelo nome (usada pela barra de busca via AJAX) e também
     * atende a troca de página, ordenação e filtros da lista (com ou sem
     * termo de busca). Retorna apenas o fragmento HTML da lista, para ser
     * injetado na página.
     */
    public function buscar(Request $request): View
    {
        $termo = trim((string) $request->query('nome', ''));
        $filtros = $this->filtrosAtuais($request);

        $clientes = $this->aplicarFiltros(Cliente::query(), $termo, $filtros)
            ->paginate(self::CLIENTES_POR_PAGINA)
            ->withQueryString();

        return view('clientes.partials._lista', [
            'clientes' => $clientes,
            'termo' => $termo,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Lê da querystring os filtros/ordenação ativos, sempre com um valor
     * seguro de fallback — usado tanto por index() (carga inicial) quanto
     * por buscar() (AJAX), para os dois lerem os parâmetros do mesmo jeito.
     *
     * @return array{status: string, saldo: string, aniversariantes: bool, sort: string, dir: string}
     */
    private function filtrosAtuais(Request $request): array
    {
        $sort = $request->query('sort', 'nome');

        return [
            'status' => in_array($request->query('status'), ['ativo', 'inativo'], true)
                ? $request->query('status')
                : 'todos',
            'saldo' => in_array($request->query('saldo'), ['com', 'sem'], true)
                ? $request->query('saldo')
                : 'todos',
            'aniversariantes' => $request->boolean('aniversariantes'),
            'sort' => in_array($sort, self::COLUNAS_ORDENAVEIS, true) ? $sort : 'nome',
            'dir' => $request->query('dir') === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Aplica busca por nome, filtros (status/saldo/aniversariantes) e
     * ordenação numa query de clientes — centralizado aqui porque index() e
     * buscar() precisam montar exatamente a mesma query.
     *
     * @param array{status: string, saldo: string, aniversariantes: bool, sort: string, dir: string} $filtros
     */
    private function aplicarFiltros(Builder $query, string $termo, array $filtros): Builder
    {
        return $query
            ->when($termo !== '', fn (Builder $q) => $q->where('nome', 'like', "%{$termo}%"))
            ->when($filtros['status'] !== 'todos', fn (Builder $q) => $q->where('status', $filtros['status']))
            ->when($filtros['saldo'] === 'com', fn (Builder $q) => $q->comSaldo())
            ->when($filtros['saldo'] === 'sem', fn (Builder $q) => $q->semSaldo())
            ->when($filtros['aniversariantes'], fn (Builder $q) => $q->aniversariantesDoMes())
            ->orderBy($filtros['sort'], $filtros['dir']);
    }

    /**
     * Números do card de resumo no topo da tela — panorama geral do
     * cadastro de clientes, independente de qualquer filtro/busca ativo.
     *
     * @return array{total: int, creditosEmCarteira: float, aniversariantesNoMes: int, cadastrosNaSemana: int}
     */
    private function resumo(): array
    {
        return [
            'total' => Cliente::count(),
            'creditosEmCarteira' => (float) Cliente::sum('creditos'),
            'aniversariantesNoMes' => Cliente::aniversariantesDoMes()->count(),
            'cadastrosNaSemana' => Cliente::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Retorna os dados de um cliente para preencher o modal de detalhes e o
     * de edição, incluindo as últimas movimentações de créditos.
     */
    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'data_nascimento' => $cliente->data_nascimento->format('Y-m-d'),
            'idade' => $cliente->idade,
            'whatsapp' => $cliente->whatsapp,
            'whatsapp_url' => $cliente->whatsapp_url,
            'status' => $cliente->status,
            'observacoes' => $cliente->observacoes,
            'creditos' => (float) $cliente->creditos,
            'foto_url' => $cliente->foto_url,
            'iniciais' => $cliente->iniciais,
            'cor_avatar' => $cliente->cor_avatar,
            'criado_em' => $cliente->created_at->format('d/m/Y'),
            'historico' => $this->historicoJson($cliente),
        ]);
    }

    /**
     * Últimas movimentações de créditos do cliente, no formato que o modal
     * de perfil e o de edição exibem.
     */
    private function historicoJson(Cliente $cliente): array
    {
        return $cliente->historicoCreditos()->take(5)->get()->map(fn (ClienteCreditoHistorico $item) => [
            'tipo' => $item->tipo,
            'valor' => (float) $item->valor,
            'saldo_novo' => (float) $item->saldo_novo,
            'motivo' => $item->motivo,
            'data' => $item->created_at->format('d/m/Y \à\s H:i'),
        ])->all();
    }

    /**
     * Cadastra um novo cliente.
     */
    public function store(StoreClienteRequest $request): JsonResponse
    {
        $dados = $request->validated();

        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        $cliente = Cliente::create($dados);

        // Créditos iniciais (definidos no cartão de créditos do modal de
        // criação) entram no histórico como um único lançamento "definir",
        // para o extrato do cliente já nascer com um saldo de partida
        // explicado, em vez de um saldo "do nada".
        if ((float) $cliente->creditos > 0) {
            $this->registrarHistoricoCreditos($cliente, 'definir', 0.0, (float) $cliente->creditos);
        }

        Atividade::registrar('clientes', "Cliente cadastrado: {$cliente->nome}");

        return response()->json([
            'success' => true,
            'message' => "Cliente {$cliente->nome} cadastrado com sucesso!",
            'id' => $cliente->id,
        ], 201);
    }

    /**
     * Atualiza os dados do cliente. Os créditos não passam por aqui: cada
     * movimentação de saldo é feita por ajustarCreditos(), que grava o
     * lançamento no histórico na hora.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $dados = $request->validated();

        if ($request->hasFile('foto')) {
            if ($cliente->foto) {
                Storage::disk('public')->delete($cliente->foto);
            }

            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        $cliente->update($dados);

        Atividade::registrar('clientes', "Dados de {$cliente->nome} atualizados");

        return response()->json([
            'success' => true,
            'message' => "Dados de {$cliente->nome} atualizados com sucesso!",
            'id' => $cliente->id,
        ]);
    }

    /**
     * Movimenta o saldo de créditos de um cliente (adicionar, descontar ou
     * definir o saldo direto) e grava o lançamento no histórico, com motivo
     * opcional. Cada movimentação é salva na hora, sem depender do "Salvar"
     * do formulário de dados.
     *
     * O saldo nunca fica negativo: descontar mais do que o saldo é recusado
     * com 422, em vez de zerar o valor em silêncio. A linha do cliente é
     * travada durante a operação para duas movimentações simultâneas não
     * calcularem sobre o mesmo saldo antigo.
     */
    public function ajustarCreditos(Request $request, Cliente $cliente): JsonResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:adicionar,descontar,definir'],
            'valor' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'motivo' => ['nullable', 'string', 'max:120'],
        ], [
            'tipo.required' => 'Escolha se o valor deve ser adicionado, descontado ou definido como saldo.',
            'tipo.in' => 'Operação inválida.',
            'valor.required' => 'Informe um valor.',
            'valor.numeric' => 'Informe um valor numérico.',
            'valor.min' => 'O valor não pode ser negativo.',
            'valor.max' => 'O valor é maior que o permitido.',
            'motivo.max' => 'O motivo pode ter no máximo :max caracteres.',
        ]);

        $tipo = $dados['tipo'];
        $valor = round((float) $dados['valor'], 2);
        $motivo = trim((string) ($dados['motivo'] ?? '')) ?: null;

        if ($tipo !== 'definir' && $valor <= 0) {
            throw ValidationException::withMessages(['valor' => 'Informe um valor maior que zero.']);
        }

        DB::transaction(function () use ($cliente, $tipo, $valor, $motivo) {
            $atual = Cliente::query()->whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $saldoAnterior = (float) $atual->creditos;

            if ($tipo === 'definir') {
                $saldoNovo = $valor;
            } elseif ($tipo === 'adicionar') {
                $saldoNovo = $saldoAnterior + $valor;
            } else {
                if ($valor > $saldoAnterior) {
                    throw ValidationException::withMessages([
                        'valor' => 'Saldo insuficiente: o máximo a descontar é R$ '.number_format($saldoAnterior, 2, ',', '.').'.',
                    ]);
                }
                $saldoNovo = $saldoAnterior - $valor;
            }

            if ($saldoNovo === $saldoAnterior) {
                throw ValidationException::withMessages(['valor' => 'O saldo já é esse valor.']);
            }

            $atual->update(['creditos' => $saldoNovo]);
            $this->registrarHistoricoCreditos($atual, $tipo, $saldoAnterior, $saldoNovo, $motivo);
        });

        $cliente->refresh();

        $acoes = ['adicionar' => 'adicionados a', 'descontar' => 'descontados de', 'definir' => 'definidos como saldo de'];
        Atividade::registrar('clientes', 'Créditos: R$ '.number_format($valor, 2, ',', '.')." {$acoes[$tipo]} {$cliente->nome}"
            .($motivo ? " · {$motivo}" : ''));

        return response()->json([
            'success' => true,
            'message' => 'Saldo atualizado com sucesso!',
            'creditos' => (float) $cliente->creditos,
            'historico' => $this->historicoJson($cliente),
        ]);
    }

    /**
     * Registra um lançamento no extrato de créditos do cliente. Em "definir"
     * o valor registrado é o próprio saldo definido (o que o usuário digitou),
     * não a diferença — assim o extrato diz "Saldo definido · R$ 50,00".
     */
    private function registrarHistoricoCreditos(
        Cliente $cliente,
        string $tipo,
        float $saldoAnterior,
        float $saldoNovo,
        ?string $motivo = null,
    ): void {
        $cliente->historicoCreditos()->create([
            'tipo' => $tipo,
            'valor' => $tipo === 'definir' ? $saldoNovo : abs($saldoNovo - $saldoAnterior),
            'saldo_anterior' => $saldoAnterior,
            'saldo_novo' => $saldoNovo,
            'motivo' => $motivo,
        ]);
    }

    /**
     * Remove definitivamente um único cliente (e sua foto, se houver).
     *
     * Exclusão continua definitiva (sem soft delete — decisão já tomada
     * neste projeto, ver migration remove_soft_deletes_from_clientes_table).
     * O "desfazer" que a tela oferece é só uma janela de alguns segundos no
     * front-end antes de disparar esta requisição (ver clientes.js) — uma
     * vez que ela chega aqui, é definitivo.
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        $nome = $cliente->nome;

        if ($cliente->foto) {
            Storage::disk('public')->delete($cliente->foto);
        }

        $cliente->delete();

        Atividade::registrar('clientes', "Cliente excluído: {$nome}");

        return response()->json([
            'success' => true,
            'message' => "Cliente {$nome} removido com sucesso!",
        ]);
    }

    /**
     * Remove definitivamente vários clientes de uma vez (e as fotos deles,
     * se houver), usado pelo modo de seleção acionado pelo botão "apagar
     * clientes". Mesma observação de destroy() sobre não ter soft delete.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:clientes,id'],
        ], [
            'ids.required' => 'Selecione ao menos um cliente para excluir.',
            'ids.*.exists' => 'Um ou mais clientes selecionados não existem mais.',
        ]);

        $ids = $validated['ids'];
        $clientes = Cliente::whereIn('id', $ids)->get(['id', 'nome', 'foto']);

        foreach ($clientes as $cliente) {
            if ($cliente->foto) {
                Storage::disk('public')->delete($cliente->foto);
            }
        }

        Cliente::whereIn('id', $ids)->delete();

        Atividade::registrar('clientes', $clientes->count() === 1
            ? "Cliente excluído: {$clientes->first()->nome}"
            : "{$clientes->count()} clientes excluídos: ".$clientes->pluck('nome')->implode(', '));

        return response()->json([
            'success' => true,
            'message' => $clientes->count() === 1
                ? '1 cliente removido com sucesso!'
                : "{$clientes->count()} clientes removidos com sucesso!",
            'ids' => $ids,
        ]);
    }

    /**
     * Exporta clientes em CSV — os selecionados (via ?ids=1,2,3, usado pela
     * barra de seleção) ou, sem seleção, todos os que batem com os filtros
     * de busca atuais.
     */
    public function exportar(Request $request): Response
    {
        $ids = array_filter(explode(',', (string) $request->query('ids', '')));
        $termo = trim((string) $request->query('nome', ''));
        $filtros = $this->filtrosAtuais($request);

        $clientes = $ids !== []
            ? Cliente::whereIn('id', $ids)->orderBy('nome')->get()
            : $this->aplicarFiltros(Cliente::query(), $termo, $filtros)->get();

        $linhas = array_merge(
            [['Nome', 'Data de nascimento', 'WhatsApp', 'Status', 'Créditos (R$)', 'Observações']],
            $clientes->map(fn (Cliente $cliente) => [
                $cliente->nome,
                $cliente->data_nascimento->format('d/m/Y'),
                $cliente->whatsapp ?? '',
                $cliente->status,
                number_format((float) $cliente->creditos, 2, ',', '.'),
                $cliente->observacoes ?? '',
            ])->all(),
        );

        $csv = '';
        $saida = fopen('php://temp', 'r+');
        foreach ($linhas as $linha) {
            fputcsv($saida, $linha, ';');
        }
        rewind($saida);
        $csv = stream_get_contents($saida);
        fclose($saida);

        $nomeArquivo = 'clientes-'.now()->format('Y-m-d').'.csv';

        // BOM UTF-8 no início: sem ele o Excel abre acentos quebrados.
        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nomeArquivo}\"",
        ]);
    }
}
