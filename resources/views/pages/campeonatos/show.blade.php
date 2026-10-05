@extends('layouts.app')

@section('title', $campeonato['nome'])

@section('content')

    @php($finalizado = $campeonato['status'] === 'finalizado')

    {{-- Figma: acessar_camp_ativo / acessar_camp_finalizado. A bolinha no
         canto mostra o status (verde = ativo, vermelha = finalizado). --}}
    <div class="champ-detail">
        <span class="champ-detail__status" title="{{ $finalizado ? 'Finalizado' : 'Ativo' }}">
            <img src="{{ asset('images/campeonatos/' . ($finalizado ? 'status-finalizado.svg' : 'status-ativo.svg')) }}"
                 alt="{{ $finalizado ? 'Finalizado' : 'Ativo' }}" width="256.5" height="30">
        </span>

        <img class="champ-detail__banner" src="{{ $campeonato['imagem'] }}" alt="{{ $campeonato['nome'] }}"
             style="object-position: {{ $campeonato['imagemPosicao'] ?? 'center' }};">

        <div class="champ-detail__body">
            <h2>{{ $campeonato['nome'] }}</h2>

            <div class="champ-detail__info">
                <span>{{ $campeonato['data'] }}</span>
                <span>{{ $campeonato['horario'] }}</span>
                <span>{{ count($campeonato['participantesLista']) }} participantes</span>
                <span></span>
                <span>R${{ number_format($campeonato['inscricao'], 2, ',', '.') }}</span>
                <span>{{ $campeonato['deck'] }}</span>
            </div>

            <p class="champ-detail__desc">{!! nl2br(e($campeonato['descricao'])) !!}</p>
        </div>

        <details class="champ-accordion">
            <summary>
                participantes cadastrados
                <img src="{{ asset('images/campeonatos/chevron.svg') }}" alt="" width="17" height="25">
            </summary>
            <div class="champ-accordion__content">
                @foreach ($campeonato['participantesLista'] as $participante)
                    <div class="participant-row">
                        <img class="participant-row__avatar" src="{{ $participante['avatar'] }}" alt="">
                        <span class="participant-row__info">
                            <strong>{{ $participante['nome'] }}</strong>
                            <span>{{ $participante['nascimento'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </details>

        @if ($finalizado)
            <details class="champ-accordion">
                <summary>
                    vencedores
                    <img src="{{ asset('images/campeonatos/chevron.svg') }}" alt="" width="17" height="25">
                </summary>
                <div class="champ-accordion__content">
                    @include('pages.campeonatos.partials.vencedores', ['vencedores' => $campeonato['vencedores'], 'editavel' => false])
                </div>
            </details>
        @endif

        <a href="{{ route('campeonatos.premiacoes', $campeonato['id']) }}" class="champ-detail__prize">
            Premiação
        </a>
    </div>

@endsection
