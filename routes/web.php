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

    // cartas avulsas de todos os jogos (catálogo + adicionadas pelo formulário):
    // total de unidades em estoque e quanto elas valem (quantidade × preço)
    $cartasAvulsas = 0;
    $valorCartasAvulsas = 0;
    foreach (jogosDeCartas() as $jogo) {
        foreach (cartasDoJogo($jogo) as $carta) {
            $cartasAvulsas += $carta['quantidade'];
            $valorCartasAvulsas += $carta['quantidade'] * $carta['preco'];
        }
    }

    $resumo = [
        'valorEstoque' => 0,
        'quantidadeProdutos' => 25,
        'cartasAvulsas' => $cartasAvulsas,
        'valorCartasAvulsas' => $valorCartasAvulsas,
    ];

    $categorias = ['comida', 'cartas', 'bebida', 'Acessórios'];

    $produtos = [
        [
            'nome' => 'Produto #1',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'comida',
            'imagem' => null,
        ],
        [
            'nome' => 'Booster Pokémon',
            'preco' => 12.00,
            'descricao' => 'booster pokemon evolving skies',
            'categoria' => 'cartas',
            'imagem' => null,
        ],
        [
            'nome' => 'Chaveiro Gengar',
            'preco' => 10.00,
            'descricao' => 'Chaveiro gengar 10cm',
            'categoria' => 'Acessório',
            'imagem' => null,
        ],
        [
            'nome' => 'Coca-Cola',
            'preco' => 8.00,
            'descricao' => 'lata de coca-cola 350ml.',
            'categoria' => 'bebida',
            'imagem' => null,
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

    // produto sem foto usa a imagem genérica da categoria
    foreach ($produtos as &$produto) {
        $produto['imagemPadrao'] = imagemPadraoProduto($produto['categoria']);
        $produto['imagem'] = $produto['imagem'] ?: $produto['imagemPadrao'];
    }
    unset($produto);

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

// imagens genéricas (public/images/placeholders) para carta ou produto sem
// foto — e também de reserva quando a url informada não carrega.
if (! function_exists('imagemPadraoCarta')) {
function imagemPadraoCarta($jogo)
{
    $arquivo = in_array($jogo, jogosDeCartas()) ? "carta-{$jogo}.svg" : 'carta-generica.svg';

    return asset('images/placeholders/' . $arquivo);
}
}

if (! function_exists('imagemPadraoProduto')) {
function imagemPadraoProduto($categoria)
{
    $arquivo = match (mb_strtolower($categoria)) {
        'comida' => 'produto-comida.svg',
        'bebida' => 'produto-bebida.svg',
        'cartas' => 'produto-cartas.svg',
        'acessório', 'acessórios' => 'produto-acessorios.svg',
        default => 'produto-generico.svg',
    };

    return asset('images/placeholders/' . $arquivo);
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
                'imagem' => null,
            ],
            [
                'nome' => 'Shiftry 163/162',
                'estado' => 'Semi-Novo',
                'colecao' => 'teste',
                'raridade' => 'Rara Secreta',
                'idioma' => 'Japonês',
                'quantidade' => 2,
                'preco' => 35.50,
                'imagem' => null,
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
        'magic' => [
            [
                'nome' => 'Llanowar Elves 234/280',
                'estado' => 'Semi-Novo',
                'colecao' => 'Dominaria',
                'raridade' => 'Comum',
                'idioma' => 'Inglês',
                'quantidade' => 12,
                'preco' => 1.50,
                'imagem' => null,
            ],
            [
                'nome' => 'Sheoldred, the Apocalypse 107/281',
                'foil' => true,
                'estado' => 'Novo',
                'colecao' => 'Dominaria United',
                'raridade' => 'Mítica',
                'idioma' => 'Inglês',
                'quantidade' => 1,
                'preco' => 389.90,
                'imagem' => null,
            ],
            [
                'nome' => 'Counterspell 045/281',
                'estado' => 'Usado',
                'colecao' => 'Modern Horizons 2',
                'raridade' => 'Incomum',
                'idioma' => 'Português',
                'quantidade' => 5,
                'preco' => 4.00,
                'imagem' => null,
            ],
            [
                'nome' => 'Lightning Bolt 146/303',
                'estado' => 'Novo',
                'colecao' => 'Magic 2011',
                'raridade' => 'Comum',
                'idioma' => 'Português',
                'quantidade' => 9,
                'preco' => 6.50,
                'imagem' => null,
            ],
            [
                'nome' => 'The One Ring 246/281',
                'foil' => true,
                'estado' => 'Semi-Novo',
                'colecao' => 'O Senhor dos Anéis',
                'raridade' => 'Mítica',
                'idioma' => 'Japonês',
                'quantidade' => 2,
                'preco' => 214.00,
                'imagem' => null,
            ],
            [
                'nome' => 'Sol Ring 263/361',
                'estado' => 'Novo',
                'colecao' => 'Commander Masters',
                'raridade' => 'Incomum',
                'idioma' => 'Inglês',
                'quantidade' => 7,
                'preco' => 8.90,
                'imagem' => null,
            ],
            [
                'nome' => 'Thoughtseize 107/269',
                'estado' => 'Danificado',
                'colecao' => 'Theros',
                'raridade' => 'Rara',
                'idioma' => 'Inglês',
                'quantidade' => 3,
                'preco' => 45.00,
                'imagem' => null,
            ],
        ],
        'onepiece' => [
            [
                'nome' => 'Monkey.D.Luffy OP01-003',
                'estado' => 'Novo',
                'colecao' => 'Romance Dawn',
                'raridade' => 'Líder',
                'idioma' => 'Japonês',
                'quantidade' => 4,
                'preco' => 18.00,
                'imagem' => null,
            ],
            [
                'nome' => 'Roronoa Zoro OP01-025',
                'foil' => true,
                'estado' => 'Semi-Novo',
                'colecao' => 'Romance Dawn',
                'raridade' => 'Super Rara',
                'idioma' => 'Inglês',
                'quantidade' => 2,
                'preco' => 32.50,
                'imagem' => null,
            ],
            [
                'nome' => 'Nami OP01-016',
                'estado' => 'Novo',
                'colecao' => 'Romance Dawn',
                'raridade' => 'Rara',
                'idioma' => 'Inglês',
                'quantidade' => 6,
                'preco' => 7.00,
                'imagem' => null,
            ],
            [
                'nome' => 'Trafalgar Law OP05-069',
                'estado' => 'Usado',
                'colecao' => 'Awakening of the New Era',
                'raridade' => 'Incomum',
                'idioma' => 'Japonês',
                'quantidade' => 11,
                'preco' => 2.20,
                'imagem' => null,
            ],
            [
                'nome' => 'Shanks OP09-004',
                'foil' => true,
                'estado' => 'Novo',
                'colecao' => 'Emperors in the New World',
                'raridade' => 'Secreta Rara',
                'idioma' => 'Japonês',
                'quantidade' => 1,
                'preco' => 540.00,
                'imagem' => null,
            ],
        ],
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
        $carta['imagemPadrao'] = imagemPadraoCarta($jogo);
        $carta['imagem'] = ($carta['imagem'] ?? null) ?: $carta['imagemPadrao'];
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

    $todas = cartasDoJogo($jogoAtual);

    // opções dos filtros laterais: só os valores que existem nas cartas do jogo
    $opcoes = [];
    foreach (['estado', 'raridade', 'idioma'] as $campo) {
        $valores = array_unique(array_column($todas, $campo));
        $valores = array_values(array_filter($valores, fn ($valor) => $valor && $valor !== '—'));
        sort($valores);
        $opcoes[$campo] = $valores;
    }

    // filtros escolhidos (vêm na url, ex.: ?estado[]=Novo&foil=1&preco_max=10)
    $filtros = [
        'estado' => (array) request('estado', []),
        'raridade' => (array) request('raridade', []),
        'idioma' => (array) request('idioma', []),
        'foil' => request()->boolean('foil'),
        'preco_min' => is_numeric(request('preco_min')) ? (float) request('preco_min') : null,
        'preco_max' => is_numeric(request('preco_max')) ? (float) request('preco_max') : null,
        'ordenar' => request('ordenar', 'recentes'),
    ];

    // array_filter mantém as chaves, então o 'id' de cada carta continua certo
    $cartas = array_filter($todas, function ($carta) use ($filtros) {
        foreach (['estado', 'raridade', 'idioma'] as $campo) {
            if ($filtros[$campo] && ! in_array($carta[$campo], $filtros[$campo])) {
                return false;
            }
        }

        return (! $filtros['foil'] || $carta['foil'])
            && ($filtros['preco_min'] === null || $carta['preco'] >= $filtros['preco_min'])
            && ($filtros['preco_max'] === null || $carta['preco'] <= $filtros['preco_max']);
    });

    match ($filtros['ordenar']) {
        'menor_preco' => uasort($cartas, fn ($a, $b) => $a['preco'] <=> $b['preco']),
        'maior_preco' => uasort($cartas, fn ($a, $b) => $b['preco'] <=> $a['preco']),
        'nome' => uasort($cartas, fn ($a, $b) => strcasecmp($a['nome'], $b['nome'])),
        default => null,
    };

    $filtrosAtivos = count($filtros['estado']) + count($filtros['raridade']) + count($filtros['idioma'])
        + ($filtros['foil'] ? 1 : 0) + ($filtros['preco_min'] !== null ? 1 : 0) + ($filtros['preco_max'] !== null ? 1 : 0);

    return view('pages.estoque.cartas', compact('jogosDisponiveis', 'jogoAtual', 'cartas', 'opcoes', 'filtros', 'filtrosAtivos'));
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
        'jogo' => in_array(request('jogo'), jogosDeCartas()) ? request('jogo') : '',
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

    $jogosDisponiveis = jogosDeCartas();

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
