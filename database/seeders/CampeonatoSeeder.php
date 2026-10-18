<?php

namespace Database\Seeders;

use App\Models\Campeonato;
use App\Models\Cliente;
use Illuminate\Database\Seeder;

/**
 * Campeonatos de exemplo: uma liga semanal, um oficial (Challenge) em
 * andamento e três finalizados. Os participantes são clientes (e um
 * provisório na liga), e a premiação dos finalizados entra no saldo deles
 * (com o lançamento no histórico de créditos). Sem imagem própria, todos
 * usam o banner do sistema.
 */
class CampeonatoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = collect([
            ['nome' => 'Guilherme Soares', 'data_nascimento' => '2009-06-20'],
            ['nome' => 'Maria Garcez', 'data_nascimento' => '2008-07-29'],
            ['nome' => 'Leonardo Pietro', 'data_nascimento' => '2009-06-24'],
            ['nome' => 'Elielso Pedroso', 'data_nascimento' => '1979-06-22'],
            ['nome' => 'Rogério Cartinhas', 'data_nascimento' => '2009-06-20'],
        ])->map(fn ($cliente) => Cliente::factory()->create($cliente + ['status' => 'ativo', 'creditos' => 0]));

        $inscritos = $clientes->take(4)->pluck('id');

        // liguinha de toda quinta: ao finalizar, a da semana seguinte é criada
        $liga = Campeonato::create([
            'nome' => 'Liguinha Pokémon de quinta',
            'jogo' => 'pokemon',
            'tipo' => 'liga',
            'status' => 'ativo',
            'data' => today()->next('Thursday'),
            'horario' => '18:00',
            'semanal' => true,
            'serie' => Campeonato::novaSerie(),
            'deck' => 'Padrão (Standard)',
            'valor_inscricao' => 10,
            'premio_1' => 30,
            'premio_2' => 15,
            'premio_3' => 5,
            'descricao' => 'Partidas de 30 minutos, check-in até 17:45.',
            'requisitos' => [],
        ]);
        $liga->participantes()->sync($inscritos->take(3));
        $liga->participantes()->attach(Cliente::criarProvisorio('Visitante da Liga')->id);

        Campeonato::create([
            'nome' => 'Challenge One Piece',
            'jogo' => 'onepiece',
            'tipo' => 'challenge',
            'codigo_sancao' => 'OP-26-0917',
            'status' => 'ativo',
            'data' => today()->addDays(10),
            'horario' => '14:00',
            'deck' => 'Padrão (Standard)',
            'valor_inscricao' => 20,
            'premio_1' => 100,
            'premio_2' => 50,
            'premio_3' => 25,
            'premio_extra' => 'Booster box da coleção nova',
            'requisitos' => ['sancao'],
        ])->participantes()->sync($inscritos);

        $finalizados = [
            ['Pokemon', 'pokemon', 'challenge', '2026-08-21', '10:00', 'Expandido', 15],
            ['Magic', 'magic', 'liga', '2026-08-21', '10:00', 'Commander', 15],
            ['Magic Commander', 'magic', 'cup', '2026-07-12', '15:00', 'Commander', 25],
        ];

        foreach ($finalizados as [$nome, $jogo, $tipo, $data, $horario, $deck, $inscricao]) {
            $campeonato = Campeonato::create([
                'nome' => $nome,
                'jogo' => $jogo,
                'tipo' => $tipo,
                'status' => 'finalizado',
                'data' => $data,
                'horario' => $horario,
                'deck' => $deck,
                'valor_inscricao' => $inscricao,
                'premio_1' => 10,
                'premio_2' => 10,
                'premio_3' => 10,
                'requisitos' => Campeonato::requisitosDoTipo($tipo),
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
