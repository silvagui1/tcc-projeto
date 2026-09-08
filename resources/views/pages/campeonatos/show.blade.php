@extends('layouts.app')

@section('title', $campeonato['nome'])

@section('content')

    <div class="hero-band" style="display:flex; align-items:center;">
        <button type="button" class="icon-btn" style="color: var(--white); font-size: 24px;"><i class="bi bi-list"></i></button>
    </div>

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
        <a href="{{ route('campeonatos.premiacoes', $campeonato['id']) }}" class="championship-detail__cta">
            participantes cadastrados
        </a>
    </div>

@endsection
