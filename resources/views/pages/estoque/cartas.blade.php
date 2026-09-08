@extends('layouts.app')

@section('title', 'Cartas em estoque')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('estoque.index', ['tab' => 'cartas']) }}" class="icon-btn"><i class="bi bi-arrow-left"></i></a>
        <p class="page-topbar__title">Veja nossas cartas</p>
        <span style="width: 30px;"></span>
    </div>

    <div class="search-bar">
        <span>Buscar cartas</span>
        <i class="bi bi-search"></i>
    </div>

    {{-- filtro por jogo (Pokémon / Magic / One Piece / Mais) --}}
    <div class="tabs tabs--pill">
        @foreach ($jogosDisponiveis as $jogo)
            <a href="{{ route('estoque.cartas', ['jogo' => $jogo]) }}" class="{{ $jogoAtual === $jogo ? 'is-active' : '' }}">
                {{ ucfirst($jogo) }}
            </a>
        @endforeach
    </div>

    <div class="card-list" style="margin-top: 24px;">
        @forelse ($cartas as $carta)
            <article>
                <h3 style="font-size: 20px; margin-bottom: 12px;">{{ $carta['nome'] }}</h3>
                <div class="trading-card">
                    <div class="trading-card__image" style="background-image: url('{{ $carta['imagem'] }}');"></div>
                    <p style="margin: 0 0 4px;"><strong>Estado:</strong> {{ $carta['estado'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Coleção:</strong> {{ $carta['colecao'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Raridade:</strong> {{ $carta['raridade'] }}</p>
                    <p style="margin: 0 0 4px;"><strong>Idioma:</strong> {{ $carta['idioma'] }}</p>
                    <p style="margin: 0 0 12px;"><strong>Quantidade:</strong> {{ $carta['quantidade'] }}</p>
                    <p style="font-size: 16.5px; font-weight: 600;">
                        Preço: <span style="color: var(--blue-900);">R$ {{ number_format($carta['preco'], 2, ',', '.') }}</span>
                    </p>
                </div>
            </article>
        @empty
            <p style="text-align:center; color: var(--text-muted-2);">Nenhuma carta cadastrada para este jogo ainda.</p>
        @endforelse
    </div>

@endsection
