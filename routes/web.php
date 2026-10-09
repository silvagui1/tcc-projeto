<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\PremioController;
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

Route::redirect('/', '/campeonatos');

// Páginas que a navbar e o menu inferior linkam, mas que ainda não foram
// feitas nesta branch. Mostram um aviso de "em breve" no lugar do erro de
// rota inexistente.
Route::view('/inicio', 'em-breve', ['titulo' => 'Início'])->name('home');
Route::view('/clientes', 'em-breve', ['titulo' => 'Clientes'])->name('clientes');
Route::view('/estoque', 'em-breve', ['titulo' => 'Estoque'])->name('estoque.index');
Route::view('/vendas', 'em-breve', ['titulo' => 'Vendas'])->name('vendas');
Route::view('/configuracoes', 'em-breve', ['titulo' => 'Configurações'])->name('config');

Route::prefix('/campeonatos')->group(function () {

    // Listar campeonatos
    Route::get('/', [CampeonatoController::class, 'index'])
        ->name('campeonatos.index');

    // Formulário para criar campeonato
    Route::get('/criar', [CampeonatoController::class, 'create'])
        ->name('campeonatos.create');

    // Salvar campeonato
    Route::post('/', [CampeonatoController::class, 'store'])
        ->name('campeonatos.store');

    // Visualizar campeonato
    Route::get('/{campeonato}', [CampeonatoController::class, 'show'])
        ->name('campeonatos.show');

    // Formulário para editar campeonato
    Route::get('/{campeonato}/editar', [CampeonatoController::class, 'edit'])
        ->name('campeonatos.edit');

    // Atualizar campeonato
    Route::put('/{campeonato}', [CampeonatoController::class, 'update'])
        ->name('campeonatos.update');

    // Excluir campeonato
    Route::delete('/{campeonato}', [CampeonatoController::class, 'destroy'])
        ->name('campeonatos.destroy');



    // Tela de premiações (1°, 2° e 3° lugar)
    Route::get('/{campeonato}/premios', [PremioController::class, 'index'])
        ->name('campeonatos.premios');

    // Finalizar campeonato (salva as premiações da tela acima)
    Route::post('/{campeonato}/finalizar', [CampeonatoController::class, 'finalizar'])
        ->name('campeonatos.finalizar');



    // Ver participantes
    Route::get('/{campeonato}/participantes', [CampeonatoController::class, 'participantes'])
        ->name('campeonatos.participantes');

    // Adicionar User ao campeonato
    Route::post('/{campeonato}/participantes', [CampeonatoController::class, 'adicionarParticipante'])
        ->name('campeonatos.participantes.adicionar');

    // Remover User do campeonato
    Route::delete('/{campeonato}/participantes/{user}', [CampeonatoController::class, 'removerParticipante'])
        ->name('campeonatos.participantes.remover');

});
