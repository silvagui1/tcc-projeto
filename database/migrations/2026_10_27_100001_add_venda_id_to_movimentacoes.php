<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Venda que causou a movimentação (venda ou cancelamento), para o
     * histórico do item ter um link direto para ela.
     */
    public function up(): void
    {
        foreach (['produto_movimentacoes', 'carta_movimentacoes'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->foreignId('venda_id')->nullable()->after('descricao')->constrained('vendas')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['produto_movimentacoes', 'carta_movimentacoes'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropConstrainedForeignId('venda_id');
            });
        }
    }
};
