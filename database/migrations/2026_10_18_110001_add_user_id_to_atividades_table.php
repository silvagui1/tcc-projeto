<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Quem fez": preenchido por Atividade::registrar() com quem está
     * conectado. Fica vazio nas atividades de antes do login e nas
     * tentativas de entrar que falharam.
     */
    public function up(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
