@extends('layouts.app')

@section('title', 'Início')

{{-- no desktop a página inicial usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    {{-- resumo do dia: empilhado no mobile, lado a lado no desktop --}}
    <div class="home-resumo">
        <div class="stat-card stat-card--blue stat-card--split">
            <div>
                <span class="stat-card__label">lucro de hoje</span>
                <p class="stat-card__value stat-card__value--lg">R$ 125,00</p>
                <span class="stat-card__label">em 3 vendas</span>
            </div>
            <strong style="color: var(--blue-200); align-self: flex-end;">+R$ 25</strong>
        </div>

        <div class="stat-card stat-card--blue stat-grid" style="grid-template-columns: 1fr auto auto; align-items: center; margin-bottom: 0;">
            <div>
                <span class="stat-card__label">ontem</span>
                <p class="stat-card__value">R$ 210,00</p>
                <span class="stat-card__label">em 5 vendas</span>
            </div>
            <a href="{{ route('vendas') }}" style="text-align:center; color: var(--text); background: var(--bg); border-radius: 8px; padding: 12px 14px; font-size: 10px;">
                <i class="bi bi-plus-lg" style="font-size: 20px; display:block; margin-bottom:4px;"></i>
                adicionar<br>venda
            </a>
            <a href="{{ route('vendas') }}" style="text-align:center; color: var(--text); background: var(--bg); border-radius: 8px; padding: 12px 14px; font-size: 10px;">
                <i class="bi bi-arrow-90deg-up" style="font-size: 20px; display:block; margin-bottom:4px;"></i>
                ver<br>vendas
            </a>
        </div>
    </div>

    <h2 style="font-size: 20px;">Realize suas ações</h2>

    <div class="action-list">
        <a href="{{ route('clientes') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--blue-300);"><i class="bi bi-people-fill"></i></span>
            <span class="action-card__text">
                <strong>Clientes</strong>
                <span>clique aqui para visualizar ou cadastrar clientes.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('campeonatos.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--pink-300);"><i class="bi bi-trophy-fill"></i></span>
            <span class="action-card__text">
                <strong>Campeonatos</strong>
                <span>clique aqui para visualizar ou criar campeonatos.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('estoque.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--purple-300);"><i class="bi bi-box-seam-fill"></i></span>
            <span class="action-card__text">
                <strong>Estoque</strong>
                <span>clique aqui para visualizar o estoque completo.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('vendas') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--blue-300);"><i class="bi bi-credit-card-fill"></i></span>
            <span class="action-card__text">
                <strong>Vendas</strong>
                <span>clique aqui para ver sobre as vendas.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('config') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--purple-light-300);"><i class="bi bi-gear-fill"></i></span>
            <span class="action-card__text">
                <strong>Configurações</strong>
                <span>configurações.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>
    </div>

@endsection
