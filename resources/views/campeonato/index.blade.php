@extends('layouts.app')

@section('title', 'Campeonatos')
@section('main_class', 'app-content--wide')

@section('content')

    @include('campeonato.partials.mensagens')

    {{-- No mobile só o botão aparece; no desktop entram título e subtítulo ao lado --}}
    <div class="champ-page-header">
        <div class="champ-page-header__text">
            <h1>Campeonatos</h1>
            <p>Gerencie os torneios da loja, participantes e premiações.</p>
        </div>

        <a href="{{ route('campeonatos.create') }}" class="champ-create">
            Criar campeonato
            <img src="{{ asset('images/campeonatos/plus-circle.png') }}" alt="" width="73" height="73">
        </a>
    </div>

    {{-- Campeonatos ativos: com mais de um, vira um carrossel (rolagem
         horizontal com scroll-snap, arrastando para o lado no celular). --}}
    @if ($ativos->isNotEmpty())
        <h2 class="champ-section-title--desktop">Em andamento</h2>

        <div class="champ-carousel {{ $ativos->count() > 1 ? 'champ-carousel--multi' : '' }}">
            @foreach ($ativos as $ativo)
                <div class="champ-active">
                    <div class="champ-active__top">
                        <span class="champ-status">
                            ativo
                            <img src="{{ asset('images/campeonatos/dot-green.svg') }}" alt="" width="12" height="12">
                        </span>
                        <span class="champ-active__date">{{ $ativo->data_extenso }}</span>
                    </div>

                    <img class="champ-active__banner" src="{{ $ativo->banner }}" alt="{{ $ativo->nome }}">

                    <div class="champ-active__bottom">
                        <a href="{{ route('campeonatos.show', $ativo) }}" class="champ-active__access">
                            acessar campeonato
                            <img src="{{ asset('images/campeonatos/arrow-circle-up-left.svg') }}" alt="" width="30" height="30">
                        </a>
                        <span class="champ-actions champ-actions--lg">
                            <a href="{{ route('campeonatos.edit', $ativo) }}" aria-label="Editar {{ $ativo->nome }}">
                                <img src="{{ asset('images/campeonatos/pencil.svg') }}" alt="" width="27" height="27">
                            </a>
                            <button type="button" data-delete-open="{{ route('campeonatos.destroy', $ativo) }}" aria-label="Apagar {{ $ativo->nome }}">
                                <img src="{{ asset('images/campeonatos/trash.svg') }}" alt="" width="27" height="27">
                            </button>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="champ-section-title">Outras competições</h2>

    <div class="champ-other-grid">
        @forelse ($finalizados as $campeonato)
            <div class="champ-other">
                <a href="{{ route('campeonatos.show', $campeonato) }}" class="champ-other__banner">
                    <img src="{{ $campeonato->banner }}" alt="{{ $campeonato->nome }}">
                </a>

                <div class="champ-other__header">
                    <h3>{{ $campeonato->nome }}</h3>
                    <span class="champ-status champ-status--sm">
                        Finalizado
                        <img src="{{ asset('images/campeonatos/dot-red.svg') }}" alt="" width="11" height="11">
                    </span>
                </div>

                <p>{{ $campeonato->participantes_count }} participantes</p>
                <p>{{ $campeonato->deck }}</p>
                <p class="champ-other__prizes">{!! nl2br(e($campeonato->descricao)) !!}</p>

                <div class="champ-other__footer">
                    <span>{{ $campeonato->data_extenso }}</span>
                    <span class="champ-actions">
                        <a href="{{ route('campeonatos.edit', $campeonato) }}" aria-label="Editar {{ $campeonato->nome }}">
                            <img src="{{ asset('images/campeonatos/pencil-sm.svg') }}" alt="" width="24" height="24">
                        </a>
                        <button type="button" data-delete-open="{{ route('campeonatos.destroy', $campeonato) }}" aria-label="Apagar {{ $campeonato->nome }}">
                            <img src="{{ asset('images/campeonatos/trash-sm.svg') }}" alt="" width="24" height="24">
                        </button>
                    </span>
                </div>
            </div>
        @empty
            <p class="champ-empty">Nenhum campeonato finalizado ainda.</p>
        @endforelse
    </div>

    @include('campeonato.partials.apagar-popup')

@endsection
