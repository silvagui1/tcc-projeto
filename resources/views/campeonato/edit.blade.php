@extends('layouts.app')

@section('title', 'Editar campeonato')

@section('content')

    {{-- Anotação do Figma: "campeonatos colocados como terminado ficam sem
         poder mexer ao serem acessados na página principal" — então, se o
         campeonato já está finalizado, a tela abre só para leitura. --}}
    @php($bloqueado = $campeonato->finalizado())

    <div class="page-topbar">
        <a href="{{ route('campeonatos.index') }}" class="icon-btn" aria-label="Fechar">
            <img src="{{ asset('images/campeonatos/x.svg') }}" alt="" width="30" height="30">
        </a>
        <p class="page-topbar__title">Editar campeonato</p>
        <span style="width: 30px;"></span>
    </div>

    @include('campeonato.partials.mensagens')

    @if ($bloqueado && ! session('error'))
        <p class="champ-locked">Este campeonato já foi finalizado e não pode mais ser alterado.</p>
    @endif

    <form method="POST" action="{{ route('campeonatos.update', $campeonato) }}">
        @csrf
        @method('PUT')

        <fieldset class="champ-form" {{ $bloqueado ? 'disabled' : '' }}>
            @include('campeonato.partials.campos-principais')

            <h3>Participantes</h3>

            @unless ($bloqueado)
                @include('campeonato.partials.busca-clientes')
            @endunless

            <p class="champ-form__count">{{ $campeonato->participantes->count() }} cadastrados</p>

            <div class="champ-participants">
                @foreach ($campeonato->participantes as $participante)
                    <div class="participant-row">
                        <img class="participant-row__avatar" src="{{ $participante->foto }}" alt="">
                        <span class="participant-row__info">
                            <strong>{{ $participante->name }}</strong>
                            <span>{{ $participante->nascimento_curto }}</span>
                        </span>
                        {{-- form="..." envia o form de remoção que fica fora deste
                             (não dá para ter um <form> dentro de outro) --}}
                        <button type="submit" class="participant-row__remove" form="remover-participante-{{ $participante->id }}"
                                aria-label="Remover {{ $participante->name }}">
                            <img src="{{ asset('images/campeonatos/trash-dark.svg') }}" alt="" width="24" height="24">
                        </button>
                    </div>
                @endforeach
            </div>

            <h3>Vencedores</h3>

            @include('campeonato.partials.vencedores', ['editavel' => ! $bloqueado])
        </fieldset>

        @unless ($bloqueado)
            <button type="submit" class="champ-submit">Salvar alterações</button>
        @endunless
    </form>

    @unless ($bloqueado)
        @foreach ($campeonato->participantes as $participante)
            <form id="remover-participante-{{ $participante->id }}" method="POST" hidden
                  action="{{ route('campeonatos.participantes.destroy', [$campeonato, $participante]) }}">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endunless

@endsection
