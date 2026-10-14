<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            // nome já inclui o número da carta (ex.: "Shiftry 163/162"), como
            // a tela pede — por isso não há coluna "numero" separada.
            $table->string('nome', 150);
            // chave de jogos_carta ('pokemon', 'magic', 'onepiece'...)
            $table->string('jogo', 20);
            $table->string('colecao', 150)->nullable();
            $table->string('raridade', 100)->nullable();
            // "condicao" no modelo original — 'Novo' | 'Semi-Novo' | 'Usado' | 'Danificado'
            $table->string('estado', 20);
            $table->string('idioma', 50);
            $table->boolean('foil')->default(false);
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('preco', 10, 2)->default(0);
            $table->string('imagem', 500)->nullable();
            $table->timestamps();

            $table->index('jogo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
