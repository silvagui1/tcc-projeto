<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class Principal extends Controller
{
    /**
     * Página inicial do sistema.
     *
     * A rota "/" já existia em routes/web.php apontando para este
     * controller, mas a classe nunca havia sido criada (quebrava
     * qualquer requisição à raiz e comandos como `route:list`).
     * Agora a home existe (rota "home", /inicio), então redireciona
     * para ela.
     */
    public function principal(): RedirectResponse
    {
        return redirect()->route('home');
    }
}
