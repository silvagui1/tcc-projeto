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
        Schema::table('clientes', function (Blueprint $table) {
            // Segmentação simples (ver análise de UI/UX): permite marcar um
            // cliente como inativo sem excluí-lo. Default 'ativo' para não
            // exigir migração de dados dos registros existentes.
            $table->string('status', 10)->default('ativo')->after('whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
