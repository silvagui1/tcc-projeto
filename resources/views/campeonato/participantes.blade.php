@extends('layouts.app')

@section('title', 'Participantes — ' . $campeonato->nome)

@section('content')

    {{-- Gerenciar só os participantes: adicionar pela busca (mesma do criar)
         e remover pela lixeira. Mesmo visual da seção "Participantes" do editar. --}}
    <div class="page-topbar">
        <a href="{{ route('campeonatos.show', $campeonato) }}" class="icon-btn" aria-label="Fechar">
            <img src="{{ asset('images/campeonatos/x.svg') }}" alt="" width="30" height="30">
        </a>
        <p class="page-topbar__title">participantes</p>
        <span style="width: 30px;"></span>
    </div>

    @include('campeonato.partials.mensagens')

    <div class="champ-form">
        <h3>{{ $campeonato->nome }}</h3>

        @unless ($campeonato->finalizado())
            <form method="POST" action="{{ route('campeonatos.participantes.store', $campeonato) }}">
                @csrf

                @include('campeonato.partials.busca-clientes')

                <button type="submit" class="champ-submit">Adicionar</button>
            </form>
        @endunless

        <p class="champ-form__count">{{ $campeonato->participantes->count() }} cadastrados</p>

        <div class="champ-participants">
            @forelse ($campeonato->participantes as $participante)
                <div class="participant-row">
                    <img class="participant-row__avatar" src="{{ $participante->foto }}" alt="">
                    <span class="participant-row__info">
                        <strong>{{ $participante->name }}</strong>
                        <span>{{ $participante->nascimento_curto }}</span>
                    </span>
                    @unless ($campeonato->finalizado())
                        <form method="POST" action="{{ route('campeonatos.participantes.destroy', [$campeonato, $participante]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="participant-row__remove" aria-label="Remover {{ $participante->name }}">
                                <img src="{{ asset('images/campeonatos/trash-dark.svg') }}" alt="" width="24" height="24">
                            </button>
                        </form>
                    @endunless
                </div>
            @empty
                <p class="champ-empty">Nenhum participante cadastrado.</p>
            @endforelse
        </div>
    </div>

@endsection
