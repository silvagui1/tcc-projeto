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
        Schema::create('campeonatos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('nome');
            $table->string('deck');
            $table->date('data');
            $table->time('horario')->nullable();
            $table->decimal('valor_inscricao', 10, 2)->default(0); 
            $table->string('imagem', 500)->nullable();
            $table->string('descricao', 350)->nullable();
            $table->enum('status', ['ativo', 'finalizado'])->default('ativo');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campeonatos');
    }
};
