<?php

namespace Tests\Feature;

use App\Models\Aluguel;
use App\Models\Mesa;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Página inicial: diferença de vendas para ontem e mesas reservadas hoje.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    private function venda(float $total, Carbon $quando): void
    {
        $venda = Venda::create(['total' => $total, 'valor_creditos' => 0, 'valor_restante' => $total, 'forma_pagamento' => 'pix']);
        $venda->forceFill(['created_at' => $quando])->save();
    }

    private function aluguel(Mesa $mesa, Carbon $inicio, Carbon $fim, string $responsavel, string $status = 'agendado'): void
    {
        Aluguel::create([
            'mesa_id' => $mesa->id, 'responsavel' => $responsavel, 'inicio' => $inicio, 'fim' => $fim,
            'valor' => 30, 'tipo_jogo' => 'rpg', 'status' => $status,
        ]);
    }

    public function test_diferenca_positiva_e_negativa(): void
    {
        $this->venda(50, today()->subDay()->setTime(12, 0));
        $this->venda(80, now());
        $this->get('/inicio')->assertOk()->assertSee('home-diferenca--positiva')->assertSee('+R$ 30,00');

        $this->venda(100, today()->subDay()->setTime(13, 0));
        $this->get('/inicio')->assertSee('home-diferenca--negativa')->assertSee('−R$ 70,00');
    }

    public function test_mostra_as_mesas_reservadas_hoje(): void
    {
        $this->travelTo(today()->setTime(15, 0));
        $mesa = Mesa::create(['nome' => 'Mesa 1', 'capacidade' => 4, 'preco_hora' => 10]);

        $this->aluguel($mesa, today()->setTime(18, 0), today()->setTime(21, 0), 'Grupo do João');
        $this->aluguel($mesa, today()->setTime(14, 0), today()->setTime(16, 0), 'Ana');
        $this->aluguel($mesa, today()->addDay()->setTime(18, 0), today()->addDay()->setTime(20, 0), 'Amanhã');
        $this->aluguel($mesa, today()->setTime(10, 0), today()->setTime(11, 0), 'Cancelado', 'cancelado');

        $this->get('/inicio')
            ->assertOk()
            ->assertSeeInOrder(['14:00', 'Mesa 1 · Ana', 'Agora', '18:00', 'até 21:00', 'Mesa 1 · Grupo do João'])
            ->assertDontSee('Amanhã')
            ->assertDontSee('Mesa 1 · Cancelado');
    }

    public function test_sem_mesas_hoje(): void
    {
        $this->get('/inicio')->assertOk()->assertSee('Nenhuma mesa reservada para hoje.');
    }
}
