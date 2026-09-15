@extends('layouts.app')

@section('title', 'Campeonatos')

@section('content')

    <div class="hero-band" style="display:flex; align-items:center;">
        <button type="button" class="icon-btn menu-toggle-inline" style="color: var(--white); font-size: 24px;"
                data-menu-toggle aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <a href="{{ route('campeonatos.criar') }}" class="btn-primary">
        Criar campeonato
        <i class="bi bi-plus-lg"></i>
    </a>

    @if ($ativo)
        <div class="championship-card">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 10px;">
                <span class="championship-status"><span class="dot dot--active"></span> ativo</span>
                <span style="color: var(--white); font-size: 12px;">{{ $ativo['data'] }}</span>
            </div>
            <div class="championship-card__banner" style="background-image: url('{{ $ativo['imagem'] }}');"></div>
            <a href="{{ route('campeonatos.show', $ativo['id']) }}" class="championship-card__access">
                acessar campeonato
                <i class="bi bi-arrow-90deg-up"></i>
            </a>
        </div>
    @endif

    <h2 style="font-size: 20px;">Outras competições</h2>

    @foreach ($outras as $campeonato)
        <div class="other-championship">
            <div class="other-championship__banner" style="background-image: url('{{ $campeonato['imagem'] }}');"></div>
            <div class="other-championship__header">
                <h3>{{ $campeonato['nome'] }}</h3>
                <span class="championship-status"><span class="dot dot--done"></span> terminado</span>
            </div>
            <p>{{ $campeonato['participantes'] }} participantes</p>
            <p>{{ $campeonato['deck'] }}</p>
            <div class="other-championship__prizes">
                @foreach ($campeonato['premios'] as $premio)
                    <p style="margin: 0;">{{ $premio }}</p>
                @endforeach
            </div>
            <div class="other-championship__footer">
                <span>{{ $campeonato['data'] }}</span>
                <span class="actions">
                    <a href="{{ route('campeonatos.editar', $campeonato['id']) }}"><i class="bi bi-pencil"></i></a>
                    <button type="button"><i class="bi bi-trash"></i></button>
                </span>
            </div>
        </div>
    @endforeach

@endsection
