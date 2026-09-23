<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogAcessoMiddleware;
use App\Http\Controllers\AlunoController;
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


// --- Estoque -----------------------------------------------------------

Route::get('/estoque', function () {
    $tab = request('tab', 'produtos');

    $resumo = [
        'valorEstoque' => 0,
        'quantidadeProdutos' => 25,
        'cartasAvulsas' => 21,
        'valorCartasAvulsas' => 101.00,
    ];

    $categorias = ['comida', 'cartas', 'bebida', 'Acessórios'];

    $produtos = [
        [
            'nome' => 'Produto #1',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'comida',
            'imagem' => 'https://www.figma.com/api/mcp/asset/af714202-2a92-45e9-8104-9c4bdc34f0f1.png',
        ],
        [
            'nome' => 'Booster Pokémon',
            'preco' => 12.00,
            'descricao' => 'booster pokemon evolving skies',
            'categoria' => 'cartas',
            'imagem' => 'https://www.figma.com/api/mcp/asset/d0fe1f59-6bc7-449c-84ef-fcaaedb6f705.png',
        ],
        [
            'nome' => 'Chaveiro Gengar',
            'preco' => 10.00,
            'descricao' => 'Chaveiro gengar 10cm',
            'categoria' => 'Acessório',
            'imagem' => 'https://www.figma.com/api/mcp/asset/e40d5570-cb99-4be0-a065-e1eb3c63a839.png',
        ],
        [
            'nome' => 'Coca-Cola',
            'preco' => 8.00,
            'descricao' => 'lata de coca-cola 350ml.',
            'categoria' => 'bebida',
            'imagem' => 'https://www.figma.com/api/mcp/asset/3d2dd4f5-16ba-4c5c-a32a-82ee622a2bd0.png',
        ],
        [
            'nome' => 'Produto #2',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'comida',
            'imagem' => '',
        ],
        [
            'nome' => 'Produto #3',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'Acessório',
            'imagem' => '',
        ],
    ];

    $cartaDestaque = [
        'nome' => 'Trevenant do Lupo 096/217',
        'preco' => 0.90,
        'imagem' => 'https://www.figma.com/api/mcp/asset/43628ec4-4244-44fd-b56a-f1ce4fade14d.png',
        'tags' => ['semi-novo', 'rara comum', 'português', 'qtd 4', 'foil'],
    ];

    return view('pages.estoque.index', compact('tab', 'resumo', 'categorias', 'produtos', 'cartaDestaque'));
})->name('estoque.index');

Route::get('/estoque/cartas', function () {
    $jogosDisponiveis = ['pokemon', 'magic', 'onepiece'];
    $jogoAtual = request('jogo', 'pokemon');

    // catálogo de exemplo por jogo — todas as cartas ficam guardadas aqui
    // por enquanto; futuramente isso vem do banco de dados.
    $catalogo = [
        'pokemon' => [
            [
                'nome' => 'Trevenant 096/217',
                'estado' => 'Semi-Novo',
                'colecao' => 'teste',
                'raridade' => 'Rara Comum',
                'idioma' => 'Português',
                'quantidade' => 4,
                'preco' => 0.90,
                'imagem' => null,
            ],
            [
                'nome' => "Ethan's Typhlosion 190/182",
                'estado' => 'Novo',
                'colecao' => 'Destined Rivals',
                'raridade' => 'Rara Secreta',
                'idioma' => 'Inglês',
                'quantidade' => 1,
                'preco' => 120.50,
                'imagem' => 'https://www.figma.com/api/mcp/asset/4d0ac0c5-494d-4511-8833-5fc497fae753.png',
            ],
            [
                'nome' => 'Shiftry 163/162',
                'estado' => 'Semi-Novo',
                'colecao' => 'teste',
                'raridade' => 'Rara Secreta',
                'idioma' => 'Japonês',
                'quantidade' => 2,
                'preco' => 35.50,
                'imagem' => 'https://www.figma.com/api/mcp/asset/40aa70be-6cb1-4fd0-9401-0656b6addef6.png',
            ],
        ],
        'magic' => [],
        'onepiece' => [],
    ];

    $cartas = $catalogo[$jogoAtual] ?? [];

    return view('pages.estoque.cartas', compact('jogosDisponiveis', 'jogoAtual', 'cartas'));
})->name('estoque.cartas');


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
    ];
}
}

Route::get('/campeonatos', function () {
    $todos = campeonatosMock();

    // vários ativos viram um carrossel na página principal
    $ativos = array_values(array_filter($todos, fn ($c) => $c['status'] === 'ativo'));
    $outras = array_values(array_filter($todos, fn ($c) => $c['status'] !== 'ativo'));

    return view('pages.campeonatos.index', compact('ativos', 'outras'));
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

Route::get('/clientes', function () {
    return view('pages.clientes');
})->name('clientes');

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
