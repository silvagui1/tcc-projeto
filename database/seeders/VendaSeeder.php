<?php

namespace Database\Seeders;

use App\Models\Aluguel;
use App\Models\Carta;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Produto;
use App\Services\VendaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mesas, aluguéis e vendas de exemplo para a tela de Vendas. As vendas passam
 * pelo VendaService (o mesmo da tela), então baixam o estoque e descontam
 * créditos de verdade. Rodar depois de ClienteSeeder e EstoqueSeeder.
 */
class VendaSeeder extends Seeder
{
    public function run(VendaService $service): void
    {
        $clientes = Cliente::inRandomOrder()->take(6)->get();

        // Mesas e aluguéis de exemplo só numa base sem mesas — com mesas já
        // cadastradas, as reservas fixas abaixo poderiam bater com as reais.
        if (Mesa::count() === 0) {
            $this->criarMesasEAlugueis($clientes);
        }

        $this->criarVendas($service, $clientes);
    }

    private function criarMesasEAlugueis(Collection $clientes): void
    {
        $mesas = collect([['Mesa 1', 6, 15], ['Mesa 2', 6, 15], ['Mesa 3', 4, 10], ['Mesa grande', 10, 25]])
            ->map(fn (array $m) => Mesa::create(['nome' => $m[0], 'capacidade' => $m[1], 'preco_hora' => $m[2]]));

        // Grupo de RPG fixo: toda segunda às 18:00, por 8 semanas.
        $serie = (string) Str::uuid();
        $segunda = today()->startOfWeek(Carbon::MONDAY)->setTime(18, 0);
        for ($semana = 0; $semana < 8; $semana++) {
            $inicio = $segunda->copy()->addWeeks($semana);
            Aluguel::create([
                'mesa_id' => $mesas[0]->id,
                'cliente_id' => $clientes->get(0)?->id,
                'responsavel' => $clientes->get(0) ? null : 'Grupo de RPG',
                'inicio' => $inicio,
                'fim' => $inicio->copy()->addHours(3),
                'valor' => 45,
                'tipo_jogo' => 'rpg',
                'jogo' => 'D&D 5e',
                'serie' => $serie,
            ]);
        }

        // Reservas avulsas de hoje e de amanhã: [dia, mesa, hora, horas, tipo, jogo, cliente, responsável]
        $avulsas = [
            [0, $mesas[1], 14, 2, 'cartas', 'Magic Commander', $clientes->get(1), null],
            [0, $mesas[2], 16, 3, 'tabuleiro', 'Catan', null, 'Lucas'],
            [0, $mesas[3], 19, 4, 'outro', 'Noite de jogos', $clientes->get(2), null],
            [1, $mesas[1], 15, 2, 'cartas', 'Pokémon TCG', $clientes->get(3), null],
        ];

        foreach ($avulsas as [$dias, $mesa, $hora, $horas, $tipo, $jogo, $cliente, $responsavel]) {
            $inicio = today()->addDays($dias)->setTime($hora, 0);
            Aluguel::create([
                'mesa_id' => $mesa->id,
                'cliente_id' => $cliente?->id,
                'responsavel' => $cliente ? null : ($responsavel ?? 'Sem nome'),
                'inicio' => $inicio,
                'fim' => $inicio->copy()->addHours($horas),
                'valor' => $mesa->preco_hora * $horas,
                'tipo_jogo' => $tipo,
                'jogo' => $jogo,
            ]);
        }
    }

    /**
     * Vendas dos últimos dias (data retroativa), algumas com cliente e
     * algumas usando os créditos dele.
     */
    private function criarVendas(VendaService $service, Collection $clientes): void
    {
        $produtos = Produto::where('quantidade', '>', 3)->get();
        $cartas = Carta::where('quantidade', '>', 1)->get();

        if ($produtos->isEmpty()) {
            return;
        }

        $formas = ['dinheiro', 'pix', 'debito', 'credito'];

        for ($i = 0; $i < 14; $i++) {
            $itens = [['tipo' => 'produto', 'id' => $produtos->random()->id, 'quantidade' => rand(1, 2)]];
            if ($cartas->isNotEmpty() && $i % 3 === 0) {
                $itens[] = ['tipo' => 'carta', 'id' => $cartas->random()->id, 'quantidade' => 1];
            }

            $cliente = $i % 2 === 0 && $clientes->isNotEmpty() ? $clientes[$i % $clientes->count()] : null;

            try {
                $venda = $service->registrar([
                    'cliente_id' => $cliente?->id,
                    'itens' => $itens,
                    'usar_creditos' => $cliente && $i % 4 === 0,
                    'forma_pagamento' => $formas[$i % 4],
                ]);
            } catch (ValidationException) {
                continue; // estoque acabou para esse item: pula
            }

            $quando = now()->subDays(intdiv($i, 3))->setTime(rand(10, 20), rand(0, 59));
            $venda->forceFill(['created_at' => $quando, 'updated_at' => $quando])->saveQuietly();
        }
    }
}
