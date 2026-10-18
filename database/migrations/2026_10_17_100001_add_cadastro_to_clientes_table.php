<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRAGMA foreign_keys não muda dentro de uma transação no SQLite.
     */
    public $withinTransaction = false;

    /**
     * Participante provisório ("fantasma") de campeonato: quem nunca veio à
     * loja entra só com o nome, e no fim a loja decide se ele vira cliente.
     * Por isso a data de nascimento passa a ser opcional (o cadastro fica
     * "incompleto" até alguém preencher em Clientes).
     */
    public function up(): void
    {
        $this->nascimentoOpcional(true);

        Schema::table('clientes', function (Blueprint $table) {
            // 'cliente' (cadastro normal) | 'provisorio' (fantasma esperando a
            // decisão) | 'avulso' (não virou cliente; fica só no histórico do campeonato)
            $table->string('cadastro', 10)->default('cliente')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cadastro');
        });

        $this->nascimentoOpcional(false);
    }

    /**
     * O projeto não tem doctrine/dbal, então o ->change() não está
     * disponível: no MySQL é um MODIFY; no SQLite (testes) a tabela é
     * recriada com a mesma definição, trocando só o NOT NULL.
     */
    private function nascimentoOpcional(bool $opcional): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE clientes MODIFY data_nascimento DATE '.($opcional ? 'NULL' : 'NOT NULL'));

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $sql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'clientes'")->sql;
        $novo = $opcional
            ? preg_replace('/("data_nascimento" date) not null/i', '$1', $sql)
            : preg_replace('/("data_nascimento" date)(?! not null)/i', '$1 not null', $sql);

        DB::statement('PRAGMA foreign_keys = OFF');
        // sem isso o RENAME reescreveria as chaves estrangeiras das outras
        // tabelas para apontar para clientes_antigo
        DB::statement('PRAGMA legacy_alter_table = ON');
        DB::statement('ALTER TABLE clientes RENAME TO clientes_antigo');
        DB::statement($novo);
        DB::statement('INSERT INTO clientes SELECT * FROM clientes_antigo');
        DB::statement('DROP TABLE clientes_antigo');
        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
