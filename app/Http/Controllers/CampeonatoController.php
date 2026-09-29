<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampeonatoRequest;
use App\Http\Requests\UpdateCampeonatoRequest;
use App\Models\Campeonato;
use App\Models\User;
use Illuminate\Http\Request;

class CampeonatoController extends Controller
{
    public function index()
    {
        // Campeonato(s) em destaque no topo (carrossel se houver mais de um)
        $ativos = Campeonato::where('status', 'ativo')
            ->orderBy('data')
            ->get();

        // Lista "Outras competições"
        $finalizados = Campeonato::where('status', 'finalizado')
            ->orderByDesc('data')
            ->get();

        return view('campeonato.index', compact('ativos', 'finalizados'));
    }

    public function create()
    {
        return view('campeonato.create');
    }

    public function store(StoreCampeonatoRequest $request)
    {
        Campeonato::create($request->validated() + ['status' => 'ativo']);

        return redirect()
            ->route('campeonatos.index')
            ->with('success', 'Campeonato cadastrado!');
    }

    public function show(Campeonato $campeonato)
    {
        return view('campeonato.show', [
            'campeonato' => $campeonato->load('users'),
        ]);
    }

    public function edit(Campeonato $campeonato)
    {
        if ($campeonato->finalizado()) {
            return $this->bloqueado();
        }

        return view('campeonato.edit', compact('campeonato'));
    }

    public function update(UpdateCampeonatoRequest $request, Campeonato $campeonato)
    {
        if ($campeonato->finalizado()) {
            return $this->bloqueado();
        }

        $campeonato->update($request->validated());

        return redirect()
            ->route('campeonatos.show', $campeonato)
            ->with('success', 'Campeonato atualizado!');
    }

    public function destroy(Campeonato $campeonato)
    {
        $campeonato->delete();

        return redirect()
            ->route('campeonatos.index')
            ->with('success', 'Campeonato excluído!');
    }

    public function finalizar(Campeonato $campeonato)
    {
        $campeonato->update(['status' => 'finalizado']);

        return redirect()
            ->route('campeonatos.show', $campeonato)
            ->with('success', 'Campeonato finalizado!');
    }

    public function participantes(Campeonato $campeonato)
    {
        $campeonato->load('users');

        return view('campeonato.participantes', [
            'campeonato' => $campeonato,
            // Usuários que ainda podem ser adicionados
            'usuarios' => User::whereNotIn('id', $campeonato->users->pluck('id'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function adicionarParticipante(Request $request, Campeonato $campeonato)
    {
        $request->validate(
            ['user_id' => 'required|exists:users,id'],
            [
                'user_id.required' => 'Selecione um usuário.',
                'user_id.exists' => 'Usuário não encontrado.',
            ]
        );

        if ($campeonato->finalizado()) {
            return $this->bloqueado();
        }

        if ($campeonato->users()->count() >= $campeonato->participantes) {
            return back()->withErrors(['user_id' => 'O campeonato já atingiu o limite de participantes.']);
        }

        $campeonato->users()->syncWithoutDetaching([$request->user_id]);

        return back()->with('success', 'Participante adicionado!');
    }

    public function removerParticipante(Campeonato $campeonato, User $user)
    {
        if ($campeonato->finalizado()) {
            return $this->bloqueado();
        }

        $campeonato->users()->detach($user->id);

        return back()->with('success', 'Participante removido!');
    }

    private function bloqueado()
    {
        return redirect()
            ->route('campeonatos.index')
            ->withErrors(['status' => 'Campeonatos finalizados não podem ser editados.']);
    }
}
