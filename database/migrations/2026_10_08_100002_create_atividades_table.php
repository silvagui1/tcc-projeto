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
        // Registro do que foi feito no sistema e quando ("Venda #12
        // cancelada"). Ainda sem "quem": o sistema não tem login — quando
        // tiver, entra uma coluna user_id aqui.
        Schema::create('atividades', function (Blueprint $table) {
            $table->id();
            // 'vendas' | 'alugueis' | 'clientes' | 'estoque' | 'configuracoes'
            $table->string('area', 20)->index();
            $table->string('descricao', 255);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividades');
    }
};
