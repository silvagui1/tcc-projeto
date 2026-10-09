<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Middleware\LogAcessoMiddleware;
use App\Http\Controllers\AlunoController;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\PremioController;
use App\Http\Controllers\ClienteController;
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
        // soma dos preços de todos os produtos (sem busca/filtros; os
        // produtos ainda não têm quantidade, então cada um conta uma vez)
        'valorEstoque' => array_sum(array_column(produtosDoEstoque(), 'preco')),
        'quantidadeProdutos' => 25,
        'cartasAvulsas' => $cartasAvulsas,
        'valorCartasAvulsas' => $valorCartasAvulsas,
    ];

    $categorias = categoriasDeProdutos();

    // produtos de exemplo + adicionados, já com as edições aplicadas
    $produtos = produtosDoEstoque();

    // produto sem foto usa a imagem genérica da categoria. 'imagemSalva' é o
    // link guardado de verdade (vazio se não tiver), usado no pop-up de editar.
    foreach ($produtos as &$produto) {
        $produto['imagemPadrao'] = imagemPadraoProduto($produto['categoria']);
        $produto['imagemSalva'] = $produto['imagem'] ?? '';
        $produto['imagem'] = $produto['imagem'] ?: $produto['imagemPadrao'];
    }
    unset($produto);

    // busca pelo nome (?busca=...): filtra os produtos ou, na aba de cartas,
    // procura em todos os jogos em vez de só mostrar a prévia de Pokémon
    $busca = trim((string) request('busca', ''));
    $bate = fn ($item) => $busca === '' || mb_stripos($item['nome'], $busca) !== false;

    $produtos = array_values(array_filter($produtos, $bate));

    // filtro de categoria (?categoria=...); vazio = todas
    $categoriaAtual = (string) request('categoria', '');
    if ($categoriaAtual !== '') {
        $produtos = array_values(array_filter($produtos,
            fn ($produto) => mb_strtolower($produto['categoria']) === mb_strtolower($categoriaAtual)));
    }

    // ordenação (?ordem=primeiros|ultimos); padrão: últimos adicionados no topo
    $ordemAtual = request('ordem') === 'primeiros' ? 'primeiros' : 'ultimos';
    if ($ordemAtual === 'ultimos') {
        $produtos = array_reverse($produtos);
    }

    // prévia da aba "estoque cartas": as primeiras cartas de Pokémon (as
    // adicionadas pelo formulário aparecem primeiro), antes do "ver todas"
    if ($busca === '') {
        $cartasDestaque = array_slice(cartasDoJogo('pokemon'), 0, 4);
    } else {
        $cartasDestaque = [];
        foreach (jogosDeCartas() as $jogo) {
            $cartasDestaque = array_merge($cartasDestaque, array_filter(cartasDoJogo($jogo), $bate));
        }
    }

    $jogosDisponiveis = jogosDeCartas();

    return view('pages.estoque.index', compact('tab', 'busca', 'resumo', 'categorias', 'categoriaAtual', 'ordemAtual', 'produtos', 'cartasDestaque', 'jogosDisponiveis'));
})->name('estoque.index');

// Formulário "Adicionar produto" (aba estoque produtos). Como as cartas, o
// produto fica guardado na sessão enquanto o projeto não tem banco de dados.
// Os erros vão para o grupo "produto" ($errors->produto na view).
Route::post('/estoque/produtos', function () {
    $dados = validarProduto('produto');

    session()->push('produtosAdicionados', $dados);

    return redirect()->route('estoque.index', ['tab' => 'produtos'])
        ->with('produtoAdicionado', $dados['nome']);
})->name('estoque.produtos.adicionar');

// Formulário "Editar produto" (lápis no card do produto). As alterações ficam
// na sessão ("produtosEditados", por id) e são aplicadas por cima dos dados
// originais em produtosDoEstoque(). Os erros vão para o grupo "produtoEditar";
// o campo produto_id diz qual pop-up reabrir quando a validação falha.
Route::put('/estoque/produtos/{id}', function ($id) {
    abort_unless(collect(produtosDoEstoque())->contains('id', (int) $id), 404);

    $dados = validarProduto('produtoEditar');

    session()->put("produtosEditados.{$id}", $dados);

    // volta para a mesma página, mantendo busca e filtros
    return redirect()->back()->with('produtoEditado', $dados['nome']);
})->whereNumber('id')->name('estoque.produtos.editar');

// --- Estoque de produtos: dados compartilhados ------------------------------

// todos os produtos do estoque, na ordem em que foram adicionados: os de
// exemplo, depois os do formulário (sessão), com as edições já aplicadas.
// Cada produto tem um id (os adicionados continuam a contagem dos de exemplo).
if (! function_exists('produtosDoEstoque')) {
function produtosDoEstoque()
{
    $produtos = [
        [
            'id' => 1,
            'nome' => 'Produto #1',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'comida',
            'imagem' => null,
        ],
        [
            'id' => 2,
            'nome' => 'Booster Pokémon',
            'preco' => 12.00,
            'descricao' => 'booster pokemon evolving skies',
            'categoria' => 'cartas',
            'imagem' => null,
        ],
        [
            'id' => 3,
            'nome' => 'Chaveiro Gengar',
            'preco' => 10.00,
            'descricao' => 'Chaveiro gengar 10cm',
            'categoria' => 'Acessórios',
            'imagem' => null,
        ],
        [
            'id' => 4,
            'nome' => 'Coca-Cola',
            'preco' => 8.00,
            'descricao' => 'lata de coca-cola 350ml.',
            'categoria' => 'bebida',
            'imagem' => null,
        ],
        [
            'id' => 5,
            'nome' => 'Produto #2',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'comida',
            'imagem' => '',
        ],
        [
            'id' => 6,
            'nome' => 'Produto #3',
            'preco' => 0.00,
            'descricao' => 'breve descrição....',
            'categoria' => 'Acessórios',
            'imagem' => '',
        ],
    ];

    $proximoId = count($produtos) + 1;
    foreach (session('produtosAdicionados', []) as $i => $produto) {
        $produtos[] = ['id' => $proximoId + $i] + $produto;
    }

    $editados = session('produtosEditados', []);
    foreach ($produtos as &$produto) {
        if (isset($editados[$produto['id']])) {
            $produto = array_merge($produto, $editados[$produto['id']]);
        }
    }
    unset($produto);

    return $produtos;
}
}

// validação dos pop-ups de adicionar e editar produto ($grupo = grupo de
// erros de cada um). Devolve os dados prontos para guardar, com a imagem
// enviada (arquivo) ou o link digitado.
if (! function_exists('validarProduto')) {
function validarProduto($grupo)
{
    $dados = request()->validateWithBag($grupo, [
        'nome' => ['required', 'string', 'max:120'],
        'categoria' => ['required', 'in:' . implode(',', categoriasDeProdutos())],
        'preco' => ['required', 'numeric', 'min:0', 'max:999999'],
        'descricao' => ['nullable', 'string', 'max:300'],
        'imagem' => ['nullable', 'url', 'max:500'],
        'imagem_arquivo' => ['nullable', 'image', 'max:5120'],
    ], [
        'required' => 'O campo :attribute é obrigatório.',
        'in' => 'Escolha uma opção válida em :attribute.',
        'string' => 'O campo :attribute deve ser um texto.',
        'max' => 'O campo :attribute passou do limite (máx. :max).',
        'min' => 'O campo :attribute deve ser no mínimo :min.',
        'numeric' => 'O campo :attribute deve ser um número.',
        'url' => 'O campo :attribute deve ser um link válido (https://...).',
        'image' => 'O arquivo de :attribute precisa ser uma imagem (jpg, png, webp...).',
        'imagem_arquivo.max' => 'A imagem passou do limite de 5 MB.',
        'uploaded' => 'Não foi possível enviar a imagem (talvez ela seja grande demais).',
    ], [
        'preco' => 'preço',
        'descricao' => 'descrição',
        'imagem' => 'url da imagem',
        'imagem_arquivo' => 'imagem',
    ]);

    unset($dados['imagem_arquivo']);
    $dados['imagem'] = salvarImagemEnviada('produtos') ?? ($dados['imagem'] ?? null);
    $dados['preco'] = (float) $dados['preco'];
    $dados['descricao'] = $dados['descricao'] ?? '';

    return $dados;
}
}

// --- Estoque de cartas: dados compartilhados --------------------------------
// (function_exists evita erro de "função redeclarada" no route:cache)

// jogos aceitos no estoque de cartas (filtro da página e campo do formulário)
if (! function_exists('jogosDeCartas')) {
function jogosDeCartas()
{
    return ['pokemon', 'magic', 'onepiece'];
}
}

// nome de cada jogo como aparece na tela (filtros, abas, títulos)
if (! function_exists('nomeDoJogo')) {
function nomeDoJogo($jogo)
{
    return ['pokemon' => 'Pokémon', 'magic' => 'Magic', 'onepiece' => 'One Piece'][$jogo] ?? ucfirst($jogo);
}
}

// categorias de produto (filtro da aba e campo do formulário)
if (! function_exists('categoriasDeProdutos')) {
function categoriasDeProdutos()
{
    return ['comida', 'cartas', 'bebida', 'Acessórios'];
}
}

// imagem arrastada/colada/escolhida nos pop-ups do estoque (campo
// "imagem_arquivo"): vai para public/uploads/<pasta> e devolve o link dela.
// Sem arquivo, devolve null (aí vale o link digitado, se houver).
if (! function_exists('salvarImagemEnviada')) {
function salvarImagemEnviada($pasta)
{
    if (! request()->hasFile('imagem_arquivo')) {
        return null;
    }

    $arquivo = request()->file('imagem_arquivo');
    $nomeArquivo = Str::uuid() . '.' . ($arquivo->guessExtension() ?: 'png');
    $arquivo->move(public_path("uploads/{$pasta}"), $nomeArquivo);

    return asset("uploads/{$pasta}/{$nomeArquivo}");
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
        'imagem_arquivo' => ['nullable', 'image', 'max:5120'],
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
        'image' => 'O arquivo de :attribute precisa ser uma imagem (jpg, png, webp...).',
        'imagem_arquivo.max' => 'A imagem passou do limite de 5 MB.',
        'uploaded' => 'Não foi possível enviar a imagem (talvez ela seja grande demais).',
    ], [
        'colecao' => 'coleção',
        'preco' => 'preço',
        'imagem' => 'url da imagem',
        'imagem_arquivo' => 'imagem',
    ]);

    unset($dados['imagem_arquivo']);
    $dados['imagem'] = salvarImagemEnviada('cartas') ?? ($dados['imagem'] ?? null);

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


// --- Outras páginas do menu ----------------------------------------------

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
