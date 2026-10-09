@extends('layouts.app')

@section('title', 'Criar campeonato')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('campeonatos.index') }}" class="icon-btn" aria-label="Fechar">
            <img src="{{ asset('images/campeonatos/x.svg') }}" alt="" width="30" height="30">
        </a>
        <p class="page-topbar__title">criar campeonato</p>
        <span style="width: 30px;"></span>
    </div>

    @include('campeonato.partials.mensagens')

    <form method="POST" action="{{ route('campeonatos.store') }}">
        @csrf

        <div class="champ-form">
            @include('campeonato.partials.campos-principais', ['campeonato' => null])

            <h3>Participantes</h3>

            @include('campeonato.partials.busca-clientes', ['placeholder' => 'Rog...'])
        </div>

        <button type="submit" class="champ-submit">Criar</button>
    </form>

@endsection
