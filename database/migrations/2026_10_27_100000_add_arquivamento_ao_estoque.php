<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Apagar" um produto ou carta passa a arquivar (soft delete): o item sai
     * das listas e das vendas, mas o histórico de movimentações e as vendas
     * antigas continuam apontando para ele, e dá para restaurar.
     */
    public function up(): void
    {
        foreach (['produtos', 'cartas'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (['produtos', 'cartas'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
