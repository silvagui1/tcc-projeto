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
        Schema::table('cliente_creditos_historico', function (Blueprint $table) {
            // Motivo opcional do lançamento (ex.: "compra no balcão"). Lançamentos
            // antigos ficam sem motivo.
            $table->string('motivo', 120)->nullable()->after('saldo_novo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cliente_creditos_historico', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
