@extends('layouts.app')

@section('title', 'Clientes')

{{-- no desktop a lista vira um formato mais denso, tipo tabela, e aproveita
     uma área mais larga (ver .app-content--wide e .clientes-lista no CSS) --}}
@section('main_class', 'app-content--wide')

@section('content')
<div class="clientes-page" data-clientes-page>

    <div class="page-topbar">
        <p class="page-topbar__title">Clientes</p>
        <span class="clientes-contador" data-contador data-total="{{ $clientes->total() }}">{{ $clientes->total() }} cadastrados</span>
    </div>

    {{-- Panorama geral do cadastro — calculado sobre a base inteira (ver
         ClienteController::resumo()), não muda com busca/filtros abaixo.
         Uma tira de números, não quatro cartões: são métricas auxiliares da
         tela, não o conteúdo principal, então não competem em peso visual
         com a listagem. O número de aniversariantes só ganha cor quando há
         algum (>0) — destaque com função, não decoração fixa. --}}
    <div class="resumo-tira">
        <div class="resumo-item">
            <span class="resumo-item__valor">{{ $resumo['total'] }}</span>
            <span class="resumo-item__rotulo">clientes cadastrados</span>
        </div>
        <div class="resumo-item">
            <span class="resumo-item__valor">R$ {{ number_format($resumo['creditosEmCarteira'], 2, ',', '.') }}</span>
            <span class="resumo-item__rotulo">em carteira</span>
        </div>
        <div class="resumo-item {{ $resumo['aniversariantesNoMes'] > 0 ? 'resumo-item--destaque' : '' }}">
            <span class="resumo-item__valor">{{ $resumo['aniversariantesNoMes'] }}</span>
            <span class="resumo-item__rotulo">aniversariantes no mês</span>
        </div>
        <div class="resumo-item">
            <span class="resumo-item__valor">{{ $resumo['cadastrosNaSemana'] }}</span>
            <span class="resumo-item__rotulo">novos nos últimos 7 dias</span>
        </div>
    </div>

    {{-- No mobile, busca e ações ficam empilhadas (uma embaixo da outra). No
         desktop (.clientes-toolbar a partir de 992px) ficam lado a lado numa
         barra só, como a busca+abas de estoque em mobilenav_atualizado. --}}
    <div class="clientes-toolbar">
        <form class="busca" data-busca-form role="search">
            <i class="bi bi-search busca__icone" aria-hidden="true"></i>
            <input
                type="search"
                name="nome"
                class="busca__campo"
                placeholder="Buscar cliente por nome"
                autocomplete="off"
                data-busca-campo
            >
            <button type="button" class="busca__limpar" data-busca-limpar hidden aria-label="Limpar busca">
                <i class="bi bi-x-circle-fill"></i>
            </button>
            <span class="busca__spinner" data-busca-spinner hidden aria-hidden="true"></span>
        </form>

        <div class="clientes-acoes">
            <button type="button" class="botao botao--principal" data-abrir-criar>
                <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                Adicionar cliente
            </button>

            <button type="button" class="botao botao--fantasma" data-alternar-selecao>
                <i class="bi bi-check2-square" data-icone-selecao aria-hidden="true"></i>
                <span data-label-selecao>Selecionar</span>
            </button>
        </div>
    </div>

    {{-- Filtros da listagem (independentes do resumo acima) — status, saldo
         e aniversariantes do mês. Controles discretos (select com borda fina,
         checkbox de verdade), não badges/pills — somam-se à busca por nome e
         à ordenação (cabeçalho de colunas, ver _lista.blade.php). --}}
    <div class="clientes-filtros" data-filtros>
        <label class="filtro">
            <span class="filtro__rotulo">Status</span>
            <select data-filtro-status>
                <option value="todos">Todos</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
        </label>
        <label class="filtro">
            <span class="filtro__rotulo">Saldo</span>
            <select data-filtro-saldo>
                <option value="todos">Todos</option>
                <option value="com">Com saldo</option>
                <option value="sem">Zerado</option>
            </select>
        </label>
        <label class="filtro filtro--checkbox">
            <input type="checkbox" data-filtro-aniversariantes>
            <span>Aniversariantes do mês</span>
        </label>
    </div>

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>

    <div class="clientes-lista-wrapper" data-lista-wrapper>
        @include('clientes.partials._lista', ['clientes' => $clientes, 'termo' => $termo, 'filtros' => $filtros])
    </div>

    <div class="barra-selecao" data-barra-selecao hidden>
        <span data-selecao-contagem>0 selecionados</span>
        <div class="barra-selecao__acoes">
            <button type="button" class="botao-texto" data-cancelar-selecao>Cancelar</button>
            <button type="button" class="botao botao--neutro" data-exportar-selecionados>
                <i class="bi bi-download" aria-hidden="true"></i>
                Exportar
            </button>
            <button type="button" class="botao botao--perigo" data-confirmar-exclusao>Excluir selecionados</button>
        </div>
    </div>
</div>

@include('clientes.partials._modal_form', ['modo' => 'criar'])
@include('clientes.partials._modal_form', ['modo' => 'editar'])
@include('clientes.partials._modal_detalhes')
@include('clientes.partials._modal_confirmar')
@endsection
