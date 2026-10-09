<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;

class PremioController extends Controller
{
    // Tela de premiações: um bloco por colocação (1°, 2° e 3° lugar).
    // O envio do formulário vai para CampeonatoController@finalizar.
    public function index(Campeonato $campeonato){
        $campeonato->load(['participantes' => fn ($q) => $q->orderBy('name'), 'premios']);

        $colocacoes = collect([1, 2, 3])->map(function ($posicao) use ($campeonato) {
            $premio = $campeonato->premios->firstWhere('colocacao', $posicao);

            return [
                'posicao'      => "{$posicao}° Lugar",
                'participante' => $premio?->user_id,
                'valor'        => (float) ($premio?->valor ?? 0),
                'descricao'    => $premio?->descricao ?? '',
            ];
        });

        return view('campeonato.premios', [
            'campeonato'    => $campeonato,
            'participantes' => $campeonato->participantes,
            'colocacoes'    => $colocacoes,
        ]);
    }
}
