@extends('layouts.app')

@section('title', 'Campeonatos')

{{-- mesma área larga de Clientes/Vendas/Estoque no desktop --}}
@section('main_class', 'app-content--wide')

@section('content')
<div class="campeonatos-page" data-campeonatos-page>

    {{-- mesmo cabeçalho de Clientes e Vendas: título + contador --}}
    <div class="page-topbar">
        <h1 class="page-topbar__title">Campeonatos</h1>
        @php($totalCampeonatos = $resumo['ativos'] + $resumo['finalizados'])
        <span class="campeonatos-contador">
            {{ $totalCampeonatos }} {{ $totalCampeonatos === 1 ? 'cadastrado' : 'cadastrados' }}
        </span>
    </div>

    {{-- resumo e avisos: atualizados sem recarregar a página quando o
         pop-up fecha depois de uma mudança (ver campeonatos.js) --}}
    <div data-champ-atualizavel="topo">
        @include('pages.campeonatos.partials.resumo')
    </div>

    @include('pages.campeonatos.partials.mensagens')

    @include('pages.campeonatos.partials.filtros', ['totalResultados' => count($ativos) + $paginaFinalizados->total()])

    <div data-champ-atualizavel="lista">
        @include('pages.campeonatos.partials.lista')
    </div>

    {{-- Pop-up único dos campeonatos (como os de Estoque e Clientes): o
         conteúdo — criar, ver, editar, premiar — é buscado nas rotas abaixo
         e trocado aqui dentro (ver resources/js/campeonatos.js). --}}
    <dialog class="champ-modal" data-champ-modal aria-label="Campeonato"
            data-url-criar="{{ route('campeonatos.criar') }}"
            data-url-copiar="{{ route('campeonatos.criar', ['copiar' => '__ID__']) }}"
            data-url-ver="{{ route('campeonatos.show', '__ID__') }}"
            data-url-checkin="{{ route('campeonatos.show', ['campeonato' => '__ID__', 'modo' => 'checkin']) }}"
            data-url-editar="{{ route('campeonatos.editar', '__ID__') }}"
            data-url-premiar="{{ route('campeonatos.premiacoes', '__ID__') }}">
        <div class="champ-modal__conteudo" data-champ-modal-conteudo></div>
        <div class="champ-modal__carregando" data-champ-modal-carregando hidden>
            <span class="champ-spinner" aria-hidden="true"></span>
            <span class="sr-only">Carregando…</span>
        </div>
    </dialog>

    @include('pages.campeonatos.partials.apagar-popup')
    @include('pages.campeonatos.partials.confirmar')

</div>
@endsection
