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
            $table->string('nome');
            $table->string('deck');
            $table->date('data');
            $table->time('horario')->nullable();
            $table->decimal('valor', 10, 2); // valor da inscrição
            $table->text('descricao')->nullable();
            $table->string('imagem', 500)->nullable(); // url do banner
            $table->string('status')->default('ativo'); // ativo | finalizado
            $table->timestamps();
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
