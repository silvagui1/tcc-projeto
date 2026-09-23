<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampController extends Controller
{
    public function index()
{
    // Campeonato(s) em destaque no topo (carrossel se houver mais de um)
    $ativos = CampModel::where('status', 'ativo')
        ->orderBy('data')
        ->get();

    // Lista "Outras competições"
    $finalizados = CampModel::where('status', 'finalizado')
        ->orderBy('data', 'desc')
        ->get();

    return view('campeonato.index', [
        'ativos' => $ativos,
        'finalizados' => $finalizados,
    ]);
}

    function add(Request $dados) { 
        $validator = Validator::make(
		      $dados->all(),
	            [
	                'nome' => 'required',
                    'deck' => 'required', 
                    'data' => 'required',
                    'participantes' => 'required',
                    'valor' => 'required'

	            ],
	            [
	                'nome.required' => 'O campo nome é obrigatório.',
	                'deck.required' => 'O campo deck é obrigatório.',
	                'data.required' => 'O campo data é obrigatório.',
	                'participantes.required' => 'O campo participantes é obrigatório.',
	                'valor.required' => 'O campo valor é obrigatório.',
	            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('campeonato.index')
                ->withErrors($validator)
                ->withInput();
        }
        
        $camp = new \App\Models\CampModel();
        $camp::create($dados->all());

        $camp = new \App\Models\CampModel();

        return view('campeonato.index', ['success'=>'Cadastrado!', 'campeonato'=>$camp::all()]);
    }

    function remove(string $id) {
        $camp = new \App\Models\CampModel();
        $camp::destroy($id);

        return view('campeonato.index', ['success'=>'mostrou!', 'campeonato'=>$camp::all()]);

    }
        public function update(Request $request){
    $camp = CampModel::find($request->id);

    if ($camp->status === 'finalizado') {
        return redirect()
            ->route('campeonato.index')
            ->withErrors(['status' => 'Campeonatos finalizados não podem ser editados.']);
    }

    $validator = $this->validarDados($request);

    if ($validator->fails()) {
        return redirect()
            ->route('campeonato.index')
            ->withErrors($validator)
            ->withInput();
    }

    $camp->update($validator->validated());

    return view('campeonato.index', [
        'success' => 'Salvo!',
        'campeonato' => CampModel::all(),
    ]);
}

    

function save(Request $dados) {
        $camp = new \App\Models\CampModel();
        $camp = $camp::find($dados->id);
        $camp->update($dados->all());

        return view('campeonato.index', ['success'=>'salvo!', 'campeonato'=>$camp::all()]);
    }
}