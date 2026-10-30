@extends('layouts.app')

@section('title', $produto->nome)

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    {{-- caminho de volta: Estoque › Produtos › nome --}}
    <nav class="trilha" aria-label="Você está em">
        <a href="{{ route('estoque.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Produtos</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">{{ $produto->nome }}</span>
    </nav>

    @if ($produto->trashed())
        <p class="estoque-aviso-arquivados">
            <i class="bi bi-archive" aria-hidden="true"></i>
            Produto arquivado em {{ $produto->deleted_at->format('d/m/Y') }}: não aparece nas vendas nem nos números do estoque.
        </p>
    @endif

    {{-- mesmo layout dos detalhes da carta (imagem à esquerda, informações à
         direita; empilhados no mobile), com a imagem quadrada --}}
    <div class="card-detail card-detail--produto">
        <div class="card-detail__image" style="background-image: url('{{ $produto->imagem_url }}'), url('{{ $produto->imagem_padrao }}');">
            @if ($produto->estoque_baixo && ! $produto->trashed())
                <span class="card-detail__foil card-detail__alerta"><i class="bi bi-exclamation-triangle-fill"></i> Estoque baixo</span>
            @endif
        </div>

        <div class="card-detail__info">
            <span class="card-detail__game">{{ Str::ucfirst(mb_strtolower($produto->categoria->nome)) }}</span>
            <h1>{{ $produto->nome }}</h1>

            @if ($produto->comercializavel)
                <p class="card-detail__price">
                    R$ {{ number_format($produto->preco, 2, ',', '.') }}
                    <span>por unidade</span>
                </p>
            @else
                {{-- categoria não comercializável: só pode ser dado --}}
                <p class="card-detail__price card-detail__price--brinde">
                    Brinde
                    <span>Não comercializável: só pode ser dado</span>
                </p>
            @endif

            @if ($produto->descricao)
                <p class="card-detail__descricao">{{ $produto->descricao }}</p>
            @endif

            <dl class="card-detail__specs">
                <div>
                    <dt>Quantidade em estoque</dt>
                    <dd>
                        {{ $produto->quantidade }}
                        @if ($produto->estoque_baixo && ! $produto->trashed())
                            <span class="selo-estoque-baixo">Estoque baixo</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Alerta de estoque baixo</dt>
                    <dd>
                        @if ($produto->limite_estoque_baixo === null)
                            Desligado
                        @else
                            até {{ $produto->limite_estoque_baixo }} un.{{ $produto->estoque_minimo === null ? ' (padrão)' : '' }}
                        @endif
                    </dd>
                </div>
                <div><dt>Cadastrado em</dt><dd>{{ $produto->created_at?->format('d/m/Y') ?? '—' }}</dd></div>
                @if ($produto->comercializavel)
                    <div class="card-detail__total">
                        <dt>Valor total em estoque</dt>
                        <dd>R$ {{ number_format($produto->preco * $produto->quantidade, 2, ',', '.') }}</dd>
                    </div>
                @endif
            </dl>

            @include('pages.estoque.partials.detalhe-acoes', ['tipo' => 'produto', 'item' => $produto])

            @unless ($produto->trashed())
                @include('pages.estoque.partials.ajuste', ['tipo' => 'produto', 'item' => $produto])
            @endunless
        </div>
    </div>

    @include('pages.estoque.partials.historico')

    @unless ($produto->trashed())
        @include('pages.estoque.partials.produto-form')
    @endunless
    @include('pages.estoque.partials.apagar-popup')

@endsection
