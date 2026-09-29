<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAcessoMiddleware;
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

Route::prefix('/campeonatos')->group(function () {

    // Listar campeonatos
    Route::get('/', [App\Http\Controllers\CampeonatoController::class, 'index'])
        ->name('campeonatos.index');

    // Formulário para criar campeonato
    Route::get('/criar', [App\Http\Controllers\CampeonatoController::class, 'create'])
        ->name('campeonatos.create');

    // Salvar campeonato
    Route::post('/', [App\Http\Controllers\CampeonatoController::class, 'store'])
        ->name('campeonatos.store');

    // Visualizar campeonato
    Route::get('/{campeonato}', [App\Http\Controllers\CampeonatoController::class, 'show'])
        ->name('campeonatos.show');

    // Formulário para editar campeonato
    Route::get('/{campeonato}/editar', [App\Http\Controllers\CampeonatoController::class, 'edit'])
        ->name('campeonatos.edit');

    // Atualizar campeonato
    Route::put('/{campeonato}', [App\Http\Controllers\CampeonatoController::class, 'update'])
        ->name('campeonatos.update');

    // Excluir campeonato
    Route::delete('/{campeonato}', [App\Http\Controllers\CampeonatoController::class, 'destroy'])
        ->name('campeonatos.destroy');




    // Finalizar campeonato
    Route::post('/{campeonato}/finalizar', [App\Http\Controllers\CampeonatoController::class, 'finalizar'])
        ->name('campeonatos.finalizar');


        
    // Ver participantes
    Route::get('/{campeonato}/participantes', [App\Http\Controllers\CampeonatoController::class, 'participantes'])
        ->name('campeonatos.participantes');

    // Adicionar User ao campeonato
    Route::post('/{campeonato}/participantes', [App\Http\Controllers\CampeonatoController::class, 'adicionarParticipante'])
        ->name('campeonatos.participantes.adicionar');

    // Remover User do campeonato
    Route::delete('/{campeonato}/participantes/{user}', [App\Http\Controllers\CampeonatoController::class, 'removerParticipante'])
        ->name('campeonatos.participantes.remover');

});


