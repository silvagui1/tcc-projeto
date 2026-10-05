@extends('layouts.app')

@section('title', $carta['nome'])

{{-- no desktop usa a mesma área larga do estoque (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('estoque.cartas', ['jogo' => $carta['jogo']]) }}" class="icon-btn" aria-label="Voltar para as cartas">
            <i class="bi bi-arrow-left"></i>
        </a>
        <p class="page-topbar__title">Detalhes da carta</p>
        <span style="width: 30px;"></span>
    </div>

    {{-- imagem grande à esquerda e informações à direita (empilhadas no mobile) --}}
    <div class="card-detail">
        <div class="card-detail__image" style="background-image: url('{{ $carta['imagem'] }}'), url('{{ $carta['imagemPadrao'] }}');">
            @if ($carta['foil'])
                <span class="card-detail__foil"><i class="bi bi-stars"></i> foil</span>
            @endif
        </div>

        <div class="card-detail__info">
            <span class="card-detail__game">{{ ucfirst($carta['jogo']) }}</span>
            <h1>{{ $carta['nome'] }}</h1>

            <p class="card-detail__price">
                R$ {{ number_format($carta['preco'], 2, ',', '.') }}
                <span>por unidade</span>
            </p>

            <div class="trading-card__meta">
                @foreach ($carta['tags'] as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </div>

            <dl class="card-detail__specs">
                <div><dt>Estado</dt><dd>{{ $carta['estado'] }}</dd></div>
                <div><dt>Coleção</dt><dd>{{ $carta['colecao'] }}</dd></div>
                <div><dt>Raridade</dt><dd>{{ $carta['raridade'] }}</dd></div>
                <div><dt>Idioma</dt><dd>{{ $carta['idioma'] }}</dd></div>
                <div><dt>Foil</dt><dd>{{ $carta['foil'] ? 'Sim' : 'Não' }}</dd></div>
                <div><dt>Quantidade em estoque</dt><dd>{{ $carta['quantidade'] }}</dd></div>
                <div class="card-detail__total">
                    <dt>Valor total em estoque</dt>
                    <dd>R$ {{ number_format($carta['preco'] * $carta['quantidade'], 2, ',', '.') }}</dd>
                </div>
            </dl>

            {{-- Somente frontend por enquanto: os botões ainda não fazem nada,
                 os atributos data-card-edit / data-card-delete ficam prontos. --}}
            <div class="card-detail__actions">
                <button type="button" class="card-detail__btn" data-card-edit>
                    <i class="bi bi-pencil"></i>
                    Editar carta
                </button>
                <button type="button" class="card-detail__btn card-detail__btn--danger" data-card-delete>
                    <i class="bi bi-trash"></i>
                    Apagar
                </button>
            </div>
        </div>
    </div>

@endsection
