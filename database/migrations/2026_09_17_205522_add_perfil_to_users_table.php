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
        // Dados que as telas de campeonato mostram na linha do participante
        // (avatar + nome + nascimento). A tabela users já vem da migration padrão.
        Schema::table('users', function (Blueprint $table) {
            $table->date('nascimento')->nullable()->after('name');
            $table->string('avatar', 500)->nullable()->after('nascimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nascimento', 'avatar']);
        });
    }
};
