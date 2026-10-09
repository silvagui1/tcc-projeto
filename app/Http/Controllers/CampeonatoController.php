<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampeonatoRequest;
use App\Http\Requests\UpdateCampeonatoRequest;
use App\Models\Campeonato;
use App\Models\MovimentacaoCredito;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampeonatoController extends Controller
{
    public function index(){
        // vários ativos viram um carrossel na página principal
        $ativos = Campeonato::where('status', 'ativo')->orderBy('data')->get();
        $finalizados = Campeonato::withCount('participantes')
            ->where('status', 'finalizado')
            ->orderByDesc('data')
            ->get();

        return view('campeonato.index', compact('ativos', 'finalizados'));
    }

    public function create(){
        $clientes = User::orderBy('name')->get();

        return view('campeonato.create', compact('clientes'));
    }

    public function store(StoreCampeonatoRequest $request){
        $dados = $request->validated();

        $campeonato = Campeonato::create($dados);
        $campeonato->participantes()->sync($dados['participantes'] ?? []);

        return redirect()
            ->route('campeonatos.index')
            ->with('success', 'Campeonato criado com sucesso!');
    }

    public function show(Campeonato $campeonato){
        $campeonato->load(['participantes' => fn ($q) => $q->orderBy('name'), 'premios.usuario']);

        return view('campeonato.show', compact('campeonato'));
    }

    public function edit(Campeonato $campeonato){
        $campeonato->load(['participantes' => fn ($q) => $q->orderBy('name'), 'premios.usuario']);

        // clientes que ainda não estão no campeonato, para a busca de "adicionar"
        $clientes = User::whereNotIn('id', $campeonato->participantes->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('campeonato.edit', compact('campeonato', 'clientes'));
    }

    public function update(UpdateCampeonatoRequest $request, Campeonato $campeonato){
        // campeonatos finalizados ficam só para leitura
        if ($campeonato->finalizado()) {
            return redirect()
                ->route('campeonatos.edit', $campeonato)
                ->with('error', 'Este campeonato já foi finalizado e não pode mais ser alterado.');
        }

        $dados = $request->validated();

        $campeonato->update($dados);
        // na edição os participantes marcados são somados aos que já existem;
        // para tirar alguém usa-se a lixeira (campeonatos.participantes.destroy)
        $campeonato->participantes()->syncWithoutDetaching($dados['participantes'] ?? []);

        return redirect()
            ->route('campeonatos.index')
            ->with('success', 'Campeonato atualizado com sucesso!');
    }

    public function destroy(Campeonato $campeonato){
        $campeonato->delete();

        return redirect()
            ->route('campeonatos.index')
            ->with('success', 'Campeonato apagado!');
    }

    // Salva as premiações (tela de premiações) e marca o campeonato como finalizado.
    // Cada vencedor recebe o crédito como uma movimentação do tipo PREMIO.
    public function finalizar(Request $request, Campeonato $campeonato){
        $dados = $request->validate([
            'premiacoes'                 => 'required|array',
            'premiacoes.*.participante'  => 'nullable|integer',
            'premiacoes.*.valor'         => 'nullable|string',
            'premiacoes.*.descricao'     => 'nullable|string|max:250',
        ]);

        $participantes = $campeonato->participantes()->pluck('users.id');

        $premiacoes = [];
        foreach ($dados['premiacoes'] as $indice => $premiacao) {
            if (empty($premiacao['participante'])) {
                continue;
            }

            if (! $participantes->contains($premiacao['participante'])) {
                return back()->withInput()->withErrors([
                    "premiacoes.$indice.participante" => 'Escolha um participante deste campeonato.',
                ]);
            }

            $premiacoes[] = [
                'colocacao' => $indice + 1,
                'user_id'   => (int) $premiacao['participante'],
                'valor'     => $this->valorEmReais($premiacao['valor'] ?? ''),
                'descricao' => $premiacao['descricao'] ?? null,
            ];
        }

        DB::transaction(function () use ($campeonato, $premiacoes) {
            // finalizar de novo substitui as premiações e os créditos anteriores
            $campeonato->premios()->delete();
            MovimentacaoCredito::where('campeonato_id', $campeonato->id)->where('tipo', 'PREMIO')->delete();
            DB::table('campeonato_user')->where('campeonato_id', $campeonato->id)->update(['colocacao' => null]);

            foreach ($premiacoes as $premiacao) {
                $campeonato->premios()->create($premiacao);
                $campeonato->participantes()->updateExistingPivot($premiacao['user_id'], ['colocacao' => $premiacao['colocacao']]);

                if ($premiacao['valor'] > 0) {
                    MovimentacaoCredito::create([
                        'user_id'       => $premiacao['user_id'],
                        'campeonato_id' => $campeonato->id,
                        'tipo'          => 'PREMIO',
                        'valor'         => $premiacao['valor'],
                        'descricao'     => "{$premiacao['colocacao']}° lugar - {$campeonato->nome}",
                    ]);
                }
            }

            $campeonato->update(['status' => 'finalizado']);
        });

        return redirect()
            ->route('campeonatos.show', $campeonato)
            ->with('success', 'Campeonato finalizado!');
    }

    public function participantes(Campeonato $campeonato){
        $campeonato->load(['participantes' => fn ($q) => $q->orderBy('name')]);

        $clientes = User::whereNotIn('id', $campeonato->participantes->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('campeonato.participantes', compact('campeonato', 'clientes'));
    }

    public function adicionarParticipante(Request $request, Campeonato $campeonato){
        $dados = $request->validate([
            'participantes'   => 'required|array',
            'participantes.*' => 'integer|exists:users,id',
        ], [
            'participantes.required' => 'Escolha pelo menos um cliente.',
        ]);

        $campeonato->participantes()->syncWithoutDetaching($dados['participantes']);

        return back()->with('success', 'Participante adicionado!');
    }

    public function removerParticipante(Campeonato $campeonato, User $user){
        $campeonato->participantes()->detach($user->id);

        return back()->with('success', 'Participante removido!');
    }

    // "R$ 1.234,56" / "10,00" / "10.5" -> 1234.56 / 10.0 / 10.5
    private function valorEmReais(string $valor): float
    {
        $valor = preg_replace('/[^\d,.]/', '', $valor);

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return round((float) $valor, 2);
    }
}
