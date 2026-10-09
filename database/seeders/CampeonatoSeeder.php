<?php

namespace Database\Seeders;

use App\Models\Campeonato;
use App\Models\MovimentacaoCredito;
use App\Models\User;
use Illuminate\Database\Seeder;

// Mesmos dados de exemplo que as telas usavam enquanto eram só frontend
class CampeonatoSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn ($arquivo) => 'images/campeonatos/' . $arquivo;

        $clientes = collect([
            ['name' => 'Guilherme Soares', 'nascimento' => '2009-06-20', 'avatar' => $img('avatar-guilherme.jpg')],
            ['name' => 'Maria Garcez', 'nascimento' => '2008-07-29', 'avatar' => $img('avatar-maria.jpg')],
            ['name' => 'Leonardo Pietro', 'nascimento' => '2009-06-24', 'avatar' => $img('avatar-leonardo.jpg')],
            ['name' => 'Elielso Pedroso', 'nascimento' => '1979-06-22', 'avatar' => $img('avatar-elielso.jpg')],
            ['name' => 'Rogério Cartinhas', 'nascimento' => '2009-06-20', 'avatar' => $img('avatar-rogerio.jpg')],
        ])->map(fn ($cliente) => User::factory()->create($cliente));

        $inscritos = $clientes->take(4)->pluck('id');

        $premios = "1° lugar ganha carta e 200 créditos\n2° lugar ganha 100 créditos\n3° lugar ganha 50 créditos";

        Campeonato::create([
            'nome' => 'Torneio Pokemon',
            'status' => 'ativo',
            'data' => '2026-08-22',
            'horario' => '10:30',
            'deck' => 'deck base',
            'valor_inscricao' => 15,
            'descricao' => 'terá prêmios e cartas, dando quantias de crédito para cada ganhador',
            'imagem' => $img('banner-torneio-pokemon.png'),
        ])->participantes()->sync($inscritos);

        foreach (['Pokemon' => 'banner-pokemon.png', 'Magic' => 'banner-magic.png'] as $nome => $banner) {
            $campeonato = Campeonato::create([
                'nome' => $nome,
                'status' => 'finalizado',
                'data' => '2026-08-21',
                'horario' => '10:00',
                'deck' => 'deck ultra max',
                'valor_inscricao' => 15,
                'descricao' => $premios,
                'imagem' => $img($banner),
            ]);
            $campeonato->participantes()->sync($inscritos);

            foreach ([1, 2, 3] as $colocacao) {
                $userId = $inscritos[$colocacao - 1];

                $campeonato->premios()->create(['user_id' => $userId, 'colocacao' => $colocacao, 'valor' => 10]);
                $campeonato->participantes()->updateExistingPivot($userId, ['colocacao' => $colocacao]);

                MovimentacaoCredito::create([
                    'user_id' => $userId,
                    'campeonato_id' => $campeonato->id,
                    'tipo' => 'PREMIO',
                    'valor' => 10,
                    'descricao' => "{$colocacao}° lugar - {$nome}",
                ]);
            }
        }
    }
}
