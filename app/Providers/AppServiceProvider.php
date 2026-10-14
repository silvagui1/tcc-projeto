<?php

namespace App\Providers;

use App\Services\Configuracoes;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // uma leitura da tabela de configurações por requisição
        $this->app->singleton(Configuracoes::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
