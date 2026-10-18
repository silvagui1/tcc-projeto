<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "aluno" e "testes" eram de atividades antigas da disciplina e não são
     * usadas pelo sistema da loja. As migrations que criavam as duas foram
     * apagadas; esta remove as tabelas dos bancos que já as tinham.
     */
    public function up(): void
    {
        Schema::dropIfExists('aluno');
        Schema::dropIfExists('testes');
    }

    /**
     * Não há volta: as tabelas não fazem parte do sistema.
     */
    public function down(): void
    {
        //
    }
};
