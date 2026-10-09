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
        Schema::create('cliente_creditos_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            // 'adicionar' | 'descontar' | 'definir' (ajuste direto do saldo,
            // sem somar/subtrair — ver botão "Definir saldo" no modal).
            $table->string('tipo', 10);
            $table->decimal('valor', 10, 2);
            $table->decimal('saldo_anterior', 10, 2);
            $table->decimal('saldo_novo', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_creditos_historico');
    }
};
