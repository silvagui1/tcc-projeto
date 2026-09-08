@extends('layouts.app')

@section('title', 'Estoque')

@section('content')

    <div class="stat-card stat-card--split" style="margin-bottom: 12px;">
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

    <hr class="divider">

    {{-- alterna entre a visão de produtos e a visão de cartas avulsas --}}
    <div class="tabs">
        <a href="{{ route('estoque.index', ['tab' => 'produtos']) }}" class="{{ $tab === 'produtos' ? 'is-active' : '' }}">estoque produtos</a>
        <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="{{ $tab === 'cartas' ? 'is-active' : '' }}">estoque cartas</a>
    </div>

    <div class="search-bar">
        <span>{{ $tab === 'produtos' ? 'buscar produto' : 'buscar carta' }}</span>
        <i class="bi bi-search"></i>
    </div>

    @if ($tab === 'produtos')

        <div class="filters">
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
        </div>

        <hr class="divider">

        <h2 style="font-size: 20px;">Produtos</h2>

        <div class="product-grid">
            @foreach ($produtos as $produto)
                <div class="product-card">
                    <div class="product-card__image" style="background-image: url('{{ $produto['imagem'] }}');">
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

    @else

        {{-- prévia de uma carta em destaque, com link para o catálogo completo --}}
        <div class="trading-card">
            <div class="trading-card__image" style="background-image: url('{{ $cartaDestaque['imagem'] }}');"></div>
            <div class="trading-card__title-row">
                <strong>{{ $cartaDestaque['nome'] }}</strong>
                <span class="price">R$ {{ number_format($cartaDestaque['preco'], 2, ',', '.') }}</span>
            </div>
            <div class="trading-card__meta">
                @foreach ($cartaDestaque['tags'] as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </div>
        </div>

        <a href="{{ route('estoque.cartas') }}" class="btn-primary" style="margin-top: 20px;">
            ver todas as cartas
            <i class="bi bi-arrow-90deg-up"></i>
        </a>

    @endif

@endsection
