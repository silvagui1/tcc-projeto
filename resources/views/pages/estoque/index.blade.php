@extends('layouts.app')

@section('title', 'Estoque')

{{-- no desktop a página de estoque usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    @include('pages.estoque.partials.cabecalho')

    @php
        // monta os links dos filtros mantendo o resto da url
        $comFiltro = fn (array $troca) => route('estoque.index', array_filter(array_merge([
            'busca' => $filtros['busca'] ?: null,
            'categoria' => $filtros['categoria'] ?: null,
            'situacao' => $filtros['situacao'] !== 'todos' ? $filtros['situacao'] : null,
            'ordenar' => $filtros['ordenar'] !== 'recentes' ? $filtros['ordenar'] : null,
        ], $troca)));
        $arquivadosAberto = $filtros['situacao'] === 'arquivados';
        $temFiltro = $filtros['busca'] !== '' || $filtros['categoria'] || $filtros['situacao'] !== 'todos';
    @endphp

    {{-- abas + busca: empilhadas no mobile, lado a lado no desktop --}}
    <div class="estoque-toolbar">
        @include('pages.estoque.partials.abas', ['aba' => 'produtos'])

        <form method="GET" action="{{ route('estoque.index') }}" class="search-bar" role="search">
            @foreach (['categoria', 'situacao', 'ordenar'] as $campo)
                @if ($filtros[$campo] && ! in_array($filtros[$campo], ['todos', 'recentes'], true))
                    <input type="hidden" name="{{ $campo }}" value="{{ $filtros[$campo] }}">
                @endif
            @endforeach
            <input type="search" name="busca" value="{{ $filtros['busca'] }}" placeholder="Buscar produto" aria-label="Buscar produto">
            <button type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @include('pages.estoque.partials.produto-form')

    {{-- no desktop: filtros numa coluna à esquerda, produtos à direita --}}
    <div class="estoque-layout">
        <aside class="filters" aria-label="Filtros de produtos">
            <h2 class="filters__titulo">Filtros</h2>

            <span class="filters__group-label">Situação</span>
            <div class="chip-row">
                <a href="{{ $comFiltro(['situacao' => null]) }}" class="chip {{ $filtros['situacao'] === 'todos' ? 'is-active' : '' }}">Ativos</a>
                <a href="{{ $comFiltro(['situacao' => 'baixo']) }}" class="chip {{ $filtros['situacao'] === 'baixo' ? 'is-active' : '' }}">
                    Estoque baixo @if ($quantidadeEstoqueBaixo)<span class="chip__contador">{{ $quantidadeEstoqueBaixo }}</span>@endif
                </a>
                <a href="{{ $comFiltro(['situacao' => 'arquivados']) }}" class="chip {{ $arquivadosAberto ? 'is-active' : '' }}">
                    Arquivados @if ($arquivados)<span class="chip__contador">{{ $arquivados }}</span>@endif
                </a>
            </div>

            <span class="filters__group-label">Categorias</span>
            <div class="chip-row">
                <a href="{{ $comFiltro(['categoria' => null]) }}" class="chip {{ ! $filtros['categoria'] ? 'is-active' : '' }}">Todas</a>
                @foreach ($categorias as $categoria)
                    <a href="{{ $comFiltro(['categoria' => $categoria->id]) }}"
                       class="chip {{ $filtros['categoria'] === $categoria->id ? 'is-active' : '' }}">{{ Str::ucfirst(mb_strtolower($categoria->nome)) }}</a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('estoque.index') }}" class="filters__ordenar" data-enviar-ao-mudar>
                @foreach (['busca', 'categoria', 'situacao'] as $campo)
                    @if ($filtros[$campo] && $filtros[$campo] !== 'todos')
                        <input type="hidden" name="{{ $campo }}" value="{{ $filtros[$campo] }}">
                    @endif
                @endforeach
                <label class="filters__group-label" for="estoque-ordenar">Ordenar por</label>
                <select name="ordenar" id="estoque-ordenar" class="cartas-filtros__select">
                    @foreach (\App\Http\Controllers\EstoqueController::ORDENACOES as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($filtros['ordenar'] === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="botao botao--neutro">Ordenar</button></noscript>
            </form>

            @if ($temFiltro)
                <a href="{{ route('estoque.index') }}" class="botao-texto filters__limpar">Limpar filtros</a>
            @endif
        </aside>

        <section class="estoque-layout__produtos" aria-labelledby="produtos-titulo">
            <div class="estoque-lista-topo">
                <h2 id="produtos-titulo">
                    {{ $produtos->total() }}
                    {{ $arquivadosAberto ? ($produtos->total() === 1 ? 'produto arquivado' : 'produtos arquivados') : ($produtos->total() === 1 ? 'produto' : 'produtos') }}
                </h2>
                @unless ($arquivadosAberto)
                    <button type="button" class="botao botao--principal" data-form-open="produto">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar produto
                    </button>
                @endunless
            </div>

            @if ($arquivadosAberto)
                <p class="estoque-aviso-arquivados">
                    <i class="bi bi-archive" aria-hidden="true"></i>
                    Produtos arquivados não aparecem nas vendas nem nos números do estoque. Restaure para voltar a vender.
                </p>
            @endif

            @if ($produtos->isEmpty())
                <div class="estoque-vazio">
                    <i class="bi {{ $temFiltro ? 'bi-funnel' : 'bi-box-seam' }}" aria-hidden="true"></i>
                    <p class="estoque-vazio__titulo">
                        @if ($arquivadosAberto) Nenhum produto arquivado
                        @elseif ($temFiltro) Nenhum produto encontrado
                        @else Nenhum produto cadastrado ainda
                        @endif
                    </p>
                    @if ($temFiltro && ! $arquivadosAberto)
                        <p>Tente outra busca ou <a href="{{ route('estoque.index') }}">limpe os filtros</a>.</p>
                    @endif
                </div>
            @endif

            <div class="product-grid">
                @foreach ($produtos as $produto)
                    <article class="product-card product-card--link {{ $produto->trashed() ? 'product-card--arquivado' : '' }}">
                        {{-- o link cobre o card inteiro; os botões ficam por cima dele --}}
                        <a href="{{ route('estoque.produtos.show', $produto) }}" class="product-card__link"
                           aria-label="Ver detalhes de {{ $produto->nome }}"></a>
                        <div class="product-card__image" style="background-image: url('{{ $produto->imagem_url }}'), url('{{ $produto->imagem_padrao }}');">
                            <span class="product-card__tag">{{ Str::ucfirst(mb_strtolower($produto->categoria->nome)) }}</span>
                            @include('pages.estoque.partials.acoes-item', ['tipo' => 'produto', 'item' => $produto])
                        </div>
                        <div class="product-card__body">
                            <div class="name-price">
                                <h3>{{ $produto->nome }}</h3>
                                @if ($produto->comercializavel)
                                    <span class="price">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                                @else
                                    {{-- categoria não comercializável: só pode ser dado --}}
                                    <span class="price price--brinde" title="Não comercializável: só pode ser dado">Brinde</span>
                                @endif
                            </div>
                            @if ($produto->descricao)
                                <p class="product-card__descricao">{{ $produto->descricao }}</p>
                            @endif
                            <p class="product-card__qtd">
                                <strong>{{ $produto->quantidade }} em estoque</strong>
                                @if ($produto->trashed())
                                    <span class="selo-arquivado">Arquivado</span>
                                @elseif ($produto->estoque_baixo)
                                    <span class="selo-estoque-baixo" title="Avisa a partir de {{ $produto->limite_estoque_baixo }} un.{{ $produto->estoque_minimo === null ? ' (padrão)' : '' }}">Estoque baixo</span>
                                @endif
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>

            @include('pages.estoque.partials.paginacao', ['paginador' => $produtos, 'rotulo' => 'produtos'])
        </section>
    </div>

    @include('pages.estoque.partials.apagar-popup')

@endsection
