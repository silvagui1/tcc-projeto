<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAcessoMiddleware;
use App\Http\Controllers\ClienteController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\Principal::class, 'principal']);

Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::get('/buscar', [ClienteController::class, 'buscar'])->name('buscar');
    Route::post('/', [ClienteController::class, 'store'])->name('store');
    Route::delete('/', [ClienteController::class, 'destroyMultiple'])->name('destroyMultiple');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
});

// Placeholders temporários (portados de mobilenav_atualizado) — as rotas
// nomeadas 'home'/'estoque.index'/'campeonatos.index'/'vendas'/'config' são
// usadas pela navbar/menu inferior importados de lá (partials/topbar.blade.php
// e partials/bottom-nav.blade.php), mas essas telas ainda não existem nesta
// branch. Redirecionam para Clientes até as branches serem unificadas —
// remover este bloco quando isso acontecer.
Route::get('/inicio', fn () => redirect()->route('clientes.index'))->name('home');
Route::get('/estoque', fn () => redirect()->route('clientes.index'))->name('estoque.index');
Route::get('/campeonatos', fn () => redirect()->route('clientes.index'))->name('campeonatos.index');
Route::get('/vendas', fn () => redirect()->route('clientes.index'))->name('vendas');
Route::get('/config', fn () => redirect()->route('clientes.index'))->name('config');