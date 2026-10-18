<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem for desativado enquanto estava conectado (inclusive pelo "Manter
 * conectado") cai fora na próxima página que abrir.
 */
class UsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sua conta foi desativada.'], 401);
            }

            return redirect()->route('login')->withErrors(['usuario' => 'Sua conta foi desativada. Fale com o administrador da loja.']);
        }

        return $next($request);
    }
}
