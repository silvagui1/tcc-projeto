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

Route::get('/campeonatos', function () {
    $ativo = [
        'id' => 1,
        'data' => '21 de agosto de 2026',
        'imagem' => 'https://www.figma.com/api/mcp/asset/9cb786cf-2723-4727-b9c3-eff84198ae91.png',
    ];

    $outras = [
        [
            'id' => 2,
            'nome' => 'Pokemon',
            'data' => '21 de agosto de 2026',
            'participantes' => 4,
            'deck' => 'deck ultra max',
            'premios' => [
                '1° lugar ganha carta e 200 créditos',
                '2° lugar ganha 100 créditos',
                '3° lugar ganha 50 créditos',
            ],
            'imagem' => 'https://www.figma.com/api/mcp/asset/61005cff-5fa2-4691-8d81-4af3e6727c8e.png',
        ],
    ];

    return view('pages.campeonatos.index', compact('ativo', 'outras'));
})->name('campeonatos.index');

Route::get('/campeonatos/criar', function () {
    $clientesSugeridos = [
        ['id' => 1, 'nome' => 'Rogério Cartinhas', 'nascimento' => '20/06/2009', 'avatar' => 'https://www.figma.com/api/mcp/asset/7d3b53c9-aae0-4c2f-be07-88d4777b0098.png'],
        ['id' => 2, 'nome' => 'Roger', 'nascimento' => '11/07/1999', 'avatar' => 'https://www.figma.com/api/mcp/asset/7d3b53c9-aae0-4c2f-be07-88d4777b0098.png'],
    ];

    return view('pages.campeonatos.criar', compact('clientesSugeridos'));
})->name('campeonatos.criar');

Route::post('/campeonatos', function () {
    // somente frontend por enquanto — sem persistência ainda.
    return redirect()->route('campeonatos.index');
});

Route::get('/campeonatos/{id}', function ($id) {
    $campeonato = [
        'id' => $id,
        'nome' => 'Torneio Pokemon',
        'data' => '22 de agosto de 2026',
        'horario' => '10:30',
        'participantes' => 12,
        'deck' => 'deck base',
        'inscricao' => 15.00,
        'descricao' => 'terá prêmios e cartas, dando quantias de crédito para cada ganhador',
        'imagem' => 'https://www.figma.com/api/mcp/asset/0159aba5-d213-4de8-9043-f60e214d8e98.png',
    ];

    return view('pages.campeonatos.show', compact('campeonato'));
})->name('campeonatos.show');

Route::get('/campeonatos/{id}/editar', function ($id) {
    $campeonato = [
        'id' => $id,
        'nome' => 'Pokemon',
        'data' => '21/08/2026',
        'horario' => '10:00',
        'deck' => 'deck ultra max',
        'inscricao' => 15.00,
        'descricao' => "1° lugar ganha carta e 200 créditos\n2° lugar ganha 100 créditos\n3° lugar ganha 50 créditos",
        'status' => 'ativo',
        'vencedores' => [
            ['posicao' => '1° Lugar', 'nome' => 'Guilherme Soares', 'credito' => 10.00, 'avatar' => 'https://www.figma.com/api/mcp/asset/ff7bf656-665a-48a3-ae39-c1ed512a5623.png'],
            ['posicao' => '2° Lugar', 'nome' => 'Leonardo Pietro', 'credito' => 10.00, 'avatar' => 'https://www.figma.com/api/mcp/asset/a3742770-313c-426c-948b-f234001c46aa.png'],
            ['posicao' => '3° Lugar', 'nome' => 'Maria Garcez', 'credito' => 10.00, 'avatar' => 'https://www.figma.com/api/mcp/asset/8668ad43-da37-42dc-9c7e-c7b20443e0f2.png'],
        ],
        'participantesLista' => [
            ['nome' => 'Guilherme Soares', 'nascimento' => '20/06/2009', 'avatar' => 'https://www.figma.com/api/mcp/asset/ff7bf656-665a-48a3-ae39-c1ed512a5623.png'],
            ['nome' => 'Maria Garcez', 'nascimento' => '29/07/2008', 'avatar' => 'https://www.figma.com/api/mcp/asset/8668ad43-da37-42dc-9c7e-c7b20443e0f2.png'],
            ['nome' => 'Leonardo Pietro', 'nascimento' => '24/06/2009', 'avatar' => 'https://www.figma.com/api/mcp/asset/a3742770-313c-426c-948b-f234001c46aa.png'],
            ['nome' => 'Elielso Pedroso', 'nascimento' => '22/06/1979', 'avatar' => 'https://www.figma.com/api/mcp/asset/7f6ad8c5-0a2d-4240-b12f-dae2fbaf973b.png'],
        ],
    ];

    return view('pages.campeonatos.editar', compact('campeonato'));
})->name('campeonatos.editar');

Route::put('/campeonatos/{id}/editar', function ($id) {
    // somente frontend por enquanto — sem persistência ainda.
    return redirect()->route('campeonatos.index');
});

Route::get('/campeonatos/{id}/premiacoes', function ($id) {
    $avatar = 'https://www.figma.com/api/mcp/asset/059f6265-ed5b-4526-98fa-bb92f0b11654.png';

    // Um bloco por colocação: quem ficou no lugar, quanto de crédito recebe
    // e a descrição do prêmio. Ainda é mock — sem banco de dados.
    $colocacoes = [
        ['posicao' => '1° Lugar', 'nome' => 'Guilherme Soares', 'nascimento' => '20/06/2009', 'avatar' => $avatar, 'valor' => 0.00, 'descricao' => ''],
        ['posicao' => '2° Lugar', 'nome' => 'Leonardo Pietro', 'nascimento' => '24/06/2009', 'avatar' => $avatar, 'valor' => 0.00, 'descricao' => ''],
        ['posicao' => '3° Lugar', 'nome' => 'Maria Garcez', 'nascimento' => '29/07/2008', 'avatar' => $avatar, 'valor' => 0.00, 'descricao' => ''],
    ];

    $campeonatoId = $id;

    return view('pages.campeonatos.premiacoes', compact('colocacoes', 'campeonatoId'));
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
