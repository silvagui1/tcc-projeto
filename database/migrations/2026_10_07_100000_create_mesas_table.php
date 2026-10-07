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
        // Mesas que a loja aluga para partidas (RPG, cartas, tabuleiro...).
        // Cadastradas pela própria tela de Vendas (botão "Mesas").
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->unsignedTinyInteger('capacidade')->default(4);
            $table->decimal('preco_hora', 10, 2)->default(0);
            // Mesa com reservas no histórico não pode ser apagada (o histórico
            // perderia a referência) — ela é desativada: some do formulário de
            // novo aluguel, mas as reservas antigas continuam mostrando o nome.
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
