@extends('layouts.app')

@section('title', $campeonato['nome'])

@section('content')

    <div class="championship-detail">
        <div class="championship-detail__banner" style="background-image: url('{{ $campeonato['imagem'] }}');"></div>
        <div class="championship-detail__body">
            <h2>{{ $campeonato['nome'] }}</h2>

            <div class="championship-detail__grid">
                <span>{{ $campeonato['data'] }}</span>
                <span>{{ $campeonato['horario'] }}</span>
                <span>{{ $campeonato['participantes'] }} participantes</span>
                <span>{{ $campeonato['deck'] }}</span>
                <span>R$ {{ number_format($campeonato['inscricao'], 2, ',', '.') }}</span>
            </div>

            <p class="championship-detail__desc">{{ $campeonato['descricao'] }}</p>
        </div>
        <a href="{{ route('campeonatos.editar', $campeonato['id']) }}" class="championship-detail__cta">
            participantes cadastrados
        </a>
        <a href="{{ route('campeonatos.premiacoes', $campeonato['id']) }}" class="btn-outline-pink">
            Premiação
        </a>
    </div>

@endsection
