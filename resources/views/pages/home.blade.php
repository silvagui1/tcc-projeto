@extends('layouts.app')

@section('title', 'Início')

{{-- no desktop a página inicial usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    {{-- resumo do dia: empilhado no mobile, lado a lado no desktop --}}
    <div class="home-resumo">
        <div class="stat-card stat-card--blue stat-card--split">
            @php $diferenca = $hoje['total'] - $ontem['total']; @endphp
            <div>
                <span class="stat-card__label">vendido hoje</span>
                <p class="stat-card__value stat-card__value--lg">R$ {{ number_format($hoje['total'], 2, ',', '.') }}</p>
                <span class="stat-card__label">em {{ $hoje['quantidade'] }} {{ $hoje['quantidade'] === 1 ? 'venda' : 'vendas' }}</span>
            </div>
            {{-- diferença para ontem --}}
            <strong style="color: var(--blue-200); align-self: flex-end;" title="em relação a ontem">
                {{ $diferenca >= 0 ? '+' : '−' }}R$ {{ number_format(abs($diferenca), 2, ',', '.') }}
            </strong>
        </div>

        <div class="stat-card stat-card--blue stat-grid" style="grid-template-columns: 1fr auto auto; align-items: center; margin-bottom: 0;">
            <div>
                <span class="stat-card__label">ontem</span>
                <p class="stat-card__value">R$ {{ number_format($ontem['total'], 2, ',', '.') }}</p>
                <span class="stat-card__label">em {{ $ontem['quantidade'] }} {{ $ontem['quantidade'] === 1 ? 'venda' : 'vendas' }}</span>
            </div>
            {{-- ?nova=1 abre o modal de nova venda direto --}}
            <a href="{{ route('vendas.index', ['nova' => 1]) }}" style="text-align:center; color: var(--text); background: var(--bg); border-radius: 8px; padding: 12px 14px; font-size: 10px;">
                <i class="bi bi-plus-lg" style="font-size: 20px; display:block; margin-bottom:4px;"></i>
                adicionar<br>venda
            </a>
            <a href="{{ route('vendas.index') }}" style="text-align:center; color: var(--text); background: var(--bg); border-radius: 8px; padding: 12px 14px; font-size: 10px;">
                <i class="bi bi-arrow-90deg-up" style="font-size: 20px; display:block; margin-bottom:4px;"></i>
                ver<br>vendas
            </a>
        </div>
    </div>

    @if ($estoqueBaixo > 0)
        <a href="{{ route('estoque.index') }}" class="alerta-estoque alerta-estoque--link">
            <i class="bi bi-exclamation-triangle-fill alerta-estoque__icone" aria-hidden="true"></i>
            <div class="alerta-estoque__texto">
                <strong>{{ $estoqueBaixo }} {{ $estoqueBaixo === 1 ? 'produto está' : 'produtos estão' }} com estoque baixo</strong>
                <span>Toque para ver no estoque.</span>
            </div>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    @endif

    <h2 style="font-size: 20px;">Realize suas ações</h2>

    <div class="action-list">
        <a href="{{ route('clientes.index') }}" class="action-card">
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

        <a href="{{ route('vendas.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--yellow-400);"><i class="bi bi-credit-card-fill"></i></span>
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
