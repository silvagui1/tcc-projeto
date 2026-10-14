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
        Schema::table('vendas', function (Blueprint $table) {
            // Opcional por padrão; vira obrigatório em Configurações > Vendas.
            $table->string('motivo_cancelamento', 255)->nullable()->after('cancelada_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropColumn('motivo_cancelamento');
        });
    }
};
