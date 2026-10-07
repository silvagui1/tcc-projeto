<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarAluguelRequest;
use App\Models\Aluguel;
use App\Models\Mesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Aluguéis de mesas (aba "Aluguéis de mesas" da tela de Vendas). O pagamento
 * não fica aqui: um aluguel é pago entrando como item de uma venda (ver
 * VendaService::cobrarAluguel), para o faturamento ficar todo num lugar só.
 */
class AluguelController extends Controller
{
    public function show(Aluguel $aluguel): JsonResponse
    {
        return response()->json($aluguel->load('mesa', 'cliente', 'itemVenda')->dadosJson());
    }

    /**
     * Cria a reserva — ou, se "toda semana", uma reserva por semana até a
     * data final, todas com o mesmo `serie`. Se qualquer data bater com outra
     * reserva da mesma mesa, nada é criado e a mensagem diz qual é o conflito.
     */
    public function store(SalvarAluguelRequest $request): JsonResponse
    {
        $dados = $request->validated();
        [$inicio, $fim] = $request->intervalo();
        $duracao = $inicio->diffInMinutes($fim);
        $inicios = $request->inicios();

        $criados = DB::transaction(function () use ($dados, $duracao, $inicios) {
            // Trava a mesa: duas reservas simultâneas da mesma mesa não passam
            // juntas pela checagem de conflito.
            $mesa = Mesa::query()->whereKey($dados['mesa_id'])->lockForUpdate()->firstOrFail();

            $intervalos = collect($inicios)->map(fn (Carbon $i) => [$i, $i->copy()->addMinutes($duracao)]);
            $this->garantirSemConflitos($mesa, $intervalos);

            $serie = $intervalos->count() > 1 ? (string) Str::uuid() : null;

            return $intervalos->map(fn (array $intervalo) => Aluguel::create([
                'mesa_id' => $mesa->id,
                'cliente_id' => $dados['cliente_id'] ?? null,
                'responsavel' => empty($dados['cliente_id']) ? $dados['responsavel'] : null,
                'inicio' => $intervalo[0],
                'fim' => $intervalo[1],
                'valor' => $dados['valor'],
                'tipo_jogo' => $dados['tipo_jogo'],
                'jogo' => $dados['jogo'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
                'serie' => $serie,
            ]));
        });

        $primeiro = $criados->first()->load('mesa');

        $mensagem = $criados->count() > 1
            ? "{$criados->count()} datas reservadas: toda {$primeiro->dia_semana} às {$primeiro->inicio->format('H:i')}, até {$criados->last()->inicio->format('d/m')}."
            : "{$primeiro->mesa->nome} reservada para {$primeiro->inicio->format('d/m')} às {$primeiro->inicio->format('H:i')}.";

        return response()->json([
            'success' => true,
            'message' => $mensagem,
            'dia' => $primeiro->inicio->format('Y-m-d'),
        ], 201);
    }

    /**
     * Edita uma única data (as outras datas da série não mudam).
     */
    public function update(SalvarAluguelRequest $request, Aluguel $aluguel): JsonResponse
    {
        $this->garantirAgendado($aluguel, 'editada');

        $dados = $request->validated();
        [$inicio, $fim] = $request->intervalo();

        DB::transaction(function () use ($aluguel, $dados, $inicio, $fim) {
            $mesa = Mesa::query()->whereKey($dados['mesa_id'])->lockForUpdate()->firstOrFail();
            $this->garantirSemConflitos($mesa, collect([[$inicio, $fim]]), $aluguel->id);

            $aluguel->update([
                'mesa_id' => $mesa->id,
                'cliente_id' => $dados['cliente_id'] ?? null,
                'responsavel' => empty($dados['cliente_id']) ? $dados['responsavel'] : null,
                'inicio' => $inicio,
                'fim' => $fim,
                'valor' => $dados['valor'],
                'tipo_jogo' => $dados['tipo_jogo'],
                'jogo' => $dados['jogo'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reserva atualizada.',
            'dia' => $inicio->format('Y-m-d'),
        ]);
    }

    /**
     * Cancela só esta data ("este") ou esta e as próximas datas ainda
     * agendadas da mesma série ("proximos"). Datas já pagas ficam como estão.
     */
    public function cancelar(Request $request, Aluguel $aluguel): JsonResponse
    {
        $escopo = $request->validate(['escopo' => ['nullable', 'in:este,proximos']])['escopo'] ?? 'este';

        $this->garantirAgendado($aluguel, 'cancelada');

        if ($escopo === 'proximos' && $aluguel->serie) {
            $quantidade = Aluguel::where('serie', $aluguel->serie)
                ->where('inicio', '>=', $aluguel->inicio)
                ->where('status', 'agendado')
                ->update(['status' => 'cancelado']);

            $mensagem = $quantidade === 1 ? 'Reserva cancelada.' : "{$quantidade} datas canceladas.";
        } else {
            $aluguel->update(['status' => 'cancelado']);
            $mensagem = 'Reserva cancelada.';
        }

        return response()->json(['success' => true, 'message' => $mensagem]);
    }

    private function garantirAgendado(Aluguel $aluguel, string $acao): void
    {
        if ($aluguel->status === 'pago') {
            $vendaId = $aluguel->itemVenda?->venda_id;
            throw ValidationException::withMessages([
                'aluguel' => "Essa reserva já foi paga e não pode ser {$acao}. Para desfazer, cancele a venda #{$vendaId}.",
            ]);
        }

        if ($aluguel->status === 'cancelado') {
            throw ValidationException::withMessages(['aluguel' => 'Essa reserva já foi cancelada.']);
        }
    }

    /**
     * @param  Collection<int, array{0: Carbon, 1: Carbon}>  $intervalos
     */
    private function garantirSemConflitos(Mesa $mesa, Collection $intervalos, ?int $ignorarId = null): void
    {
        $conflitos = $intervalos
            ->map(fn (array $i) => Aluguel::with('cliente')
                ->conflitantes($mesa->id, $i[0], $i[1])
                ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
                ->orderBy('inicio')
                ->first())
            ->filter()
            ->values();

        if ($conflitos->isEmpty()) {
            return;
        }

        $primeiro = $conflitos->first();
        $mensagem = "{$mesa->nome} já está reservada em {$primeiro->inicio->format('d/m')} "
            ."das {$primeiro->inicio->format('H:i')} às {$primeiro->fim->format('H:i')} ({$primeiro->nome_exibicao})";

        if ($conflitos->count() > 1) {
            $outras = $conflitos->count() - 1;
            $mensagem .= $outras === 1 ? ' e em mais 1 data da série' : " e em mais {$outras} datas da série";
        }

        throw ValidationException::withMessages([
            'mesa_id' => $mensagem.'. Escolha outro horário ou outra mesa.',
        ]);
    }
}
