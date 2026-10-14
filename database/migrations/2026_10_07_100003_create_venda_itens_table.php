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
        Schema::create('venda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            // 'produto' | 'carta' | 'aluguel' — só a referência do tipo vem
            // preenchida. nullOnDelete: apagar um produto do estoque não
            // apaga o que já foi vendido.
            $table->string('tipo', 10);
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->foreignId('carta_id')->nullable()->constrained('cartas')->nullOnDelete();
            $table->foreignId('aluguel_id')->nullable()->constrained('alugueis')->nullOnDelete();
            // Nome e preço copiados no momento da venda: editar o produto
            // depois não muda o que foi vendido.
            $table->string('descricao', 190);
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venda_itens');
    }
};
