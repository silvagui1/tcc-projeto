@extends('layouts.app')

@section('title', 'Editar campeonato')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('campeonatos.index') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
        <p class="page-topbar__title">Editar campeonato</p>
        <span style="width: 30px;"></span>
    </div>

    <form class="form-card" method="POST" action="{{ route('campeonatos.editar', $campeonato['id']) }}">
        @csrf
        @method('PUT')

        <h3>Informações Principais</h3>

        <div class="form-field" style="margin-bottom: 12px;">
            <label for="nome">nome do campeonato</label>
            <input type="text" id="nome" name="nome" value="{{ $campeonato['nome'] }}">
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="data">data</label>
                <input type="text" id="data" name="data" value="{{ $campeonato['data'] }}">
            </div>
            <div class="form-field">
                <label for="horario">Horário</label>
                <input type="text" id="horario" name="horario" value="{{ $campeonato['horario'] }}">
            </div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="deck">deck</label>
                <input type="text" id="deck" name="deck" value="{{ $campeonato['deck'] }}">
            </div>
            <div class="form-field">
                <label for="inscricao">valor da inscrição</label>
                <input type="text" id="inscricao" name="inscricao" value="R$ {{ number_format($campeonato['inscricao'], 2, ',', '.') }}">
            </div>
        </div>

        <div class="form-field" style="margin-bottom: 20px;">
            <label for="descricao">premiação</label>
            <textarea id="descricao" name="descricao">{{ $campeonato['descricao'] }}</textarea>
        </div>

        <h3>Participantes</h3>
        <p style="font-size: 12px; color: #4a4242; margin-top: -6px;">{{ count($campeonato['participantesLista']) }} cadastrados</p>

        <div>
            @foreach ($campeonato['participantesLista'] as $participante)
                <div class="participant-row">
                    <span class="participant-row__avatar" style="background-image: url('{{ $participante['avatar'] }}');"></span>
                    <span class="participant-row__info">
                        <strong>{{ $participante['nome'] }}</strong>
                        <span>{{ $participante['nascimento'] }}</span>
                    </span>
                    <button type="button" class="participant-row__remove"><i class="bi bi-trash"></i></button>
                </div>
            @endforeach
        </div>

        <div class="form-submit-row">
            <button type="submit" class="btn-pink">Salvar alterações</button>
        </div>
    </form>

@endsection
