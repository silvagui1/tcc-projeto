<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Atividade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Entrar e sair do sistema. Não existe tela de cadastro: as contas dos
 * funcionários são criadas pelo admin em Configurações > Usuários.
 */
class LoginController extends Controller
{
    public function form(): View
    {
        return view('auth.login');
    }

    public function entrar(LoginRequest $request): RedirectResponse
    {
        $request->autenticar();

        // id de sessão novo a cada login (evita fixação de sessão)
        $request->session()->regenerate();

        $usuario = $request->user();
        $usuario->forceFill([
            'ultimo_login_em' => now(),
            'ultimo_login_ip' => $request->ip(),
        ])->save();

        Atividade::registrar('acesso', "{$usuario->nome} entrou no sistema");

        return redirect()->intended(route('home'));
    }

    public function sair(Request $request): RedirectResponse
    {
        // registrado antes de sair, para a atividade guardar quem foi
        Atividade::registrar('acesso', "{$request->user()->nome} saiu do sistema");

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('aviso', 'Você saiu do sistema.');
    }
}
