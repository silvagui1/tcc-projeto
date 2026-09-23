@extends('layouts.app')

@section('title', 'Premiações')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('campeonatos.show', $campeonatoId) }}" class="icon-btn" aria-label="Fechar">
            <img src="{{ asset('images/campeonatos/x.svg') }}" alt="" width="30" height="30">
        </a>
    </div>

    {{-- Um bloco por colocação: pesquisa quem ficou no lugar (sugestões vêm
         dos participantes do campeonato), quanto de crédito recebe e uma
         descrição. Somente frontend por enquanto. --}}
    <form method="POST" action="{{ route('campeonatos.premiacoes.salvar', $campeonatoId) }}">
        @csrf

        <datalist id="participantes-campeonato">
            @foreach ($participantes as $participante)
                <option value="{{ $participante['nome'] }}"></option>
            @endforeach
        </datalist>

        @foreach ($colocacoes as $indice => $colocacao)
            <div class="prize-block prize-block--{{ $indice + 1 }}">
                <div class="prize-block__header">{{ $colocacao['posicao'] }}</div>

                <div class="prize-block__body">
                    <div class="champ-search__field prize-block__search">
                        <input type="search" name="premiacoes[{{ $indice }}][participante]" list="participantes-campeonato"
                               placeholder="pesquisar" aria-label="Participante do {{ $colocacao['posicao'] }}">
                        <img src="{{ asset('images/campeonatos/search.svg') }}" alt="" width="14.6409" height="14.6409">
                    </div>

                    <div class="prize-amount" data-stepper>
                        <label class="prize-amount__input">
                            <span>R$</span>
                            <input type="text" inputmode="decimal"
                                   name="premiacoes[{{ $indice }}][valor]"
                                   placeholder="0,00"
                                   value="{{ $colocacao['valor'] > 0 ? number_format($colocacao['valor'], 2, ',', '.') : '' }}"
                                   aria-label="Crédito do {{ $colocacao['posicao'] }}"
                                   data-stepper-input>
                        </label>
                        <button type="button" class="prize-amount__step" data-stepper-step="1" aria-label="Aumentar crédito">
                            <img src="{{ asset('images/campeonatos/stepper-up.svg') }}" alt="" width="49" height="49">
                        </button>
                        <button type="button" class="prize-amount__step" data-stepper-step="-1" aria-label="Diminuir crédito">
                            <img src="{{ asset('images/campeonatos/stepper-down.svg') }}" alt="" width="49" height="49">
                        </button>
                    </div>

                    <input type="text" class="prize-block__desc"
                           name="premiacoes[{{ $indice }}][descricao]"
                           placeholder="descrição"
                           value="{{ $colocacao['descricao'] }}"
                           aria-label="Descrição do prêmio do {{ $colocacao['posicao'] }}">
                </div>
            </div>
        @endforeach

        <button type="submit" class="champ-submit champ-submit--bottom">Finalizar campeonato</button>
    </form>

@endsection
