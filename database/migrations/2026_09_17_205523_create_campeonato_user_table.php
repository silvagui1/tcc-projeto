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
        Schema::create('campeonato_user', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('campeonato_id')
                ->constrained('campeonatos')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('valor_pago', 10, 2)->default(0);
            $table->unsignedInteger('colocacao')->nullable();

            $table->unique(['campeonato_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campeonato_user');
    }
};


