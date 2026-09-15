@extends('layouts.app')

@section('title', 'Premiações')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('campeonatos.show', $campeonatoId) }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
        <p class="page-topbar__title">Premiações</p>
        <span style="width: 30px;"></span>
    </div>

    {{-- Uma tela por colocação: quem ficou no lugar, quanto de crédito recebe
         e uma observação. Somente frontend por enquanto. --}}
    <form method="POST" action="{{ route('campeonatos.premiacoes.salvar', $campeonatoId) }}">
        @csrf

        @foreach ($colocacoes as $indice => $colocacao)
            <div class="prize-block">
                <div class="prize-block__header">{{ $colocacao['posicao'] }}</div>

                <div class="prize-block__body">
                    <div class="prize-winner">
                        <span class="avatar" style="background-image: url('{{ $colocacao['avatar'] }}');"></span>
                        <span class="prize-winner__info">
                            <strong>{{ $colocacao['nome'] }}</strong>
                            <span>{{ $colocacao['nascimento'] }}</span>
                        </span>
                    </div>

                    <div class="prize-amount">
                        <input type="text"
                               name="premiacoes[{{ $indice }}][valor]"
                               value="R$ {{ number_format($colocacao['valor'], 2, ',', '.') }}"
                               aria-label="Crédito do {{ $colocacao['posicao'] }}">
                        <button type="button" class="stepper" aria-label="Diminuir crédito">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <button type="button" class="stepper" aria-label="Aumentar crédito">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <textarea name="premiacoes[{{ $indice }}][descricao]"
                              placeholder="descrição"
                              aria-label="Descrição do prêmio do {{ $colocacao['posicao'] }}">{{ $colocacao['descricao'] }}</textarea>
                </div>
            </div>
        @endforeach

        <div class="form-submit-row">
            <button type="submit" class="btn-pink">Finalizar campeonato</button>
        </div>
    </form>

@endsection
