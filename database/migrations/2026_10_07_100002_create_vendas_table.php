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
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            // Cliente opcional — apagar o cliente não apaga a venda.
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->decimal('total', 10, 2)->default(0);
            // Parte do total paga com os créditos do cliente (0 se não usou)
            // e o restante, pago na forma de pagamento escolhida.
            $table->decimal('valor_creditos', 10, 2)->default(0);
            $table->decimal('valor_restante', 10, 2)->default(0);
            // 'dinheiro' | 'pix' | 'debito' | 'credito' (ver Venda::FORMAS_PAGAMENTO);
            // null quando os créditos cobriram a venda inteira.
            $table->string('forma_pagamento', 10)->nullable();
            $table->string('observacoes', 255)->nullable();
            // 'concluida' | 'cancelada' — cancelar devolve estoque e créditos,
            // mas a venda continua no histórico.
            $table->string('status', 10)->default('concluida');
            $table->timestamp('cancelada_em')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas');
    }
};
