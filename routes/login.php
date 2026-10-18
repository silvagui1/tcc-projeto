<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
|
| As únicas rotas que não exigem login (ver RouteServiceProvider). Não
| existe rota de cadastro: o sistema é interno da loja e as contas dos
| funcionários são criadas pelo admin.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'entrar'])->middleware('throttle:login')->name('login.entrar');
});
