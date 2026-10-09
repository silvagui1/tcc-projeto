@extends('layouts.app')

@section('title', $campeonato->nome)

@section('content')

    @include('campeonato.partials.mensagens')

    @php($finalizado = $campeonato->finalizado())

    {{-- Figma: acessar_camp_ativo / acessar_camp_finalizado. A bolinha no
         canto mostra o status (verde = ativo, vermelha = finalizado). --}}
    <div class="champ-detail">
        <span class="champ-detail__status" title="{{ $finalizado ? 'Finalizado' : 'Ativo' }}">
            <img src="{{ asset('images/campeonatos/' . ($finalizado ? 'status-finalizado.svg' : 'status-ativo.svg')) }}"
                 alt="{{ $finalizado ? 'Finalizado' : 'Ativo' }}" width="256.5" height="30">
        </span>

        <img class="champ-detail__banner" src="{{ $campeonato->banner }}" alt="{{ $campeonato->nome }}">

        <div class="champ-detail__body">
            <h2>{{ $campeonato->nome }}</h2>

            <div class="champ-detail__info">
                <span>{{ $campeonato->data_extenso }}</span>
                <span>{{ $campeonato->horario_curto }}</span>
                <span>{{ $campeonato->participantes->count() }} participantes</span>
                <span></span>
                <span>R${{ number_format($campeonato->valor_inscricao, 2, ',', '.') }}</span>
                <span>{{ $campeonato->deck }}</span>
            </div>

            <p class="champ-detail__desc">{!! nl2br(e($campeonato->descricao)) !!}</p>
        </div>

        <details class="champ-accordion">
            <summary>
                participantes cadastrados
                <img src="{{ asset('images/campeonatos/chevron.svg') }}" alt="" width="17" height="25">
            </summary>
            <div class="champ-accordion__content">
                @forelse ($campeonato->participantes as $participante)
                    <div class="participant-row">
                        <img class="participant-row__avatar" src="{{ $participante->foto }}" alt="">
                        <span class="participant-row__info">
                            <strong>{{ $participante->name }}</strong>
                            <span>{{ $participante->nascimento_curto }}</span>
                        </span>
                    </div>
                @empty
                    <p class="champ-empty">Nenhum participante cadastrado.</p>
                @endforelse
            </div>
        </details>

        @if ($finalizado)
            <details class="champ-accordion">
                <summary>
                    vencedores
                    <img src="{{ asset('images/campeonatos/chevron.svg') }}" alt="" width="17" height="25">
                </summary>
                <div class="champ-accordion__content">
                    @include('campeonato.partials.vencedores', ['editavel' => false])
                </div>
            </details>
        @endif

        <a href="{{ route('campeonatos.premios', $campeonato) }}" class="champ-detail__prize">
            Premiação
        </a>
    </div>

@endsection
