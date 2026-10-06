<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAcessoMiddleware;
use App\Http\Controllers\AlunoController;
use App\Http\Controllers\CartaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\ProdutoController;
use App\Models\Carta;
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
    return view('pages.home');
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

// Dados de exemplo compartilhados pelas telas de campeonato (lista, acessar,
// editar). Ainda é mock — quando o backend existir, isso vem do banco.
// (function_exists evita erro de "função redeclarada" no route:cache)
if (! function_exists('campeonatosMock')) {
function campeonatosMock()
{
    $img = fn ($arquivo) => asset('images/campeonatos/' . $arquivo);

    $participantes = [
        ['id' => 1, 'nome' => 'Guilherme Soares', 'nascimento' => '20/06/2009', 'avatar' => $img('avatar-guilherme.jpg')],
        ['id' => 2, 'nome' => 'Maria Garcez', 'nascimento' => '29/07/2008', 'avatar' => $img('avatar-maria.jpg')],
        ['id' => 3, 'nome' => 'Leonardo Pietro', 'nascimento' => '24/06/2009', 'avatar' => $img('avatar-leonardo.jpg')],
        ['id' => 4, 'nome' => 'Elielso Pedroso', 'nascimento' => '22/06/1979', 'avatar' => $img('avatar-elielso.jpg')],
    ];

    $vencedores = [
        ['posicao' => '1° Lugar', 'nome' => 'Guilherme Soares', 'credito' => 10.00, 'avatar' => $img('avatar-guilherme.jpg')],
        ['posicao' => '2° Lugar', 'nome' => 'Guilherme Soares', 'credito' => 10.00, 'avatar' => $img('avatar-guilherme.jpg')],
        ['posicao' => '3° Lugar', 'nome' => 'Guilherme Soares', 'credito' => 10.00, 'avatar' => $img('avatar-guilherme.jpg')],
    ];

    $premios = "1° lugar ganha carta e 200 créditos\n2° lugar ganha 100 créditos\n3° lugar ganha 50 créditos";

    return [
        1 => [
            'id' => 1,
            'nome' => 'Torneio Pokemon',
            'jogo' => 'pokemon',
            'status' => 'ativo',
            'data' => '22 de agosto de 2026',
            'dataCurta' => '22/08/2026',
            'horario' => '10:30',
            'deck' => 'deck base',
            'inscricao' => 15.00,
            'descricao' => 'terá prêmios e cartas, dando quantias de crédito para cada ganhador',
            'imagem' => $img('banner-torneio-pokemon.png'),
            'participantesLista' => $participantes,
            'vencedores' => [],
        ],
        2 => [
            'id' => 2,
            'nome' => 'Pokemon',
            'jogo' => 'pokemon',
            'status' => 'finalizado',
            'data' => '21 de agosto de 2026',
            'dataCurta' => '21/08/2026',
            'horario' => '10:00',
            'deck' => 'deck ultra max',
            'inscricao' => 15.00,
            'descricao' => $premios,
            'imagem' => $img('banner-pokemon.png'),
            'participantesLista' => $participantes,
            'vencedores' => $vencedores,
        ],
        3 => [
            'id' => 3,
            'nome' => 'Magic',
            'jogo' => 'magic',
            'status' => 'finalizado',
            'data' => '21 de agosto de 2026',
            'dataCurta' => '21/08/2026',
            'horario' => '10:00',
            'deck' => 'deck ultra max',
            'inscricao' => 15.00,
            'descricao' => $premios,
            'imagem' => $img('banner-magic.png'),
            // o banner do Magic é recortado mais para cima no Figma
            'imagemPosicao' => 'center 35%',
            'participantesLista' => $participantes,
            'vencedores' => $vencedores,
        ],
        4 => [
            'id' => 4,
            'nome' => 'Liga One Piece',
            'jogo' => 'onepiece',
            'status' => 'ativo',
            'data' => '5 de setembro de 2026',
            'dataCurta' => '05/09/2026',
            'horario' => '14:00',
            'deck' => 'deck livre',
            'inscricao' => 20.00,
            'descricao' => 'liga mensal com premiação em booster para os 3 primeiros',
            'imagem' => $img('banner-onepiece.svg'),
            'participantesLista' => array_slice($participantes, 0, 3),
            'vencedores' => [],
        ],
        5 => [
            'id' => 5,
            'nome' => 'Magic Commander',
            'jogo' => 'magic',
            'status' => 'finalizado',
            'data' => '12 de julho de 2026',
            'dataCurta' => '12/07/2026',
            'horario' => '15:00',
            'deck' => 'commander',
            'inscricao' => 25.00,
            'descricao' => $premios,
            'imagem' => $img('banner-magic.png'),
            'imagemPosicao' => 'center 35%',
            'participantesLista' => $participantes,
            'vencedores' => $vencedores,
        ],
        6 => [
            'id' => 6,
            'nome' => 'Liga Pokémon de Julho',
            'jogo' => 'pokemon',
            'status' => 'finalizado',
            'data' => '4 de julho de 2026',
            'dataCurta' => '04/07/2026',
            'horario' => '09:30',
            'deck' => 'deck base',
            'inscricao' => 10.00,
            'descricao' => $premios,
            'imagem' => $img('banner-pokemon.png'),
            'participantesLista' => array_slice($participantes, 1),
            'vencedores' => $vencedores,
        ],
    ];
}
}

Route::get('/campeonatos', function () {
    $todos = campeonatosMock();

    // filtros da barra acima da lista (vêm na url, ex.: ?jogo=magic&status=finalizado)
    $filtros = [
        'busca' => trim((string) request('busca', '')),
        'jogo' => in_array(request('jogo'), Carta::JOGOS) ? request('jogo') : '',
        'status' => in_array(request('status'), ['ativo', 'finalizado']) ? request('status') : '',
        'ordenar' => request('ordenar') === 'antigos' ? 'antigos' : 'recentes',
    ];

    $todos = array_filter($todos, fn ($c) =>
        ($filtros['busca'] === '' || mb_stripos($c['nome'], $filtros['busca']) !== false)
        && ($filtros['jogo'] === '' || $c['jogo'] === $filtros['jogo'])
        && ($filtros['status'] === '' || $c['status'] === $filtros['status'])
    );

    // ordena pela data (dataCurta é dd/mm/aaaa)
    $data = fn ($c) => DateTime::createFromFormat('d/m/Y', $c['dataCurta'])->format('Y-m-d');
    usort($todos, fn ($a, $b) => $filtros['ordenar'] === 'antigos'
        ? strcmp($data($a), $data($b))
        : strcmp($data($b), $data($a)));

    $filtrosAtivos = ($filtros['busca'] !== '') + ($filtros['jogo'] !== '') + ($filtros['status'] !== '');

    // vários ativos viram um carrossel na página principal
    $ativos = array_values(array_filter($todos, fn ($c) => $c['status'] === 'ativo'));
    $outras = array_values(array_filter($todos, fn ($c) => $c['status'] !== 'ativo'));

    $jogosDisponiveis = Carta::JOGOS;

    return view('pages.campeonatos.index', compact('ativos', 'outras', 'filtros', 'filtrosAtivos', 'jogosDisponiveis'));
})->name('campeonatos.index');

Route::get('/campeonatos/criar', function () {
    $avatar = asset('images/campeonatos/avatar-rogerio.jpg');

    $clientesSugeridos = [
        ['id' => 1, 'nome' => 'Rogério Cartinhas', 'nascimento' => '20/06/2009', 'avatar' => $avatar],
        ['id' => 2, 'nome' => 'Roger', 'nascimento' => '11/07/1999', 'avatar' => $avatar],
    ];

    return view('pages.campeonatos.criar', compact('clientesSugeridos'));
})->name('campeonatos.criar');

Route::post('/campeonatos', function () {
    // somente frontend por enquanto — sem persistência ainda.
    return redirect()->route('campeonatos.index');
});

Route::get('/campeonatos/{id}', function ($id) {
    $campeonato = campeonatosMock()[$id] ?? abort(404);

    return view('pages.campeonatos.show', compact('campeonato'));
})->name('campeonatos.show');

Route::delete('/campeonatos/{id}', function ($id) {
    // somente frontend por enquanto — sem persistência ainda.
    return redirect()->route('campeonatos.index');
})->name('campeonatos.apagar');

Route::get('/campeonatos/{id}/editar', function ($id) {
    $campeonato = campeonatosMock()[$id] ?? abort(404);

    return view('pages.campeonatos.editar', compact('campeonato'));
})->name('campeonatos.editar');

Route::put('/campeonatos/{id}/editar', function ($id) {
    // somente frontend por enquanto — sem persistência ainda.
    return redirect()->route('campeonatos.index');
});

Route::get('/campeonatos/{id}/premiacoes', function ($id) {
    // Um bloco por colocação: busca de quem ficou no lugar, quanto de
    // crédito recebe e a descrição do prêmio. Ainda é mock — sem banco.
    $colocacoes = [
        ['posicao' => '1° Lugar', 'valor' => 0.00, 'descricao' => ''],
        ['posicao' => '2° Lugar', 'valor' => 0.00, 'descricao' => ''],
        ['posicao' => '3° Lugar', 'valor' => 0.00, 'descricao' => ''],
    ];

    $participantes = campeonatosMock()[$id]['participantesLista'] ?? [];
    $campeonatoId = $id;

    return view('pages.campeonatos.premiacoes', compact('colocacoes', 'participantes', 'campeonatoId'));
})->name('campeonatos.premiacoes');

Route::post('/campeonatos/{id}/premiacoes', function ($id) {
    // "Finalizar campeonato" — somente frontend por enquanto.
    return redirect()->route('campeonatos.index');
})->name('campeonatos.premiacoes.salvar');


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
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
});

Route::get('/vendas', function () {
    return view('pages.vendas');
})->name('vendas');

Route::get('/config', function () {
    $conta = [
        'usuario' => 'ADM ART PLAY',
        'email' => 'artplay123@gmail.com',
    ];

    return view('pages.config', compact('conta'));
})->name('config');
