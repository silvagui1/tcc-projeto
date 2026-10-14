<?php

namespace Tests\Feature;

use App\Models\Carta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paginação do catálogo de cartas (11 por página = 3 fileiras no desktop).
 */
class CartasPaginacaoTest extends TestCase
{
    use RefreshDatabase;

    private function cartas(string $jogo, int $quantidade): void
    {
        for ($i = 1; $i <= $quantidade; $i++) {
            Carta::create([
                'nome' => sprintf('%s %02d', $jogo, $i),
                'jogo' => $jogo,
                'estado' => 'Novo',
                'idioma' => 'Português',
                'quantidade' => 1,
                'preco' => 5,
            ]);
        }
    }

    public function test_ate_11_cartas_nao_mostra_paginacao(): void
    {
        $this->cartas('pokemon', 11);

        $this->get('/estoque/cartas?jogo=pokemon')
            ->assertOk()
            ->assertSee('11 cartas de Pokémon')
            ->assertDontSee('cartas-paginacao');
    }

    public function test_pagina_as_cartas_dos_tres_jogos(): void
    {
        foreach (['pokemon', 'magic', 'onepiece'] as $jogo) {
            $this->cartas($jogo, 25);

            $primeira = $this->get("/estoque/cartas?jogo={$jogo}&ordenar=nome")->assertOk();
            $primeira->assertSee('25 cartas de')->assertSee('cartas-paginacao');
            $this->assertCount(11, $primeira->viewData('cartas')->items());
            $this->assertSame(3, $primeira->viewData('cartas')->lastPage());

            // os links de página mantêm o jogo e a ordenação escolhidos
            $primeira->assertSee("jogo={$jogo}&amp;ordenar=nome&amp;page=2", false);

            $ultima = $this->get("/estoque/cartas?jogo={$jogo}&ordenar=nome&page=3")->assertOk();
            $this->assertSame(["{$jogo} 23", "{$jogo} 24", "{$jogo} 25"], collect($ultima->viewData('cartas')->items())->pluck('nome')->all());
        }
    }
}
