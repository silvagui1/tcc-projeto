<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampeonatoRequest;
use App\Http\Requests\UpdateCampeonatoRequest;
use App\Models\Campeonato;
use App\Models\User;
use Illuminate\Http\Request;

class CampeonatoController extends Controller
{
 
    public function index(){
        $ativos = CampeonatoModel::where('status', 'ativo')->get();
        $finalizados = CampeonatoModel::where('status', 'finalizado')->get();

        return view('campeonatos.index', compact('ativos', 'finalizados'));

        return back()->with('success', 'Participante removido!');
    }
 
    public function create(){
        return view('campeonatos.create');

    }
 
    public function store(Request $request){

        $dados = $request->validate([
            'nome' => 'required|string|max:150',
            'deck' => 'required|string|max:150',
            'data' => 'required|date',
            'valor_inscricao' => 'required|numeric|min:0',
            'descricao' => 'nullable|string|max:350',
            'imagem' => 'nullable|string',
        ]);

        $dados['status'] = 'ativo';

        CampeonatoModel::create($dados);

        return redirect()
        ->route('campeonatos.index')
        ->with('success', 'Campeonato criado com sucesso!');
    }
 
    public function show(CampeonatoModel $campeonato){

    }
 
    public function edit(CampeonatoModel $campeonato){

    }
 
    public function update(Request $request, CampeonatoModel $campeonato){

    }
 
    public function destroy(CampeonatoModel $campeonato){

    }
}
