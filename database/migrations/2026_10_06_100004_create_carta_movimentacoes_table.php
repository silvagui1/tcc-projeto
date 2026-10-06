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
        // Mesmo formato de produto_movimentacoes.
        Schema::create('carta_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carta_id')->constrained('cartas')->cascadeOnDelete();
            // 'entrada' | 'saida' | 'troca' | 'ajuste'
            $table->string('tipo', 10);
            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('quantidade_anterior');
            $table->unsignedInteger('quantidade_nova');
            $table->string('descricao')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carta_movimentacoes');
    }
};
