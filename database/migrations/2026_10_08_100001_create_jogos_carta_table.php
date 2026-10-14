<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jogos de carta do estoque (antes fixos em Carta::JOGOS). A coluna
        // cartas.jogo continua guardando a chave ("pokemon"), então as
        // cartas já cadastradas não mudam.
        Schema::create('jogos_carta', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 40)->unique();
            $table->string('nome', 60)->unique();
            $table->timestamps();
        });

        DB::table('jogos_carta')->insert(collect([
            ['pokemon', 'Pokémon'],
            ['magic', 'Magic'],
            ['onepiece', 'One Piece'],
        ])->map(fn (array $jogo) => [
            'chave' => $jogo[0],
            'nome' => $jogo[1],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jogos_carta');
    }
};
