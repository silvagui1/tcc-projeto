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

Route::get('/', [App\Http\Controllers\Principal::class, 'principal']);




Route::prefix('/campeonato')->group(function(){
    Route::get('/index', [App\Http\Controllers\CampController::class, 'index'])->name('campeonato.index');
    Route::post('/add', [App\Http\Controllers\CampController::class, 'add'])->name('campeonato.add');
    Route::post('/remove', [App\Http\Controllers\CampController::class, 'remove'])->name('campeonato.remove');
    Route::post('/edit', [App\Http\Controllers\CampController::class, 'edit'])->name('campeonato.edit');
    Route::get('/list', [App\Http\Controllers\CampController::class, 'list'])->name('campeonato.list');
}); 

Route::prefix('/premiacao')->group(function(){
    Route::get('/index', [App\Http\Controllers\PremioController::class, 'index'])->name('premiacao.index');    
});