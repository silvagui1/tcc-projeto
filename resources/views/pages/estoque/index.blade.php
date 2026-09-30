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

    {{-- busca + abas, no mesmo painel da lista de campeonatos (.champ-filtros):
         em cima a busca; embaixo a troca entre estoque produtos / cartas --}}
    @php
        $totalResultados = $tab === 'produtos' ? count($produtos) : count($cartasDestaque);
    @endphp

    <form method="GET" action="{{ route('estoque.index') }}" class="champ-filtros">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="champ-filtros__topo">
            <div class="champ-filtros__busca">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="busca" value="{{ $busca }}"
                       placeholder="{{ $tab === 'produtos' ? 'Buscar produto' : 'Buscar carta' }}"
                       aria-label="{{ $tab === 'produtos' ? 'Buscar produto pelo nome' : 'Buscar carta pelo nome' }}">
                <button type="submit">Buscar</button>
            </div>
        </div>

        <div class="champ-filtros__base">
            {{-- alterna entre a visão de produtos e a visão de cartas avulsas --}}
            <div class="champ-filtros__grupo" role="group" aria-labelledby="filtro-estoque">
                <span class="champ-filtros__rotulo" id="filtro-estoque">Estoque</span>
                <div class="chip-row">
                    <a href="{{ route('estoque.index', ['tab' => 'produtos']) }}" class="chip {{ $tab === 'produtos' ? 'is-active' : '' }}">
                        <i class="bi bi-box-seam" aria-hidden="true"></i> Produtos
                    </a>
                    <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="chip {{ $tab === 'cartas' ? 'is-active' : '' }}">
                        <i class="bi bi-collection" aria-hidden="true"></i> Cartas
                    </a>
                </div>
            </div>

            <div class="champ-filtros__resumo">
                <span>
                    {{ $totalResultados }}
                    @if ($tab === 'produtos')
                        {{ $totalResultados === 1 ? 'produto' : 'produtos' }}
                    @else
                        {{ $totalResultados === 1 ? 'carta' : 'cartas' }}
                    @endif
                </span>
                @if ($busca !== '')
                    <a href="{{ route('estoque.index', ['tab' => $tab]) }}" class="champ-filtros__limpar">
                        <i class="bi bi-x-lg"></i> Limpar busca
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if ($tab === 'produtos')

        {{-- no desktop: filtros numa coluna à esquerda, produtos à direita --}}
        <div class="estoque-layout">
            <aside class="filters">
                <h3>Filtros</h3>

                <span class="filters__group-label">Categorias</span>
                <div class="chip-row">
                    @foreach ($categorias as $categoria)
                        <span class="chip {{ $loop->first ? 'is-active' : '' }}">{{ Str::ucfirst($categoria) }}</span>
                    @endforeach
                </div>

                <span class="filters__group-label">Ordenar</span>
                <div class="chip-row">
                    <span class="chip is-active">Primeiros adicionados</span>
                    <span class="chip">Últimos adicionados</span>
                </div>
            </aside>

            <hr class="divider estoque-layout__divider">

            <section class="estoque-layout__produtos">
                <h2 style="font-size: 20px;">Produtos</h2>

                @include('pages.estoque.partials.adicionar-produto')

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

                    <button type="button" class="add-product-card" data-card-dialog-open="produto">
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
