<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarMesaRequest;
use App\Models\Mesa;
use Illuminate\Http\JsonResponse;

/**
 * Cadastro das mesas que a loja aluga (modal "Mesas" da tela de Vendas).
 * Toda resposta devolve a lista atualizada, para o modal se redesenhar.
 */
class MesaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['mesas' => $this->lista()]);
    }

    public function store(SalvarMesaRequest $request): JsonResponse
    {
        $mesa = Mesa::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => "{$mesa->nome} cadastrada.",
            'mesas' => $this->lista(),
        ], 201);
    }

    public function update(SalvarMesaRequest $request, Mesa $mesa): JsonResponse
    {
        $mesa->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => $mesa->wasChanged('ativa') && ! $mesa->ativa
                ? "{$mesa->nome} desativada. Ela não aparece mais para novos aluguéis."
                : "{$mesa->nome} atualizada.",
            'mesas' => $this->lista(),
        ]);
    }

    /**
     * Só apaga mesas sem nenhuma reserva no histórico; as outras são
     * desativadas (o histórico de aluguéis continua apontando para elas).
     */
    public function destroy(Mesa $mesa): JsonResponse
    {
        if ($mesa->alugueis()->exists()) {
            return response()->json([
                'message' => "{$mesa->nome} tem reservas no histórico e não pode ser excluída. Desative-a para ela sair dos novos aluguéis.",
            ], 422);
        }

        $nome = $mesa->nome;
        $mesa->delete();

        return response()->json([
            'success' => true,
            'message' => "{$nome} excluída.",
            'mesas' => $this->lista(),
        ]);
    }

    private function lista(): array
    {
        return Mesa::orderBy('id')->get()->map(fn (Mesa $mesa) => $mesa->dadosJson())->all();
    }
}
