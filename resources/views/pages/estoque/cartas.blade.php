@extends('layouts.app')

@section('title', 'Cartas avulsas')

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    @include('pages.estoque.partials.cabecalho')

    {{-- abas + busca: empilhadas no mobile, lado a lado no desktop --}}
    <div class="estoque-toolbar">
        @include('pages.estoque.partials.abas', ['aba' => 'cartas'])

        <form method="GET" action="{{ route('estoque.cartas') }}" class="search-bar" role="search">
            <input type="hidden" name="jogo" value="{{ $jogoAtual }}">
            <input type="search" name="busca" value="{{ $filtros['busca'] }}" placeholder="Buscar carta" aria-label="Buscar carta">
            <button type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- filtro por jogo (cadastrados em Configurações > Estoque) --}}
    <nav class="tabs tabs--pill cartas-jogos" aria-label="Jogo">
        @foreach ($jogosDisponiveis as $chave => $nomeJogo)
            <a href="{{ route('estoque.cartas', ['jogo' => $chave]) }}" class="{{ $jogoAtual === $chave ? 'is-active' : '' }}" @if ($jogoAtual === $chave) aria-current="page" @endif>
                {{ $nomeJogo }}
            </a>
        @endforeach
    </nav>

    <div class="estoque-lista-topo">
        @php
            $rotuloCartas = $filtros['arquivadas']
                ? ($cartas->total() === 1 ? 'carta arquivada' : 'cartas arquivadas')
                : ($cartas->total() === 1 ? 'carta' : 'cartas');
        @endphp
        <h2>{{ $cartas->total() }} {{ $rotuloCartas }} de {{ $jogosDisponiveis[$jogoAtual] ?? ucfirst($jogoAtual) }}</h2>
        @unless ($filtros['arquivadas'])
            <button type="button" class="botao botao--principal" data-form-open="carta">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar carta
            </button>
        @endunless
    </div>

    @if ($filtros['arquivadas'])
        <p class="estoque-aviso-arquivados">
            <i class="bi bi-archive" aria-hidden="true"></i>
            Cartas arquivadas não aparecem nas vendas nem nos números do estoque. Restaure para voltar a vender.
        </p>
    @endif

    @include('pages.estoque.partials.carta-form', ['jogoPadrao' => $jogoAtual])

    {{-- os filtros entram na própria grade: no desktop ocupam a 1ª coluna --}}
    <div class="card-list card-list--com-filtros" data-card-list>
        @include('pages.estoque.partials.filtros-cartas')

        @forelse ($cartas as $carta)
            <article class="trading-card--link {{ $carta->trashed() ? 'trading-card--arquivada' : '' }}">
                {{-- o link cobre a carta inteira; os botões ficam por cima dele --}}
                <a href="{{ route('estoque.cartas.show', $carta) }}" class="trading-card__link"
                   aria-label="Ver detalhes de {{ $carta->nome }}"></a>
                <h3 class="trading-card__nome">{{ $carta->nome }}</h3>
                <div class="trading-card">
                    @include('pages.estoque.partials.acoes-item', ['tipo' => 'carta', 'item' => $carta])
                    <div class="trading-card__image" style="background-image: url('{{ $carta->imagem_url }}'), url('{{ $carta->imagem_padrao }}');"></div>
                    <dl class="trading-card__dados">
                        <div><dt>Estado</dt><dd>{{ $carta->estado }}</dd></div>
                        <div><dt>Coleção</dt><dd>{{ $carta->colecao ?? '—' }}</dd></div>
                        <div><dt>Raridade</dt><dd>{{ $carta->raridade ?? '—' }}</dd></div>
                        <div><dt>Idioma</dt><dd>{{ $carta->idioma }}</dd></div>
                    </dl>
                    {{-- preço e quantidade juntos no rodapé da carta --}}
                    <div class="trading-card__rodape">
                        <p class="trading-card__preco">R$ {{ number_format($carta->preco, 2, ',', '.') }}</p>
                        <p class="trading-card__estoque">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            @if ($carta->trashed())
                                <span class="selo-arquivado">Arquivada</span>
                            @else
                                {{ $carta->quantidade > 0 ? $carta->quantidade.' em estoque' : 'Sem estoque' }}
                            @endif
                        </p>
                    </div>
                </div>
            </article>
        @empty
            <div class="estoque-vazio">
                <i class="bi {{ $filtrosAtivos ? 'bi-funnel' : 'bi-stack' }}" aria-hidden="true"></i>
                <p class="estoque-vazio__titulo">
                    @if ($filtros['arquivadas']) Nenhuma carta arquivada
                    @elseif ($filtrosAtivos) Nenhuma carta encontrada com esses filtros
                    @else Nenhuma carta cadastrada para este jogo ainda
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    @include('pages.estoque.partials.paginacao', ['paginador' => $cartas, 'rotulo' => 'cartas'])

    @include('pages.estoque.partials.apagar-popup')

@endsection
