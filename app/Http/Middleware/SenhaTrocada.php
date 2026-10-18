<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem entrou com uma senha provisória (conta nova ou senha redefinida pelo
 * admin) só consegue criar a própria senha ou sair — o resto do sistema
 * fica bloqueado até lá.
 */
class SenhaTrocada
{
    private const LIBERADAS = ['conta.criar-senha', 'conta.criar-senha.salvar', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->deve_trocar_senha && ! $request->routeIs(...self::LIBERADAS)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Crie a sua senha antes de continuar.'], 403);
            }

            return redirect()->route('conta.criar-senha');
        }

        return $next($request);
    }
}
