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
        // Clientes inscritos em cada campeonato.
        Schema::create('campeonato_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->decimal('valor_pago', 10, 2)->default(0);
            // preenchida ao finalizar o campeonato (1, 2 ou 3)
            $table->unsignedTinyInteger('colocacao')->nullable();
            $table->timestamps();

            $table->unique(['campeonato_id', 'cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campeonato_cliente');
    }
};
