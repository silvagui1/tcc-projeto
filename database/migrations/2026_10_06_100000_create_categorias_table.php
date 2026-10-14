<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Equivale à tabela "classe" do modelo original do banco — renomeada
        // para "categorias" porque é assim que a tela de estoque chama.
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        // Categorias que a tela já usava nos dados de exemplo — sem elas o
        // formulário de produto nasceria sem nenhuma opção.
        DB::table('categorias')->insert(collect(['Comida', 'Bebida', 'Cartas', 'Acessórios'])
            ->map(fn (string $nome) => ['nome' => $nome, 'created_at' => now(), 'updated_at' => now()])
            ->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
