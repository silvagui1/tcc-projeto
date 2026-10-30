<?php

namespace App\Providers;

use App\Services\Configuracoes;
use App\View\Composers\CabecalhoDoEstoque;
use Illuminate\Support\Facades\View;
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
        // cabeçalho das abas Produtos e Cartas do estoque
        View::composer('pages.estoque.partials.cabecalho', CabecalhoDoEstoque::class);
    }
}
