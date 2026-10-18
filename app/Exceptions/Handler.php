<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        // nomes usados no sistema (login, "Sua conta" e "Crie a sua senha"):
        // nunca voltam para o formulário nem ficam guardados na sessão
        'senha',
        'senha_atual',
        'senha_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Formulário enviado com a sessão já expirada (ex.: tela de login
        // aberta desde ontem): em vez da página "419", volta para o login
        // com um aviso. Chamadas via fetch continuam recebendo o 419.
        $this->renderable(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('login')->with('aviso', 'A sessão expirou. Entre de novo para continuar.');
        });
    }
}
