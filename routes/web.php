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

    // prévia da aba "estoque cartas": as primeiras cartas de Pokémon (as
    // adicionadas pelo formulário aparecem primeiro), antes do "ver todas"
    $cartasDestaque = array_slice(cartasDoJogo('pokemon'), 0, 4);

    $jogosDisponiveis = jogosDeCartas();

    return view('pages.estoque.index', compact('tab', 'resumo', 'categorias', 'produtos', 'cartasDestaque', 'jogosDisponiveis'));
})->name('estoque.index');

// --- Estoque de cartas: dados compartilhados --------------------------------
// (function_exists evita erro de "função redeclarada" no route:cache)

// jogos aceitos no estoque de cartas (filtro da página e campo do formulário)
if (! function_exists('jogosDeCartas')) {
function jogosDeCartas()
{
    return ['pokemon', 'magic', 'onepiece'];
}
}

// catálogo de exemplo por jogo — por enquanto as cartas ficam guardadas
// aqui; futuramente isso vem do banco de dados.
if (! function_exists('catalogoCartas')) {
function catalogoCartas()
{
    return [
        'pokemon' => [
            [
                'nome' => 'Trevenant 096/217',
                'foil' => true,
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
            [
                'nome' => 'Carta genérica 001/100',
                'estado' => 'Novo',
                'colecao' => 'teste',
                'raridade' => 'Comum',
                'idioma' => 'Português',
                'quantidade' => 6,
                'preco' => 0.50,
                'imagem' => null,
            ],
            [
                'nome' => 'Carta genérica 002/100',
                'estado' => 'Semi-Novo',
                'colecao' => 'teste',
                'raridade' => 'Incomum',
                'idioma' => 'Inglês',
                'quantidade' => 3,
                'preco' => 1.50,
                'imagem' => null,
            ],
            [
                'nome' => 'Carta genérica 003/100',
                'estado' => 'Novo',
                'colecao' => 'teste',
                'raridade' => 'Rara',
                'idioma' => 'Português',
                'quantidade' => 2,
                'preco' => 5.00,
                'imagem' => null,
            ],
            [
                'nome' => 'Carta genérica 004/100',
                'estado' => 'Usado',
                'colecao' => 'teste',
                'raridade' => 'Comum',
                'idioma' => 'Japonês',
                'quantidade' => 8,
                'preco' => 0.30,
                'imagem' => null,
            ],
            [
                'nome' => 'Carta genérica 005/100',
                'estado' => 'Novo',
                'colecao' => 'teste',
                'raridade' => 'Rara Holo',
                'idioma' => 'Inglês',
                'quantidade' => 1,
                'preco' => 12.00,
                'imagem' => null,
            ],
        ],
        'magic' => [],
        'onepiece' => [],
    ];
}
}

// Lista completa de um jogo: cartas adicionadas pelo formulário (sessão, mais
// novas primeiro) + catálogo de exemplo. Cada carta ganha 'jogo' e 'id' (a
// posição na lista), usados no link da página de detalhes.
if (! function_exists('cartasDoJogo')) {
function cartasDoJogo($jogo)
{
    $adicionadas = array_filter(
        array_reverse(session('cartasAdicionadas', [])),
        fn ($carta) => $carta['jogo'] === $jogo
    );

    $cartas = array_merge($adicionadas, catalogoCartas()[$jogo] ?? []);

    foreach ($cartas as $id => &$carta) {
        $carta['id'] = $id;
        $carta['jogo'] = $jogo;
        $carta['foil'] = $carta['foil'] ?? false;
        $carta['tags'] = array_values(array_filter([
            mb_strtolower($carta['estado']),
            mb_strtolower($carta['raridade']),
            mb_strtolower($carta['idioma']),
            'qtd ' . $carta['quantidade'],
            $carta['foil'] ? 'foil' : null,
        ], fn ($tag) => $tag && $tag !== '—'));
    }

    return $cartas;
}
}

// Formulário "Adicionar carta" (aba estoque cartas e página de cartas).
// Ainda sem banco de dados: a carta fica guardada na sessão, então aparece
// nas duas telas até a sessão expirar.
Route::post('/estoque/cartas', function () {
    $dados = request()->validate([
        'jogo' => ['required', 'in:' . implode(',', jogosDeCartas())],
        'nome' => ['required', 'string', 'max:120'],
        'colecao' => ['nullable', 'string', 'max:120'],
        'raridade' => ['nullable', 'string', 'max:60'],
        'estado' => ['required', 'in:Novo,Semi-Novo,Usado,Danificado'],
        'idioma' => ['required', 'string', 'max:40'],
        'quantidade' => ['required', 'integer', 'min:1', 'max:9999'],
        'preco' => ['required', 'numeric', 'min:0', 'max:999999'],
        'imagem' => ['nullable', 'url', 'max:500'],
        'foil' => ['nullable', 'boolean'],
    ], [
        // mensagens em português (o projeto ainda não tem lang/pt_BR)
        'required' => 'O campo :attribute é obrigatório.',
        'in' => 'Escolha uma opção válida em :attribute.',
        'string' => 'O campo :attribute deve ser um texto.',
        'max' => 'O campo :attribute passou do limite (máx. :max).',
        'min' => 'O campo :attribute deve ser no mínimo :min.',
        'integer' => 'O campo :attribute deve ser um número inteiro.',
        'numeric' => 'O campo :attribute deve ser um número.',
        'url' => 'O campo :attribute deve ser um link válido (https://...).',
        'boolean' => 'O campo :attribute é inválido.',
    ], [
        'colecao' => 'coleção',
        'preco' => 'preço',
        'imagem' => 'url da imagem',
    ]);

    $dados['foil'] = request()->boolean('foil');
    $dados['colecao'] = $dados['colecao'] ?? '—';
    $dados['raridade'] = $dados['raridade'] ?? '—';

    session()->push('cartasAdicionadas', $dados);

    return back()->with('cartaAdicionada', $dados['nome']);
})->name('estoque.cartas.adicionar');

Route::get('/estoque/cartas', function () {
    $jogosDisponiveis = jogosDeCartas();
    $jogoAtual = request('jogo', 'pokemon');

    $cartas = cartasDoJogo($jogoAtual);

    return view('pages.estoque.cartas', compact('jogosDisponiveis', 'jogoAtual', 'cartas'));
})->name('estoque.cartas');

// Detalhes de uma carta (abre ao clicar numa carta em qualquer das listas).
Route::get('/estoque/cartas/{jogo}/{id}', function ($jogo, $id) {
    $carta = cartasDoJogo($jogo)[$id] ?? abort(404);

    return view('pages.estoque.carta', compact('carta'));
})->whereNumber('id')->name('estoque.cartas.show');


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
