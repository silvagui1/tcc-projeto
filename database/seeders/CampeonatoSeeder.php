<?php

namespace Database\Seeders;

use App\Models\Campeonato;
use App\Models\Cliente;
use Illuminate\Database\Seeder;

/**
 * Mesmos campeonatos de exemplo que as telas usavam enquanto eram só
 * frontend. Os participantes são clientes, e a premiação dos finalizados
 * entra no saldo deles (com o lançamento no histórico de créditos).
 */
class CampeonatoSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn ($arquivo) => 'images/campeonatos/'.$arquivo;

        $clientes = collect([
            ['nome' => 'Guilherme Soares', 'data_nascimento' => '2009-06-20'],
            ['nome' => 'Maria Garcez', 'data_nascimento' => '2008-07-29'],
            ['nome' => 'Leonardo Pietro', 'data_nascimento' => '2009-06-24'],
            ['nome' => 'Elielso Pedroso', 'data_nascimento' => '1979-06-22'],
            ['nome' => 'Rogério Cartinhas', 'data_nascimento' => '2009-06-20'],
        ])->map(fn ($cliente) => Cliente::factory()->create($cliente + ['status' => 'ativo', 'creditos' => 0]));

        $inscritos = $clientes->take(4)->pluck('id');

        $premios = "1° lugar ganha carta e 200 créditos\n2° lugar ganha 100 créditos\n3° lugar ganha 50 créditos";

        $ativos = [
            ['Torneio Pokemon', 'pokemon', '2026-08-22', '10:30', 'deck base', 15, 'terá prêmios e cartas, dando quantias de crédito para cada ganhador', $img('banner-torneio-pokemon.png'), $inscritos],
            ['Liga One Piece', 'onepiece', '2026-09-05', '14:00', 'deck livre', 20, 'liga mensal com premiação em booster para os 3 primeiros', null, $inscritos->take(3)],
        ];

        foreach ($ativos as [$nome, $jogo, $data, $horario, $deck, $inscricao, $descricao, $imagem, $participantes]) {
            Campeonato::create([
                'nome' => $nome,
                'jogo' => $jogo,
                'status' => 'ativo',
                'data' => $data,
                'horario' => $horario,
                'deck' => $deck,
                'valor_inscricao' => $inscricao,
                'descricao' => $descricao,
                'imagem' => $imagem,
            ])->participantes()->sync($participantes);
        }

        $finalizados = [
            ['Pokemon', 'pokemon', '2026-08-21', '10:00', 'deck ultra max', 15],
            ['Magic', 'magic', '2026-08-21', '10:00', 'deck ultra max', 15],
            ['Magic Commander', 'magic', '2026-07-12', '15:00', 'commander', 25],
        ];

        foreach ($finalizados as [$nome, $jogo, $data, $horario, $deck, $inscricao]) {
            $campeonato = Campeonato::create([
                'nome' => $nome,
                'jogo' => $jogo,
                'status' => 'finalizado',
                'data' => $data,
                'horario' => $horario,
                'deck' => $deck,
                'valor_inscricao' => $inscricao,
                'descricao' => $premios,
            ]);
            $campeonato->participantes()->sync($inscritos);

            foreach ([1, 2, 3] as $colocacao) {
                $cliente = $clientes[$colocacao - 1];

                $campeonato->premios()->create(['cliente_id' => $cliente->id, 'colocacao' => $colocacao, 'valor' => 10]);
                $campeonato->participantes()->updateExistingPivot($cliente->id, ['colocacao' => $colocacao]);
                $cliente->lancarCredito(10, "{$colocacao}° lugar - {$nome}");
            }
        }
    }
}
