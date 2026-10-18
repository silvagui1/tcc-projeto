<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contas dos funcionários da loja. A tabela "users" padrão do Laravel
     * nunca foi usada (o sistema não tinha login), então é recriada no
     * formato do sistema em vez de alterada coluna a coluna — o projeto não
     * tem doctrine/dbal para o ->change().
     *
     * - o login é pelo nome de usuário; o e-mail é opcional (a pessoa vincula
     *   depois de entrar, se quiser)
     * - não existe cadastro aberto: contas são criadas pelo admin (o
     *   primeiro, pelo comando `php artisan loja:criar-admin`)
     * - quem sai da loja é desativado, não apagado, para o histórico continuar
     *   mostrando quem fez o quê
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && DB::table('users')->exists()) {
            throw new RuntimeException('A tabela users já tem contas; recriá-la apagaria essas contas.');
        }

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('usuario', 30)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            // 'admin' | 'intermediario' (ver App\Models\User::PAPEIS)
            $table->string('papel', 20)->default('intermediario');
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamp('ultimo_login_em')->nullable();
            $table->string('ultimo_login_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Volta para a tabela padrão do Laravel.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
};
