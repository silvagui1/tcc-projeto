@extends('layouts.app')

@section('title', 'Configurações')

{{-- no desktop a página de configurações usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')
@php
    $secoes = [
        'aparencia' => ['Aparência', 'bi-circle-half'],
        'loja' => ['Loja', 'bi-shop'],
        'vendas' => ['Vendas', 'bi-receipt'],
        'alugueis' => ['Mesas e aluguéis', 'bi-calendar-week'],
        'estoque' => ['Estoque', 'bi-box-seam'],
        'clientes' => ['Clientes e créditos', 'bi-people'],
        'dados' => ['Dados e atividades', 'bi-clock-history'],
    ];

    // "3h", "1h30"
    $textoDuracao = fn (int $minutos) => intdiv($minutos, 60).'h'.($minutos % 60 ? sprintf('%02d', $minutos % 60) : '');
@endphp

<div class="config-page" data-config-page data-url-base="{{ url('/config') }}">
    <div class="page-topbar">
        <p class="page-topbar__title">Configurações</p>
        <span class="config-subtitulo">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Valem para todos os computadores da loja
        </span>
    </div>

    <div class="config-layout">
        {{-- Índice das seções: coluna fixa no desktop, faixa rolável no telefone --}}
        <nav class="config-nav" aria-label="Seções das configurações" data-config-nav>
            @foreach ($secoes as $id => [$rotulo, $icone])
                <a href="#{{ $id }}" class="config-nav__item" data-config-nav-item="{{ $id }}">
                    <i class="bi {{ $icone }}" aria-hidden="true"></i>
                    <span>{{ $rotulo }}</span>
                </a>
            @endforeach
        </nav>

        <div class="config-conteudo">
            @include('pages.configuracoes._aparencia')
            @include('pages.configuracoes._loja')
            @include('pages.configuracoes._vendas')
            @include('pages.configuracoes._alugueis')
            @include('pages.configuracoes._estoque')
            @include('pages.configuracoes._clientes')
            @include('pages.configuracoes._dados')
        </div>
    </div>

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>
    @include('clientes.partials._modal_confirmar')
</div>
@endsection
