<?php

namespace App\Http\Controllers;

use App\Models\AlunoModel;
use Illuminate\Http\Request;

/**
 * CRUD de alunos.
 *
 * Assim como o Principal, este controller era chamado pelas rotas do
 * grupo /aluno mas não existia no repositório. Os métodos abaixo são os
 * cinco que routes/web.php já esperava: index, add, remove, edit e list.
 *
 * O model usado é o App\Models\AlunoModel, que aponta para a tabela
 * "aluno" (ver a migration create_aluno_table) e tem só id e nome
 * como campos preenchíveis.
 */
class AlunoController extends Controller
{
    /**
     * Tela com a lista de alunos e o formulário de cadastro.
     */
    public function index()
    {
        $alunos = AlunoModel::orderBy('nome')->get();

        return view('aluno.index', compact('alunos'));
    }

    /**
     * Mesma listagem, só que em JSON — útil para testar sem abrir a tela.
     */
    public function list()
    {
        return response()->json(AlunoModel::orderBy('nome')->get());
    }

    /**
     * Cadastra um aluno novo.
     */
    public function add(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        AlunoModel::create($dados);

        return redirect()->route('aluno.index')->with('status', 'Aluno cadastrado.');
    }

    /**
     * Altera o nome de um aluno existente.
     */
    public function edit(Request $request)
    {
        $dados = $request->validate([
            'id' => 'required|integer|exists:aluno,id',
            'nome' => 'required|string|max:255',
        ]);

        AlunoModel::where('id', $dados['id'])->update(['nome' => $dados['nome']]);

        return redirect()->route('aluno.index')->with('status', 'Aluno atualizado.');
    }

    /**
     * Apaga um aluno.
     */
    public function remove(Request $request)
    {
        $dados = $request->validate([
            'id' => 'required|integer|exists:aluno,id',
        ]);

        AlunoModel::destroy($dados['id']);

        return redirect()->route('aluno.index')->with('status', 'Aluno removido.');
    }
}
