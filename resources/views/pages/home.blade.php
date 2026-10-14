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
                <span class="stat-card__label">Vendido hoje</span>
                <p class="stat-card__value stat-card__value--lg">R$ {{ number_format($hoje['total'], 2, ',', '.') }}</p>
                <span class="stat-card__label">Em {{ $hoje['quantidade'] }} {{ $hoje['quantidade'] === 1 ? 'venda' : 'vendas' }}</span>
            </div>
            {{-- diferença para ontem: verde se vendeu mais, vermelho se vendeu menos --}}
            <span class="home-diferenca {{ $diferenca > 0 ? 'home-diferenca--positiva' : ($diferenca < 0 ? 'home-diferenca--negativa' : '') }}"
                  title="Em relação ao total de ontem">
                <strong>{{ $diferenca > 0 ? '+' : ($diferenca < 0 ? '−' : '') }}R$ {{ number_format(abs($diferenca), 2, ',', '.') }}</strong>
                <small>vs. ontem</small>
            </span>
        </div>

        <div class="stat-card stat-card--blue stat-grid home-ontem">
            <div>
                <span class="stat-card__label">Ontem</span>
                <p class="stat-card__value">R$ {{ number_format($ontem['total'], 2, ',', '.') }}</p>
                <span class="stat-card__label">Em {{ $ontem['quantidade'] }} {{ $ontem['quantidade'] === 1 ? 'venda' : 'vendas' }}</span>
            </div>
            {{-- ?nova=1 abre o modal de nova venda direto --}}
            <a href="{{ route('vendas.index', ['nova' => 1]) }}" class="home-atalho home-atalho--principal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Nova venda</span>
            </a>
            <a href="{{ route('vendas.index') }}" class="home-atalho">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                <span>Ver vendas</span>
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

    {{-- mesas reservadas para hoje, em ordem de horário --}}
    <section class="home-mesas">
        <div class="home-mesas__header">
            <h2>Mesas de hoje</h2>
            <a href="{{ route('vendas.index', ['aba' => 'alugueis']) }}">
                Ver agenda <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </div>

        @forelse ($alugueisHoje as $aluguel)
            @php
                $estado = now()->gte($aluguel->fim) ? 'encerrado' : (now()->gte($aluguel->inicio) ? 'agora' : 'proximo');
            @endphp
            <div class="home-mesa home-mesa--{{ $estado }}">
                <span class="home-mesa__horario">
                    <strong>{{ $aluguel->inicio->format('H:i') }}</strong>
                    <small>até {{ $aluguel->fim->format('H:i') }}</small>
                </span>
                <span class="home-mesa__corpo">
                    <strong>{{ $aluguel->mesa->nome }} · {{ $aluguel->nome_exibicao }}</strong>
                    <span class="jogo-chip jogo-chip--{{ $aluguel->tipo_jogo_cor }}">
                        <i class="bi {{ $aluguel->tipo_jogo_icone }}" aria-hidden="true"></i>
                        {{ $aluguel->jogo ?: $aluguel->tipo_jogo_rotulo }}
                    </span>
                </span>
                @if ($estado === 'agora')
                    <span class="home-mesa__selo">Agora</span>
                @elseif ($estado === 'encerrado')
                    <span class="home-mesa__selo home-mesa__selo--fim">Encerrado</span>
                @endif
            </div>
        @empty
            <p class="home-mesas__vazio">Nenhuma mesa reservada para hoje.</p>
        @endforelse
    </section>

    <h2 style="font-size: 20px;">Realize suas ações</h2>

    <div class="action-list">
        <a href="{{ route('clientes.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--blue-300);"><i class="bi bi-people-fill"></i></span>
            <span class="action-card__text">
                <strong>Clientes</strong>
                <span>Cadastre e consulte clientes.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('campeonatos.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--pink-300);"><i class="bi bi-trophy-fill"></i></span>
            <span class="action-card__text">
                <strong>Campeonatos</strong>
                <span>Crie e acompanhe campeonatos.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('estoque.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--purple-300);"><i class="bi bi-box-seam-fill"></i></span>
            <span class="action-card__text">
                <strong>Estoque</strong>
                <span>Consulte e atualize o estoque.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('vendas.index') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--yellow-400);"><i class="bi bi-credit-card-fill"></i></span>
            <span class="action-card__text">
                <strong>Vendas</strong>
                <span>Registre e consulte vendas.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('config') }}" class="action-card">
            <span class="action-card__icon" style="background: var(--purple-light-300);"><i class="bi bi-gear-fill"></i></span>
            <span class="action-card__text">
                <strong>Configurações</strong>
                <span>Ajuste a loja e o sistema.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>
    </div>

@endsection
