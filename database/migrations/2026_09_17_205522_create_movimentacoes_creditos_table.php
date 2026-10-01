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
        Schema::create('movimentacoes_creditos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();  

            $table->foreignId('campeonato_id')
                ->constrained('campeonatos')
                ->nullOnDelete()
                ->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('tipo',[
                'PREMIO',
                'BONUS',
                'AJUSTE',
                'UTILIZACAO',
                'ESTORNO'
            ]);

            $table->decimal('valor', 10, 2);
            $table->string('descricao', 350)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_creditos');
    }
};

//o que deve existir nessa página?