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
     * Como ainda não existe uma home própria, redireciona para a
     * única tela funcional do sistema até que uma exista.
     */
    public function principal(): RedirectResponse
    {
        return redirect()->route('clientes.index');
    }
}
