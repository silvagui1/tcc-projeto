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
        // Uma reserva de mesa num horário. Aluguel que se repete ("toda
        // segunda às 18:00") vira uma linha por data, todas com o mesmo
        // "serie" — assim cada data pode ser paga ou cancelada sozinha.
        Schema::create('alugueis', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: mesa com reservas é desativada, não apagada.
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            // Cliente é opcional: quem não tem cadastro fica só com o nome
            // do responsável.
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('responsavel', 120)->nullable();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->decimal('valor', 10, 2)->default(0);
            // 'rpg' | 'cartas' | 'tabuleiro' | 'outro' (ver Aluguel::TIPOS_JOGO)
            $table->string('tipo_jogo', 15);
            $table->string('jogo', 120)->nullable();
            $table->uuid('serie')->nullable()->index();
            // 'agendado' | 'pago' | 'cancelado' — vira 'pago' quando o aluguel
            // entra numa venda (ver venda_itens.aluguel_id).
            $table->string('status', 10)->default('agendado');
            $table->string('observacoes', 255)->nullable();
            $table->timestamps();

            $table->index(['mesa_id', 'inicio']);
            $table->index('inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alugueis');
    }
};
