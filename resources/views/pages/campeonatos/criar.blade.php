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

    {{-- somente frontend: o form ainda não salva nada, é só a estrutura da tela --}}
    <form method="POST" action="{{ route('campeonatos.index') }}">
        @csrf

        <div class="champ-form">
            @include('pages.campeonatos.partials.campos-principais', ['campeonato' => null])

            <h3>Participantes</h3>

            {{-- busca: digitar filtra a lista de clientes logo abaixo; o "+"
                 marca o cliente como participante (checkbox escondido) --}}
            <div class="champ-search" data-participant-search>
                <div class="champ-search__field">
                    <input type="search" placeholder="Rog..." aria-label="Pesquisar clientes" data-participant-search-input>
                    <img src="{{ asset('images/campeonatos/search.svg') }}" alt="" width="14.6409" height="14.6409">
                </div>

                <div class="champ-search__results">
                    @foreach ($clientesSugeridos as $cliente)
                        <label class="participant-row participant-row--pick" data-participant-name="{{ $cliente['nome'] }}">
                            <img class="participant-row__avatar" src="{{ $cliente['avatar'] }}" alt="">
                            <span class="participant-row__info">
                                <strong>{{ $cliente['nome'] }}</strong>
                                <span>{{ $cliente['nascimento'] }}</span>
                            </span>
                            <input type="checkbox" name="participantes[]" value="{{ $cliente['id'] }}">
                            <img class="participant-row__add" src="{{ asset('images/campeonatos/plus-circle.png') }}" alt="Adicionar" width="24" height="24">
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="submit" class="champ-submit">Criar</button>
    </form>

@endsection
