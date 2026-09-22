<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Tela principal: lista de clientes cadastrados.
     */
    public function index(): View
    {
        $clientes = Cliente::orderBy('nome')->get();

        return view('clientes.index', compact('clientes'));
    }

    /**
     * Busca clientes pelo nome (usada pela barra de busca via AJAX).
     * Retorna apenas o fragmento HTML da lista, para ser injetado na página.
     */
    public function buscar(Request $request): View
    {
        $termo = trim((string) $request->query('nome', ''));

        $clientes = Cliente::when($termo !== '', function ($query) use ($termo) {
                $query->where('nome', 'like', "%{$termo}%");
            })
            ->orderBy('nome')
            ->get();

        return view('clientes.partials._lista', compact('clientes'));
    }

    /**
     * Retorna os dados de um cliente para preencher o modal de edição.
     */
    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'data_nascimento' => $cliente->data_nascimento->format('Y-m-d'),
            'observacoes' => $cliente->observacoes,
            'creditos' => (float) $cliente->creditos,
            'foto_url' => $cliente->foto_url,
            'iniciais' => $cliente->iniciais,
            'cor_avatar' => $cliente->cor_avatar,
        ]);
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

        return response()->json([
            'success' => true,
            'message' => "Cliente {$cliente->nome} cadastrado com sucesso!",
            'id' => $cliente->id,
        ], 201);
    }

    /**
     * Atualiza os dados (e créditos) de um cliente existente.
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

        return response()->json([
            'success' => true,
            'message' => "Dados de {$cliente->nome} atualizados com sucesso!",
            'id' => $cliente->id,
        ]);
    }

    /**
     * Remove (soft delete) um único cliente.
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        $nome = $cliente->nome;
        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => "Cliente {$nome} removido com sucesso!",
        ]);
    }

    /**
     * Remove (soft delete) vários clientes de uma vez, usado pelo modo
     * de seleção acionado pelo botão "apagar clientes".
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
        $quantidade = Cliente::whereIn('id', $ids)->count();
        Cliente::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => $quantidade === 1
                ? '1 cliente removido com sucesso!'
                : "{$quantidade} clientes removidos com sucesso!",
            'ids' => $ids,
        ]);
    }
}
