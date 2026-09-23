@extends('layouts.app')

@section('title', 'Campeonatos')

@section('content')

    <a href="{{ route('campeonatos.criar') }}" class="champ-create">
        Criar campeonato
        <img src="{{ asset('images/campeonatos/plus-circle.png') }}" alt="" width="73" height="73">
    </a>

    {{-- Campeonatos ativos: com mais de um, vira um carrossel (rolagem
         horizontal com scroll-snap, arrastando para o lado no celular). --}}
    @if (count($ativos))
        <div class="champ-carousel {{ count($ativos) > 1 ? 'champ-carousel--multi' : '' }}">
            @foreach ($ativos as $ativo)
                <div class="champ-active">
                    <div class="champ-active__top">
                        <span class="champ-status">
                            ativo
                            <img src="{{ asset('images/campeonatos/dot-green.svg') }}" alt="" width="12" height="12">
                        </span>
                        <span class="champ-active__date">{{ $ativo['data'] }}</span>
                    </div>

                    <img class="champ-active__banner" src="{{ $ativo['imagem'] }}" alt="{{ $ativo['nome'] }}">

                    <div class="champ-active__bottom">
                        <a href="{{ route('campeonatos.show', $ativo['id']) }}" class="champ-active__access">
                            acessar campeonato
                            <img src="{{ asset('images/campeonatos/arrow-circle-up-left.svg') }}" alt="" width="30" height="30">
                        </a>
                        <span class="champ-actions champ-actions--lg">
                            <a href="{{ route('campeonatos.editar', $ativo['id']) }}" aria-label="Editar {{ $ativo['nome'] }}">
                                <img src="{{ asset('images/campeonatos/pencil.svg') }}" alt="" width="27" height="27">
                            </a>
                            <button type="button" data-delete-open="{{ route('campeonatos.apagar', $ativo['id']) }}" aria-label="Apagar {{ $ativo['nome'] }}">
                                <img src="{{ asset('images/campeonatos/trash.svg') }}" alt="" width="27" height="27">
                            </button>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="champ-section-title">Outras competições</h2>

    @foreach ($outras as $campeonato)
        <div class="champ-other">
            <a href="{{ route('campeonatos.show', $campeonato['id']) }}" class="champ-other__banner">
                <img src="{{ $campeonato['imagem'] }}" alt="{{ $campeonato['nome'] }}"
                     style="object-position: {{ $campeonato['imagemPosicao'] ?? 'center' }};">
            </a>

            <div class="champ-other__header">
                <h3>{{ $campeonato['nome'] }}</h3>
                <span class="champ-status champ-status--sm">
                    Finalizado
                    <img src="{{ asset('images/campeonatos/dot-red.svg') }}" alt="" width="11" height="11">
                </span>
            </div>

            <p>{{ count($campeonato['participantesLista']) }} participantes</p>
            <p>{{ $campeonato['deck'] }}</p>
            <p class="champ-other__prizes">{!! nl2br(e($campeonato['descricao'])) !!}</p>

            <div class="champ-other__footer">
                <span>{{ $campeonato['data'] }}</span>
                <span class="champ-actions">
                    <a href="{{ route('campeonatos.editar', $campeonato['id']) }}" aria-label="Editar {{ $campeonato['nome'] }}">
                        <img src="{{ asset('images/campeonatos/pencil-sm.svg') }}" alt="" width="24" height="24">
                    </a>
                    <button type="button" data-delete-open="{{ route('campeonatos.apagar', $campeonato['id']) }}" aria-label="Apagar {{ $campeonato['nome'] }}">
                        <img src="{{ asset('images/campeonatos/trash-sm.svg') }}" alt="" width="24" height="24">
                    </button>
                </span>
            </div>
        </div>
    @endforeach

    @include('pages.campeonatos.partials.apagar-popup')

@endsection
