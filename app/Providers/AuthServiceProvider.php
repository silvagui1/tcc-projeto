<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Papéis: 'admin' pode tudo; 'intermediario' usa o dia a dia da loja
        // (inclusive cancelar vendas e ajustar créditos). O que for só do
        // admin usa @can('admin') / middleware can:admin.
        Gate::define('admin', fn (User $usuario) => $usuario->isAdmin());
    }
}
