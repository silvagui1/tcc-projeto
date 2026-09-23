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
     * Quantos clientes carregar por página (lista e busca).
     */
    private const CLIENTES_POR_PAGINA = 20;

    /**
     * Tela principal: lista de clientes cadastrados (paginada).
     */
    public function index(): View
    {
        $clientes = Cliente::orderBy('nome')->paginate(self::CLIENTES_POR_PAGINA);

        return view('clientes.index', compact('clientes'));
    }

    /**
     * Busca clientes pelo nome (usada pela barra de busca via AJAX) e também
     * atende a troca de página da lista (com ou sem termo de busca).
     * Retorna apenas o fragmento HTML da lista, para ser injetado na página.
     */
    public function buscar(Request $request): View
    {
        $termo = trim((string) $request->query('nome', ''));

        $clientes = Cliente::when($termo !== '', function ($query) use ($termo) {
                $query->where('nome', 'like', "%{$termo}%");
            })
            ->orderBy('nome')
            ->paginate(self::CLIENTES_POR_PAGINA)
            ->withQueryString();

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
     * Remove definitivamente um único cliente (e sua foto, se houver).
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        $nome = $cliente->nome;

        if ($cliente->foto) {
            Storage::disk('public')->delete($cliente->foto);
        }

        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => "Cliente {$nome} removido com sucesso!",
        ]);
    }

    /**
     * Remove definitivamente vários clientes de uma vez (e as fotos deles,
     * se houver), usado pelo modo de seleção acionado pelo botão "apagar
     * clientes".
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
        $clientes = Cliente::whereIn('id', $ids)->get(['id', 'foto']);

        foreach ($clientes as $cliente) {
            if ($cliente->foto) {
                Storage::disk('public')->delete($cliente->foto);
            }
        }

        Cliente::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => $clientes->count() === 1
                ? '1 cliente removido com sucesso!'
                : "{$clientes->count()} clientes removidos com sucesso!",
            'ids' => $ids,
        ]);
    }
}
