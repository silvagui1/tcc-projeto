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
        Schema::create('premios', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

           $table->foreignId('campeonato_id')
            ->constrained('campeonatos')
            ->cascadeOnDelete();

           $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

           $table->unsignedInteger('colocacao');
           $table->decimal('valor', 10, 2)->default(0);
           $table->string('descricao', 250)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premios');
    }
};
