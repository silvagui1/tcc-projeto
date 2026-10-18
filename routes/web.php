<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AluguelController;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\CartaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VendaController;
use App\Models\Aluguel;
use App\Models\Produto;
use App\Models\Venda;
/*
|--------------------------------------------------------------------------
| Rotas do sistema
|--------------------------------------------------------------------------
|
| Todas exigem login: o RouteServiceProvider carrega este arquivo com os
| middlewares "auth" e "usuario.ativo". Quem não está conectado vai para
| a tela de login (routes/login.php), a única parte aberta do sistema.
|
*/

Route::get('/', function () {
    return redirect()->route('home');
});

Route::post('/logout', [LoginController::class, 'sair'])->name('logout');

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
// Tudo acontece na lista: os GETs de criar/ver/editar/premiar devolvem o
// conteúdo do pop-up (pedido via fetch) ou redirecionam para a lista com o
// pop-up aberto (?abrir=ID).

Route::prefix('campeonatos')->name('campeonatos.')->group(function () {
    Route::get('/', [CampeonatoController::class, 'index'])->name('index');
    Route::get('/criar', [CampeonatoController::class, 'create'])->name('criar');
    Route::get('/banner.svg', [CampeonatoController::class, 'banner'])->name('banner');
    Route::post('/', [CampeonatoController::class, 'store'])->name('store');
    Route::get('/{campeonato}', [CampeonatoController::class, 'show'])->name('show');
    Route::delete('/{campeonato}', [CampeonatoController::class, 'destroy'])->name('apagar');
    Route::get('/{campeonato}/editar', [CampeonatoController::class, 'edit'])->name('editar');
    Route::put('/{campeonato}/editar', [CampeonatoController::class, 'update'])->name('update');

    // premiação (1°, 2° e 3° lugar) — salvar também finaliza o campeonato
    Route::get('/{campeonato}/premiacoes', [CampeonatoController::class, 'premiacoes'])->name('premiacoes');
    Route::post('/{campeonato}/premiacoes', [CampeonatoController::class, 'finalizar'])->name('premiacoes.salvar');

    // checklist dos eventos oficiais (sanção, Player ID...)
    Route::put('/{campeonato}/requisitos', [CampeonatoController::class, 'requisitos'])->name('requisitos');

    // participantes: inscrever cliente, inscrever provisório ("fantasma"), tirar
    Route::post('/{campeonato}/participantes', [CampeonatoController::class, 'inscrever'])->name('participantes.inscrever');
    Route::post('/{campeonato}/participantes/provisorio', [CampeonatoController::class, 'inscreverProvisorio'])->name('participantes.provisorio');
    Route::delete('/{campeonato}/participantes/{cliente}', [CampeonatoController::class, 'removerParticipante'])->name('participantes.remover');
    // provisório: vira cliente ou não
    Route::post('/{campeonato}/participantes/{cliente}/cadastro', [CampeonatoController::class, 'decidirProvisorio'])->name('participantes.cadastro');

    // pagamento da inscrição — vira uma venda em Vendas (nunca com créditos)
    Route::post('/{campeonato}/participantes/{cliente}/pagamento', [CampeonatoController::class, 'registrarPagamento'])->name('participantes.pagamento');
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

// --- Sua conta (qualquer pessoa conectada) ------------------------------------

Route::prefix('conta')->name('conta.')->controller(ContaController::class)->group(function () {
    Route::put('/email', 'salvarEmail')->name('email');
    Route::put('/senha', 'trocarSenha')->name('senha');
    // obrigatória depois de entrar com senha provisória (middleware senha.trocada)
    Route::get('/criar-senha', 'formCriarSenha')->name('criar-senha');
    Route::put('/criar-senha', 'criarSenha')->name('criar-senha.salvar');
});

// --- Configurações (valem para a loja inteira) ---------------------------------
// A página abre para todos ("Sua conta" e "Aparência"); o resto é só do admin.

Route::get('/config', [ConfiguracaoController::class, 'index'])->name('config');

Route::prefix('config')->name('config')->middleware('can:admin')->group(function () {
    Route::controller(ConfiguracaoController::class)->group(function () {
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

    // contas da equipe
    Route::prefix('usuarios')->name('.usuarios')->controller(UsuarioController::class)->group(function () {
        Route::get('/', 'index')->name('');
        Route::post('/', 'store')->name('.store');
        Route::put('/{usuario}', 'update')->name('.update');
        Route::put('/{usuario}/status', 'status')->name('.status');
        Route::post('/{usuario}/senha', 'redefinirSenha')->name('.senha');
        Route::post('/{usuario}/sessoes', 'encerrarSessoes')->name('.sessoes');
    });
});
