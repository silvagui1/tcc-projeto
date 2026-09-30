@extends('layouts.app')

@section('title', 'Cartas em estoque')

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="icon-btn"><i class="bi bi-arrow-left"></i></a>
        <p class="page-topbar__title">Veja nossas cartas</p>
        <span style="width: 30px;"></span>
    </div>

    {{-- busca + filtro por jogo: empilhados no mobile, lado a lado no desktop --}}
    <div class="cartas-toolbar">
        <div class="search-bar">
            <span>Buscar cartas</span>
            <i class="bi bi-search"></i>
        </div>

        {{-- filtro por jogo (Pokémon / Magic / One Piece / Mais) --}}
        <div class="tabs tabs--pill">
            @foreach ($jogosDisponiveis as $jogo)
                <a href="{{ route('estoque.cartas', ['jogo' => $jogo]) }}" class="{{ $jogoAtual === $jogo ? 'is-active' : '' }}">
                    {{ nomeDoJogo($jogo) }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="cartas-header" style="margin-top: 24px;">
        <h2>{{ count($cartas) }} {{ count($cartas) === 1 ? 'carta' : 'cartas' }} de {{ nomeDoJogo($jogoAtual) }}</h2>
        @include('pages.estoque.partials.adicionar-carta-botao')
    </div>

    @include('pages.estoque.partials.adicionar-carta', ['jogoPadrao' => $jogoAtual])

    {{-- os filtros entram na própria grade: no desktop ocupam a 1ª coluna --}}
    <div class="card-list card-list--com-filtros" data-card-list>
        @include('pages.estoque.partials.filtros-cartas')

        @forelse ($cartas as $carta)
            <article class="trading-card--link">
                {{-- o link cobre a carta inteira; os botões ficam por cima dele --}}
                <a href="{{ route('estoque.cartas.show', [$carta['jogo'], $carta['id']]) }}" class="trading-card__link"
                   aria-label="Ver detalhes de {{ $carta['nome'] }}"></a>
                <h3 style="font-size: 20px; margin-bottom: 12px;">{{ $carta['nome'] }}</h3>
                <div class="trading-card" style="position: relative;">
                    @include('pages.estoque.partials.carta-acoes')
                    <div class="trading-card__image" style="background-image: url('{{ $carta['imagem'] }}'), url('{{ $carta['imagemPadrao'] }}');"></div>
                    <p style="margin: 0 0 4px;"><strong>Estado:</strong> {{ $carta['estado'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Coleção:</strong> {{ $carta['colecao'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Raridade:</strong> {{ $carta['raridade'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Idioma:</strong> {{ $carta['idioma'] }}</p>
                    <p style="margin: 0 0 12px;"><strong>Quantidade:</strong> {{ $carta['quantidade'] }}</p>
                    <p style="font-size: 16.5px; font-weight: 600;">
                        Preço: <span style="color: var(--text-accent);">R$ {{ number_format($carta['preco'], 2, ',', '.') }}</span>
                    </p>
                </div>
            </article>
        @empty
            <p style="text-align:center; color: var(--text-muted-2);">
                {{ $filtrosAtivos ? 'Nenhuma carta encontrada com esses filtros.' : 'Nenhuma carta cadastrada para este jogo ainda.' }}
            </p>
        @endforelse
    </div>

@endsection
