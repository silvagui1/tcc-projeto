<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampController extends Controller
{
    function index(){ 

        return view('campeonato.index');
    }

    function add(Request $dados) { 
        $validator = Validator::make(
		      $dados->all(),
	            [
	                'nome' => 'required|min:3|max:255',
	            ],
	            [
	                'nome.required' => 'O campo nome é obrigatório.',
	                'nome.min' => 'O campo nome deve conter no mínimo 3 caracteres.',
	                'nome.max' => 'O campo nome deve conter no máximo 255 caracteres.',
	            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('campeonato.index')
                ->withErrors($validator)
                ->withInput();
        }
        


        $aluno = new \App\Models\AlunoModel();
        $aluno::create($dados->all());

        //RECUPERANDO TODOS ALUNOS DO BANCO E ENVIANDO PARA A VIEW
        $alunos = new \App\Models\AlunoModel();

        return view('aluno.index', ['success'=>'Cadastrado!', 'alunos'=>$alunos::all()]);
    }

    function remove(string $id) {
        $camp = new \App\Models\CampModel();
        $camp::destroy($id);

        return view('campeonato.index', ['success'=>'mostrou!', 'campeonatos'=>$camp::all()]);

    }

    function atualizar(string $id) {
        $camp= new \App\Models\CampModel();
        $camp = $camp::find($id);

        return view('campeonato.atualizar', ['campeonatos'=>$camp]);
    }

    function save(Request $dados) {
        $camp = new \App\Models\CampModel();
        $camp = $camp::find($dados->id);
        $camp->update($dados->all());

        return view('campeonato.index', ['success'=>'salvo!', 'campeonatos'=>$camp::all()]);
    }
}