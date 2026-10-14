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
        // Categorias "não comercializáveis": os produtos delas ficam no
        // estoque, mas só podem ser dados (brindes, premiações) — não
        // aparecem nem podem ser vendidos na tela de Vendas.
        Schema::table('categorias', function (Blueprint $table) {
            $table->boolean('comercializavel')->default(true)->after('descricao');
        });

        DB::table('categorias')->updateOrInsert(['nome' => 'Não comercializável'], [
            'descricao' => 'Itens que só podem ser dados (brindes, premiações)',
            'comercializavel' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn('comercializavel');
        });
    }
};
