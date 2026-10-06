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
        // Mesmo formato de cliente_creditos_historico: cada mudança de
        // quantidade vira um lançamento com o antes e o depois.
        Schema::create('produto_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            // 'entrada' | 'saida' | 'ajuste'
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
        Schema::dropIfExists('produto_movimentacoes');
    }
};
