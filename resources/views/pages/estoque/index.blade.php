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

    <hr class="divider">

    {{-- abas + busca: empilhadas no mobile, lado a lado no desktop --}}
    <div class="estoque-toolbar">
        {{-- alterna entre a visão de produtos e a visão de cartas avulsas --}}
        <div class="tabs">
            <a href="{{ route('estoque.index', ['tab' => 'produtos']) }}" class="{{ $tab === 'produtos' ? 'is-active' : '' }}">estoque produtos</a>
            <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="{{ $tab === 'cartas' ? 'is-active' : '' }}">estoque cartas</a>
        </div>

        <div class="search-bar">
            <span>{{ $tab === 'produtos' ? 'buscar produto' : 'buscar carta' }}</span>
            <i class="bi bi-search"></i>
        </div>
    </div>

    @if ($tab === 'produtos')

        {{-- no desktop: filtros numa coluna à esquerda, produtos à direita --}}
        <div class="estoque-layout">
            <aside class="filters">
                <h3>Filtros</h3>

                <span class="filters__group-label">categorias</span>
                <div class="chip-row">
                    @foreach ($categorias as $categoria)
                        <span class="chip {{ $loop->first ? 'is-active' : '' }}">{{ $categoria }}</span>
                    @endforeach
                </div>

                <span class="filters__group-label">ordenar</span>
                <div class="chip-row">
                    <span class="chip is-active">primeiros adicionados</span>
                    <span class="chip">ultimos adicionados</span>
                </div>
            </aside>

            <hr class="divider estoque-layout__divider">

            <section class="estoque-layout__produtos">
                <h2 style="font-size: 20px;">Produtos</h2>

                <div class="product-grid">
                    @foreach ($produtos as $produto)
                        <div class="product-card">
                            <div class="product-card__image" style="background-image: url('{{ $produto['imagem'] }}'), url('{{ $produto['imagemPadrao'] }}');">
                                <span class="product-card__tag">{{ $produto['categoria'] }}</span>
                                <button type="button" class="product-card__delete"><i class="bi bi-trash"></i></button>
                            </div>
                            <div class="product-card__body">
                                <div class="name-price">
                                    <strong>{{ $produto['nome'] }}</strong>
                                    <span class="price">R$ {{ number_format($produto['preco'], 2, ',', '.') }}</span>
                                </div>
                                <p>{{ $produto['descricao'] }}</p>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" class="add-product-card">
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

            @include('pages.estoque.partials.adicionar-carta', ['jogoPadrao' => 'pokemon'])

            {{-- prévia de algumas cartas, com link para o catálogo completo.
                 No mobile vira um carrossel lateral; no desktop, uma fileira de 4. --}}
            <div class="estoque-cartas-grid">
                @foreach ($cartasDestaque as $carta)
                    <div class="trading-card trading-card--link">
                        {{-- o link cobre a carta inteira; os botões ficam por cima dele --}}
                        <a href="{{ route('estoque.cartas.show', [$carta['jogo'], $carta['id']]) }}" class="trading-card__link"
                           aria-label="Ver detalhes de {{ $carta['nome'] }}"></a>
                        @include('pages.estoque.partials.carta-acoes')
                        <div class="trading-card__image" style="background-image: url('{{ $carta['imagem'] }}'), url('{{ $carta['imagemPadrao'] }}');"></div>
                        <div class="trading-card__title-row">
                            <strong>{{ $carta['nome'] }}</strong>
                            <span class="price">R$ {{ number_format($carta['preco'], 2, ',', '.') }}</span>
                        </div>
                        <div class="trading-card__meta">
                            @foreach ($carta['tags'] as $tag)
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

@endsection
