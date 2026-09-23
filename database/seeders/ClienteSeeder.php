<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Cria clientes fake para alimentar e testar a tela (busca, paginação
     * de 20 em 20, badge de créditos zerados etc.). 45 registros rendem 3
     * páginas (20 + 20 + 5).
     */
    public function run(): void
    {
        Cliente::factory()->count(40)->create();
        Cliente::factory()->count(5)->semCreditos()->create();
    }
}
