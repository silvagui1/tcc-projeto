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
        // Configurações gerais da loja (tela Configurações), no formato
        // chave → valor (json). Só ficam aqui as chaves que alguém alterou:
        // as que faltam usam o padrão de App\Services\Configuracoes::PADROES.
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->string('chave', 80)->primary();
            $table->json('valor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
