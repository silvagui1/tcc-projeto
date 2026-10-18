@extends('layouts.app')

@section('title', 'Configurações')

{{-- no desktop a página de configurações usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')
@php
    // o intermediário vê só a própria conta e a aparência; o resto é do admin
    $ehAdmin = auth()->user()->can('admin');
    $secoes = [
        'conta' => ['Sua conta', 'bi-person-circle'],
        'aparencia' => ['Aparência', 'bi-circle-half'],
    ];
    if ($ehAdmin) {
        $secoes += [
            'usuarios' => ['Usuários', 'bi-person-gear'],
            'loja' => ['Loja', 'bi-shop'],
            'vendas' => ['Vendas', 'bi-receipt'],
            'alugueis' => ['Mesas e aluguéis', 'bi-calendar-week'],
            'estoque' => ['Estoque', 'bi-box-seam'],
            'clientes' => ['Clientes e créditos', 'bi-people'],
            'dados' => ['Dados e atividades', 'bi-clock-history'],
        ];
    }

    // "3h", "1h30"
    $textoDuracao = fn (int $minutos) => intdiv($minutos, 60).'h'.($minutos % 60 ? sprintf('%02d', $minutos % 60) : '');
@endphp

<div class="config-page" data-config-page data-url-base="{{ url('/config') }}">
    <div class="page-topbar">
        <p class="page-topbar__title">Configurações</p>
        <span class="config-subtitulo">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            @if ($ehAdmin)
                Valem para todos os computadores da loja
            @else
                As configurações da loja ficam com o administrador
            @endif
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
            @include('pages.configuracoes._conta')
            @include('pages.configuracoes._aparencia')
            @if ($ehAdmin)
                @include('pages.configuracoes._usuarios')
                @include('pages.configuracoes._loja')
                @include('pages.configuracoes._vendas')
                @include('pages.configuracoes._alugueis')
                @include('pages.configuracoes._estoque')
                @include('pages.configuracoes._clientes')
                @include('pages.configuracoes._dados')
            @endif
        </div>
    </div>

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>
    @include('clientes.partials._modal_confirmar')
</div>
@endsection
