@extends('layouts.app')

@section('title', $carta->nome)

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('estoque.cartas', ['jogo' => $carta->jogo]) }}" class="icon-btn" aria-label="Voltar para as cartas">
            <i class="bi bi-arrow-left"></i>
        </a>
        <p class="page-topbar__title">Detalhes da carta</p>
        <span style="width: 30px;"></span>
    </div>

    @include('pages.estoque.partials.mensagem')

    {{-- imagem grande à esquerda e informações à direita (empilhadas no mobile) --}}
    <div class="card-detail">
        <div class="card-detail__image" style="background-image: url('{{ $carta->imagem_url }}'), url('{{ $carta->imagem_padrao }}');">
            @if ($carta->foil)
                <span class="card-detail__foil"><i class="bi bi-stars"></i> foil</span>
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

            <div class="card-detail__actions">
                <button type="button" class="card-detail__btn" data-form-open="carta"
                        data-form-dados='@json($carta->dadosFormulario())'>
                    <i class="bi bi-pencil"></i>
                    Editar carta
                </button>
                <button type="button" class="card-detail__btn card-detail__btn--danger"
                        data-delete-open="{{ route('estoque.cartas.destroy', $carta) }}"
                        data-delete-title="Deseja apagar a carta &quot;{{ $carta->nome }}&quot;?">
                    <i class="bi bi-trash"></i>
                    Apagar
                </button>
            </div>
        </div>
    </div>

    @include('pages.estoque.partials.carta-form', ['jogoPadrao' => $carta->jogo])
    @include('pages.estoque.partials.apagar-popup')

@endsection
