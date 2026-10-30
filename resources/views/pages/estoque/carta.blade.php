@extends('layouts.app')

@section('title', $carta->nome)

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    {{-- caminho de volta: Cartas de <jogo> › nome --}}
    <nav class="trilha" aria-label="Você está em">
        <a href="{{ route('estoque.cartas', ['jogo' => $carta->jogo]) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Cartas de {{ $jogosDisponiveis[$carta->jogo] ?? ucfirst($carta->jogo) }}
        </a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">{{ $carta->nome }}</span>
    </nav>

    @if ($carta->trashed())
        <p class="estoque-aviso-arquivados">
            <i class="bi bi-archive" aria-hidden="true"></i>
            Carta arquivada em {{ $carta->deleted_at->format('d/m/Y') }}: não aparece nas vendas nem nos números do estoque.
        </p>
    @endif

    {{-- imagem grande à esquerda e informações à direita (empilhadas no mobile) --}}
    <div class="card-detail">
        <div class="card-detail__image" style="background-image: url('{{ $carta->imagem_url }}'), url('{{ $carta->imagem_padrao }}');">
            @if ($carta->foil)
                <span class="card-detail__foil"><i class="bi bi-stars"></i> Foil</span>
            @endif
        </div>

        <div class="card-detail__info">
            <span class="card-detail__game">{{ $jogosDisponiveis[$carta->jogo] ?? ucfirst($carta->jogo) }}</span>
            <h1>{{ $carta->nome }}</h1>

            <p class="card-detail__price">
                R$ {{ number_format($carta->preco, 2, ',', '.') }}
                <span>por unidade</span>
            </p>

            <div class="trading-card__meta">
                @foreach ($carta->tags as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </div>

            <dl class="card-detail__specs">
                <div><dt>Estado</dt><dd>{{ $carta->estado }}</dd></div>
                <div><dt>Coleção</dt><dd>{{ $carta->colecao ?? '—' }}</dd></div>
                <div><dt>Raridade</dt><dd>{{ $carta->raridade ?? '—' }}</dd></div>
                <div><dt>Idioma</dt><dd>{{ $carta->idioma }}</dd></div>
                <div><dt>Foil</dt><dd>{{ $carta->foil ? 'Sim' : 'Não' }}</dd></div>
                <div><dt>Quantidade em estoque</dt><dd>{{ $carta->quantidade }}</dd></div>
                <div class="card-detail__total">
                    <dt>Valor total em estoque</dt>
                    <dd>R$ {{ number_format($carta->preco * $carta->quantidade, 2, ',', '.') }}</dd>
                </div>
            </dl>

            @include('pages.estoque.partials.detalhe-acoes', ['tipo' => 'carta', 'item' => $carta])

            @unless ($carta->trashed())
                @include('pages.estoque.partials.ajuste', ['tipo' => 'carta', 'item' => $carta])
            @endunless
        </div>
    </div>

    @include('pages.estoque.partials.historico')

    @unless ($carta->trashed())
        @include('pages.estoque.partials.carta-form', ['jogoPadrao' => $carta->jogo])
    @endunless
    @include('pages.estoque.partials.apagar-popup')

@endsection
