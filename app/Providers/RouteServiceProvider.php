<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/inicio';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // única parte aberta: a tela de login
            Route::middleware('web')
                ->group(base_path('routes/login.php'));

            // todo o sistema exige login — rota nova em web.php já nasce
            // protegida, sem precisar lembrar de colocar o middleware.
            // auth.session: trocar a senha derruba as sessões dos outros aparelhos
            Route::middleware(['web', 'auth', 'auth.session', 'usuario.ativo', 'senha.trocada'])
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // além do bloqueio por usuário (App\Http\Requests\LoginRequest), um
        // mesmo IP não passa de 20 tentativas de login por minuto
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
