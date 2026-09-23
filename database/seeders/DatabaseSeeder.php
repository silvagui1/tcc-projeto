<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // FornecedorSeeder/ContatoSeeder (chamados aqui antes) não existem
        // no projeto — resíduo de outra atividade, quebrava `db:seed` por
        // completo com "class not found". Removidos.
        $this->call(ClienteSeeder::class);
    }
}
