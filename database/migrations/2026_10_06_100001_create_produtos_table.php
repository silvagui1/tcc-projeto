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
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2)->default(0);
            // A quantidade atual fica no próprio produto (no modelo original
            // era uma tabela "estoque" 1:1 separada); o que mudou e quando
            // fica em produto_movimentacoes.
            $table->unsignedInteger('quantidade')->default(0);
            $table->string('imagem', 500)->nullable();
            // restrictOnDelete: não deixa apagar uma categoria que ainda tem
            // produtos, para nenhum produto ficar "sem categoria".
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
