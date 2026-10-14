<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAcessoMiddleware;
use App\Http\Controllers\AluguelController;
use App\Http\Controllers\AlunoController;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\CartaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\VendaController;
use App\Models\Aluguel;
use App\Models\JogoCarta;
use App\Models\Produto;
use App\Models\Venda;
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

Route::get('/', function () {
    return redirect()->route('home');
});


// --- Alunos (CRUD) -------------------------------------------------------

Route::prefix('aluno')->name('aluno.')->group(function () {
    Route::get('/', [AlunoController::class, 'index'])->name('index');
    Route::get('/list', [AlunoController::class, 'list'])->name('list');
    Route::post('/add', [AlunoController::class, 'add'])->name('add');
    Route::post('/edit', [AlunoController::class, 'edit'])->name('edit');
    Route::post('/remove', [AlunoController::class, 'remove'])->name('remove');
});


/*
|--------------------------------------------------------------------------
| Rotas do protótipo TCC (frontend)
|--------------------------------------------------------------------------
|
| Telas criadas a partir do protótipo "Protótipo TCC" no Figma.
| Por enquanto é só o frontend: os dados abaixo são só exemplos (mock)
| para as telas terem algo pra mostrar, sem nenhuma ligação com banco
| de dados ainda. Quando o backend for feito, essas rotas passam a
| chamar controllers/models de verdade.
|
*/

Route::get('/inicio', function () {
    // cartões do topo: vendido hoje x ontem (vendas concluídas)
    $hoje = Venda::resumoDoDia(today());
    $ontem = Venda::resumoDoDia(today()->subDay());
    // alerta de estoque baixo (Configurações > Estoque)
    $estoqueBaixo = Produto::estoqueBaixo()->count();
    // mesas reservadas para hoje (inclui as que começaram ontem e passam da meia-noite)
    $alugueisHoje = Aluguel::ativos()
        ->with(['mesa', 'cliente'])
        ->where('inicio', '<', today()->addDay())
        ->where('fim', '>', today())
        ->orderBy('inicio')
        ->get();

    return view('pages.home', compact('hoje', 'ontem', 'estoqueBaixo', 'alugueisHoje'));
})->name('home');


// --- Estoque (produtos + cartas avulsas) ------------------------------------

Route::get('/estoque', [EstoqueController::class, 'index'])->name('estoque.index');

Route::prefix('estoque/produtos')->name('estoque.produtos.')->group(function () {
    Route::post('/', [ProdutoController::class, 'store'])->name('store');
    Route::put('/{produto}', [ProdutoController::class, 'update'])->name('update');
    Route::delete('/{produto}', [ProdutoController::class, 'destroy'])->name('destroy');
});

Route::prefix('estoque/cartas')->name('estoque.cartas')->group(function () {
    Route::get('/', [CartaController::class, 'index'])->name('');
    Route::post('/', [CartaController::class, 'store'])->name('.store');
    Route::get('/{carta}', [CartaController::class, 'show'])->name('.show');
    Route::put('/{carta}', [CartaController::class, 'update'])->name('.update');
    Route::delete('/{carta}', [CartaController::class, 'destroy'])->name('.destroy');
});


// --- Campeonatos ---------------------------------------------------------

Route::prefix('campeonatos')->name('campeonatos.')->group(function () {
    Route::get('/', [CampeonatoController::class, 'index'])->name('index');
    Route::get('/criar', [CampeonatoController::class, 'create'])->name('criar');
    Route::post('/', [CampeonatoController::class, 'store'])->name('store');
    Route::get('/{campeonato}', [CampeonatoController::class, 'show'])->name('show');
    Route::delete('/{campeonato}', [CampeonatoController::class, 'destroy'])->name('apagar');
    Route::get('/{campeonato}/editar', [CampeonatoController::class, 'edit'])->name('editar');
    Route::put('/{campeonato}/editar', [CampeonatoController::class, 'update'])->name('update');

    // premiação (1°, 2° e 3° lugar) — salvar também finaliza o campeonato
    Route::get('/{campeonato}/premiacoes', [CampeonatoController::class, 'premiacoes'])->name('premiacoes');
    Route::post('/{campeonato}/premiacoes', [CampeonatoController::class, 'finalizar'])->name('premiacoes.salvar');

    Route::post('/{campeonato}/participantes', [CampeonatoController::class, 'adicionarParticipante'])->name('participantes.store');
    Route::delete('/{campeonato}/participantes/{cliente}', [CampeonatoController::class, 'removerParticipante'])->name('participantes.destroy');
});


// --- Outras páginas do menu ----------------------------------------------

// --- Clientes (CRUD + busca, filtros, créditos) --------------------------------

Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::get('/buscar', [ClienteController::class, 'buscar'])->name('buscar');
    Route::get('/exportar', [ClienteController::class, 'exportar'])->name('exportar');
    Route::post('/', [ClienteController::class, 'store'])->name('store');
    Route::delete('/', [ClienteController::class, 'destroyMultiple'])->name('destroyMultiple');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::post('/{cliente}/creditos', [ClienteController::class, 'ajustarCreditos'])->name('creditos');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
});

// --- Vendas (histórico, nova venda) + aluguéis de mesas -------------------------

Route::prefix('vendas')->name('vendas.')->group(function () {
    Route::get('/', [VendaController::class, 'index'])->name('index');
    Route::get('/listar', [VendaController::class, 'listar'])->name('listar');
    Route::get('/catalogo', [VendaController::class, 'catalogo'])->name('catalogo');
    Route::get('/clientes', [VendaController::class, 'clientes'])->name('clientes');
    Route::post('/', [VendaController::class, 'store'])->name('store');

    Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
    Route::post('/mesas', [MesaController::class, 'store'])->name('mesas.store');
    Route::put('/mesas/{mesa}', [MesaController::class, 'update'])->name('mesas.update');
    Route::delete('/mesas/{mesa}', [MesaController::class, 'destroy'])->name('mesas.destroy');

    Route::post('/alugueis', [AluguelController::class, 'store'])->name('alugueis.store');
    Route::get('/alugueis/{aluguel}', [AluguelController::class, 'show'])->name('alugueis.show');
    Route::put('/alugueis/{aluguel}', [AluguelController::class, 'update'])->name('alugueis.update');
    Route::post('/alugueis/{aluguel}/cancelar', [AluguelController::class, 'cancelar'])->name('alugueis.cancelar');

    Route::get('/{venda}', [VendaController::class, 'show'])->whereNumber('venda')->name('show');
    Route::post('/{venda}/cancelar', [VendaController::class, 'cancelar'])->whereNumber('venda')->name('cancelar');
});

// --- Configurações (valem para a loja inteira; ainda sem login) ------------------

Route::prefix('config')->name('config')->controller(ConfiguracaoController::class)->group(function () {
    Route::get('/', 'index')->name('');

    Route::put('/loja', 'salvarLoja')->name('.loja');
    Route::post('/loja/logo', 'enviarLogo')->name('.logo');
    Route::delete('/loja/logo', 'removerLogo')->name('.logo.remover');
    Route::put('/vendas', 'salvarVendas')->name('.vendas');
    Route::put('/alugueis', 'salvarAlugueis')->name('.alugueis');
    Route::put('/estoque', 'salvarEstoque')->name('.estoque');
    Route::put('/clientes', 'salvarClientes')->name('.clientes');

    Route::post('/categorias', 'salvarCategoria')->name('.categorias.store');
    Route::put('/categorias/{categoria}', 'salvarCategoria')->name('.categorias.update');
    Route::delete('/categorias/{categoria}', 'excluirCategoria')->name('.categorias.destroy');
    Route::post('/jogos-carta', 'salvarJogo')->name('.jogos.store');
    Route::put('/jogos-carta/{jogoCarta}', 'salvarJogo')->name('.jogos.update');
    Route::delete('/jogos-carta/{jogoCarta}', 'excluirJogo')->name('.jogos.destroy');

    Route::get('/atividades', 'atividades')->name('.atividades');
    Route::get('/exportar/vendas', 'exportarVendas')->name('.exportar.vendas');
    Route::get('/exportar/estoque', 'exportarEstoque')->name('.exportar.estoque');
});
