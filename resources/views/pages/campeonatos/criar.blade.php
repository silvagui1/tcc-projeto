@extends('layouts.app')

@section('title', 'Criar campeonato')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('campeonatos.index') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
        <p class="page-topbar__title">criar campeonato</p>
        <span style="width: 30px;"></span>
    </div>

    {{-- somente frontend: o form ainda não envia dados, é só a estrutura da tela --}}
    <form class="form-card" method="POST" action="{{ route('campeonatos.index') }}">
        @csrf

        <h3>Informações Principais</h3>

        <div class="form-field" style="margin-bottom: 12px;">
            <label for="nome">nome do campeonato</label>
            <input type="text" id="nome" name="nome" placeholder="nome do campeonato">
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="data">data</label>
                <input type="date" id="data" name="data">
            </div>
            <div class="form-field">
                <label for="horario">Horário</label>
                <input type="time" id="horario" name="horario">
            </div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="deck">deck</label>
                <select id="deck" name="deck">
                    <option value="">selecione um deck</option>
                    <option>deck ultra max</option>
                    <option>deck base</option>
                </select>
            </div>
            <div class="form-field">
                <label for="inscricao">valor da inscrição</label>
                <input type="text" id="inscricao" name="inscricao" placeholder="R$ 0,00">
            </div>
        </div>

        <div class="form-field" style="margin-bottom: 20px;">
            <label for="descricao">descrição</label>
            <textarea id="descricao" name="descricao" placeholder="descrição do campeonato e premiação"></textarea>
        </div>

        <h3>Participantes</h3>

        <div class="participants-search">
            <input type="text" placeholder="Rog...">
            <span class="icon"><i class="bi bi-search"></i></span>
        </div>

        <div>
            @foreach ($clientesSugeridos as $cliente)
                <label class="participant-row" style="cursor: pointer;">
                    <input type="checkbox" name="participantes[]" value="{{ $cliente['id'] }}" style="margin-right: 4px;">
                    <span class="participant-row__avatar" style="background-image: url('{{ $cliente['avatar'] }}');"></span>
                    <span class="participant-row__info">
                        <strong>{{ $cliente['nome'] }}</strong>
                        <span>{{ $cliente['nascimento'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="form-submit-row">
            <button type="submit" class="btn-pink">Criar</button>
        </div>
    </form>

@endsection
