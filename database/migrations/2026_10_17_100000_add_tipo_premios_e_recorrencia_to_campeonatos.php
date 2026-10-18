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
        Schema::table('campeonatos', function (Blueprint $table) {
            // 'liga' | 'challenge' | 'cup' | 'prerelease' (ver Campeonato::TIPOS).
            // Os oficiais têm checklist de burocracia (sanção, Player ID...)
            $table->string('tipo', 12)->default('liga')->after('jogo');
            $table->string('codigo_sancao', 60)->nullable()->after('tipo');
            // chaves do checklist já cumpridas (ver Campeonato::REQUISITOS)
            $table->json('requisitos')->nullable()->after('codigo_sancao');

            // premiação planejada, sempre em créditos (o 1° lugar é obrigatório);
            // a tela de premiação já abre com esses valores
            $table->decimal('premio_1', 10, 2)->default(0)->after('valor_inscricao');
            $table->decimal('premio_2', 10, 2)->default(0)->after('premio_1');
            $table->decimal('premio_3', 10, 2)->default(0)->after('premio_2');
            $table->string('premio_extra', 250)->nullable()->after('premio_3');

            // campeonato que se repete toda semana: ao finalizar, o da semana
            // seguinte já nasce pré-cadastrado. Todas as ocorrências da mesma
            // série dividem o mesmo código.
            $table->boolean('semanal')->default(false)->after('horario');
            $table->string('serie', 36)->nullable()->index()->after('semanal');
        });

        // os banners padrão importados do Figma saem: sem imagem própria, o
        // campeonato passa a usar o banner gerado pelo sistema
        DB::table('campeonatos')->where('imagem', 'like', 'images/campeonatos/banner-%')->update(['imagem' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->dropIndex(['serie']);
            $table->dropColumn(['tipo', 'codigo_sancao', 'requisitos', 'premio_1', 'premio_2', 'premio_3', 'premio_extra', 'semanal', 'serie']);
        });
    }
};
