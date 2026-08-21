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
Route::get('/menu', [App\Http\Controllers\Principal::class, 'menu']);

Route::get('/login', [App\Http\Controllers\Login::class, 'login'])->name('login');
Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register'])->name('register');

Route::get('/noticias', [App\Http\Controllers\NoticiaController::class, 'index'])->name('noticias.index');
Route::get('/noticias/{id}', [App\Http\Controllers\NoticiaController::class, 'show'])->name('noticias.show');


