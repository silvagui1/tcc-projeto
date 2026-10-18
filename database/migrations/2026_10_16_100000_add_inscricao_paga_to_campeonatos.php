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
        // Pagamento da inscrição: vira uma venda (tipo de item "inscricao")
        // no histórico de Vendas. Cancelar essa venda deixa a inscrição em
        // aberto de novo (ver VendaService::cancelar).
        Schema::table('campeonato_cliente', function (Blueprint $table) {
            $table->foreignId('venda_id')->nullable()->after('valor_pago')->constrained('vendas')->nullOnDelete();
        });

        Schema::table('venda_itens', function (Blueprint $table) {
            $table->foreignId('campeonato_id')->nullable()->after('aluguel_id')->constrained('campeonatos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venda_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campeonato_id');
        });

        Schema::table('campeonato_cliente', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venda_id');
        });
    }
};
