@extends('layouts.app')

@section('title', 'Estoque')

{{-- no desktop a página de estoque usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    {{-- no mobile os cartões ficam empilhados; no desktop viram uma fileira só --}}
    <div class="estoque-resumo">
        <div class="stat-card stat-card--split">
            <div>
                <span class="stat-card__label">valor em estoque</span>
                <p class="stat-card__value">R$ {{ number_format($resumo['valorEstoque'], 2, ',', '.') }}</p>
            </div>
            <div>
                <span class="stat-card__label">quantidade de produtos</span>
                <p class="stat-card__value">{{ $resumo['quantidadeProdutos'] }}</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-card__label">cartas avulsas no estoque</span>
                <p class="stat-card__value stat-card__value--lg">{{ $resumo['cartasAvulsas'] }}</p>
            </div>
            <div class="stat-card">
                <span class="stat-card__label">valor total em cartas avulsas</span>
                <p class="stat-card__value stat-card__value--lg">R$ {{ number_format($resumo['valorCartasAvulsas'], 2, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Alerta de estoque baixo (Configurações > Estoque) --}}
    @if ($estoqueBaixo->isNotEmpty())
        <div class="alerta-estoque" role="status">
            <i class="bi bi-exclamation-triangle-fill alerta-estoque__icone" aria-hidden="true"></i>
            <div class="alerta-estoque__texto">
                <strong>
                    {{ $estoqueBaixo->count() }} {{ $estoqueBaixo->count() === 1 ? 'produto está' : 'produtos estão' }}
                    com estoque baixo
                </strong>
                <span>
                    {{ $estoqueBaixo->take(4)->map(fn ($p) => "{$p->nome} ({$p->quantidade})")->implode(' · ') }}{{ $estoqueBaixo->count() > 4 ? ' · e mais '.($estoqueBaixo->count() - 4) : '' }}
                </span>
            </div>
            <a href="{{ route('config') }}#estoque" class="alerta-estoque__link" title="Alerta a partir de {{ $alertaMinimo }} unidades — mudar em Configurações">
                até {{ $alertaMinimo }} un.
            </a>
        </div>
    @endif

    <hr class="divider">

    {{-- abas + busca: empilhadas no mobile, lado a lado no desktop --}}
    <div class="estoque-toolbar">
        {{-- alterna entre a visão de produtos e a visão de cartas avulsas --}}
        <div class="tabs">
            <a href="{{ route('estoque.index', ['tab' => 'produtos']) }}" class="{{ $tab === 'produtos' ? 'is-active' : '' }}">estoque produtos</a>
            <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="{{ $tab === 'cartas' ? 'is-active' : '' }}">estoque cartas</a>
        </div>

        {{-- na aba de produtos filtra a lista abaixo; na de cartas leva para o catálogo --}}
        @if ($tab === 'produtos')
            <form method="GET" action="{{ route('estoque.index') }}" class="search-bar" role="search">
                <input type="hidden" name="tab" value="produtos">
                @if ($filtros['categoria'])
                    <input type="hidden" name="categoria" value="{{ $filtros['categoria'] }}">
                @endif
                <input type="search" name="busca" value="{{ $filtros['busca'] }}" placeholder="buscar produto" aria-label="Buscar produto">
                <button type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
            </form>
        @else
            <form method="GET" action="{{ route('estoque.cartas') }}" class="search-bar" role="search">
                <input type="search" name="busca" placeholder="buscar carta" aria-label="Buscar carta">
                <button type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
            </form>
        @endif
    </div>

    @include('pages.estoque.partials.mensagem')

    @if ($tab === 'produtos')

        @include('pages.estoque.partials.produto-form')

        {{-- no desktop: filtros numa coluna à esquerda, produtos à direita --}}
        <div class="estoque-layout">
            <aside class="filters">
                <h3>Filtros</h3>

                <span class="filters__group-label">categorias</span>
                <div class="chip-row">
                    <a href="{{ route('estoque.index', ['tab' => 'produtos', 'busca' => $filtros['busca'] ?: null, 'ordenar' => $filtros['ordenar']]) }}"
                       class="chip {{ ! $filtros['categoria'] ? 'is-active' : '' }}">todas</a>
                    @foreach ($categorias as $categoria)
                        <a href="{{ route('estoque.index', ['tab' => 'produtos', 'categoria' => $categoria->id, 'busca' => $filtros['busca'] ?: null, 'ordenar' => $filtros['ordenar']]) }}"
                           class="chip {{ $filtros['categoria'] === $categoria->id ? 'is-active' : '' }}">{{ mb_strtolower($categoria->nome) }}</a>
                    @endforeach
                </div>

                <span class="filters__group-label">ordenar</span>
                <div class="chip-row">
                    @foreach (['antigos' => 'primeiros adicionados', 'recentes' => 'ultimos adicionados'] as $valor => $rotulo)
                        <a href="{{ route('estoque.index', ['tab' => 'produtos', 'categoria' => $filtros['categoria'] ?: null, 'busca' => $filtros['busca'] ?: null, 'ordenar' => $valor]) }}"
                           class="chip {{ $filtros['ordenar'] === $valor ? 'is-active' : '' }}">{{ $rotulo }}</a>
                    @endforeach
                </div>
            </aside>

            <hr class="divider estoque-layout__divider">

            <section class="estoque-layout__produtos">
                <h2 style="font-size: 20px;">Produtos</h2>

                @if ($produtos->isEmpty())
                    <p style="color: var(--text-muted-2);">
                        {{ $filtros['busca'] !== '' || $filtros['categoria'] ? 'Nenhum produto encontrado com esses filtros.' : 'Nenhum produto cadastrado ainda.' }}
                    </p>
                @endif

                <div class="product-grid">
                    @foreach ($produtos as $produto)
                        <div class="product-card">
                            <div class="product-card__image" style="background-image: url('{{ $produto->imagem_url }}'), url('{{ $produto->imagem_padrao }}');">
                                <span class="product-card__tag">{{ mb_strtolower($produto->categoria->nome) }}</span>
                                <span class="product-card__actions">
                                    <button type="button" class="product-card__delete" data-form-open="produto"
                                            data-form-dados='@json($produto->dadosFormulario())'
                                            aria-label="Editar {{ $produto->nome }}" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="product-card__delete"
                                            data-delete-open="{{ route('estoque.produtos.destroy', $produto) }}"
                                            data-delete-title="Deseja apagar o produto &quot;{{ $produto->nome }}&quot;?"
                                            aria-label="Apagar {{ $produto->nome }}" title="Apagar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="product-card__body">
                                <div class="name-price">
                                    <strong>{{ $produto->nome }}</strong>
                                    <span class="price">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                                </div>
                                <p>{{ $produto->descricao }}</p>
                                <p>
                                    <strong>qtd {{ $produto->quantidade }}</strong>
                                    @if ($produto->estoque_baixo)
                                        <span class="selo-estoque-baixo">estoque baixo</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" class="add-product-card" data-form-open="produto">
                        <span class="plus-circle"><i class="bi bi-plus-lg"></i></span>
                        Adicionar produto
                    </button>
                </div>
            </section>
        </div>

    @else

        <div class="estoque-cartas-preview">
            <div class="cartas-header">
                <h2>Cartas em destaque</h2>
                @include('pages.estoque.partials.adicionar-carta-botao')
            </div>

            @include('pages.estoque.partials.carta-form', ['jogoPadrao' => array_key_first($jogosDisponiveis)])

            @if ($cartasDestaque->isEmpty())
                <p style="color: var(--text-muted-2);">Nenhuma carta cadastrada ainda.</p>
            @endif

            {{-- prévia das últimas cartas, com link para o catálogo completo.
                 No mobile vira um carrossel lateral; no desktop, uma fileira de 4. --}}
            <div class="estoque-cartas-grid">
                @foreach ($cartasDestaque as $carta)
                    <div class="trading-card trading-card--link">
                        {{-- o link cobre a carta inteira; os botões ficam por cima dele --}}
                        <a href="{{ route('estoque.cartas.show', $carta) }}" class="trading-card__link"
                           aria-label="Ver detalhes de {{ $carta->nome }}"></a>
                        @include('pages.estoque.partials.carta-acoes')
                        <div class="trading-card__image" style="background-image: url('{{ $carta->imagem_url }}'), url('{{ $carta->imagem_padrao }}');"></div>
                        <div class="trading-card__title-row">
                            <strong>{{ $carta->nome }}</strong>
                            <span class="price">R$ {{ number_format($carta->preco, 2, ',', '.') }}</span>
                        </div>
                        <div class="trading-card__meta">
                            @foreach ($carta->tags as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <a href="{{ route('estoque.cartas') }}" class="btn-primary estoque-cartas-preview__all" style="margin-top: 20px;">
                ver todas as cartas
                <i class="bi bi-arrow-90deg-up"></i>
            </a>
        </div>

    @endif

    @include('pages.estoque.partials.apagar-popup')

@endsection
