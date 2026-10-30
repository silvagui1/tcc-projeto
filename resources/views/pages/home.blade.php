@extends('layouts.app')

@section('title', 'Início')

{{-- no desktop a página inicial usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

@section('content')

    <h1 class="sr-only">Página inicial</h1>

    {{-- resumo do dia: empilhado no mobile, lado a lado no desktop --}}
    <div class="home-resumo">
        <div class="stat-card stat-card--blue stat-card--split">
            {{-- comparação justa: hoje até agora x ontem até este mesmo horário --}}
            @php $diferenca = $hoje['total'] - $ontemAteAgora['total']; @endphp
            <div>
                <span class="stat-card__label">Vendido hoje</span>
                <p class="stat-card__value stat-card__value--lg">R$ {{ number_format($hoje['total'], 2, ',', '.') }}</p>
                <span class="stat-card__label">Em {{ $hoje['quantidade'] }} {{ $hoje['quantidade'] === 1 ? 'venda' : 'vendas' }}</span>
            </div>
            {{-- verde se vendeu mais, vermelho se vendeu menos --}}
            <span class="home-diferenca {{ $diferenca > 0 ? 'home-diferenca--positiva' : ($diferenca < 0 ? 'home-diferenca--negativa' : '') }}"
                  title="Ontem até {{ now()->format('H:i') }}: R$ {{ number_format($ontemAteAgora['total'], 2, ',', '.') }}">
                <strong>{{ $diferenca > 0 ? '+' : ($diferenca < 0 ? '−' : '') }}R$ {{ number_format(abs($diferenca), 2, ',', '.') }}</strong>
                <small>vs. ontem até {{ now()->format('H:i') }}</small>
            </span>
        </div>

        <div class="stat-card stat-card--blue stat-grid home-ontem">
            <div>
                <span class="stat-card__label">Ontem (dia inteiro)</span>
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

    {{-- busca geral (o mesmo pop-up do Ctrl+K) --}}
    <button type="button" class="home-busca" data-abrir-busca>
        <i class="bi bi-search" aria-hidden="true"></i>
        <span>Buscar cliente, produto, carta, venda ou campeonato</span>
        <kbd aria-hidden="true">Ctrl K</kbd>
    </button>

    {{-- mesas reservadas para hoje, em ordem de horário --}}
    <section class="home-mesas" aria-labelledby="home-mesas-titulo">
        <div class="home-mesas__header">
            <h2 id="home-mesas-titulo">Mesas de hoje</h2>
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

    {{-- o que pede atenção hoje: estoque, campeonatos e o que acabou de ser vendido --}}
    <div class="home-paineis">
        <section class="home-painel {{ $estoqueBaixo->isNotEmpty() ? 'home-painel--alerta' : '' }}" aria-labelledby="home-estoque-titulo">
            <div class="home-painel__topo">
                <h2 id="home-estoque-titulo">
                    @if ($estoqueBaixo->isNotEmpty())
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    @endif
                    Estoque baixo
                </h2>
                <a href="{{ route('estoque.index', ['situacao' => 'baixo']) }}">Ver todos <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
            @forelse ($estoqueBaixo->take(5) as $produto)
                <a href="{{ route('estoque.produtos.show', $produto) }}" class="home-painel__linha">
                    <span>{{ $produto->nome }}</span>
                    <strong>{{ $produto->quantidade }} un.</strong>
                </a>
            @empty
                <p class="home-painel__vazio"><i class="bi bi-check-circle" aria-hidden="true"></i> Nenhum produto acabando.</p>
            @endforelse
            @if ($estoqueBaixo->count() > 5)
                <p class="home-painel__mais">e mais {{ $estoqueBaixo->count() - 5 }}</p>
            @endif
        </section>

        <section class="home-painel" aria-labelledby="home-campeonatos-titulo">
            <div class="home-painel__topo">
                <h2 id="home-campeonatos-titulo">Próximos campeonatos</h2>
                <a href="{{ route('campeonatos.index') }}">Ver todos <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
            @forelse ($proximosCampeonatos as $campeonato)
                <a href="{{ route('campeonatos.index', ['abrir' => $campeonato->id]) }}" class="home-painel__linha">
                    <span>
                        {{ $campeonato->nome }}
                        <small>{{ $campeonato->quando }}{{ $campeonato->horario_curto ? ' · '.$campeonato->horario_curto : '' }}</small>
                    </span>
                    <strong title="Inscritos">{{ $campeonato->participantes_count }} <i class="bi bi-people" aria-label="inscritos"></i></strong>
                </a>
            @empty
                <p class="home-painel__vazio">Nenhum campeonato marcado.</p>
            @endforelse
        </section>

        <section class="home-painel" aria-labelledby="home-vendas-titulo">
            <div class="home-painel__topo">
                <h2 id="home-vendas-titulo">Últimas vendas</h2>
                <a href="{{ route('vendas.index') }}">Ver todas <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
            @forelse ($ultimasVendas as $venda)
                <a href="{{ route('vendas.index', ['venda' => $venda->id]) }}" class="home-painel__linha {{ $venda->cancelada ? 'home-painel__linha--cancelada' : '' }}">
                    <span>
                        {{ $venda->resumo_itens }}
                        <small>{{ $venda->created_at->isToday() ? $venda->created_at->format('H:i') : $venda->created_at->format('d/m H:i') }} · {{ $venda->cliente?->nome ?? 'sem cliente' }}{{ $venda->cancelada ? ' · cancelada' : '' }}</small>
                    </span>
                    <strong>R$ {{ number_format($venda->total, 2, ',', '.') }}</strong>
                </a>
            @empty
                <p class="home-painel__vazio">Nenhuma venda ainda.</p>
            @endforelse
        </section>
    </div>

    {{-- atalhos para as telas (no celular o menu fica recolhido no botão
         flutuante, então estes cards continuam sendo o caminho mais rápido) --}}
    <h2 class="home-secao-titulo">Atalhos</h2>

    <div class="action-list">
        <a href="{{ route('clientes.index') }}" class="action-card">
            <span class="action-card__icon action-card__icon--clientes"><i class="bi bi-people-fill"></i></span>
            <span class="action-card__text">
                <strong>Clientes</strong>
                <span>Cadastre e consulte clientes.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('campeonatos.index') }}" class="action-card">
            <span class="action-card__icon action-card__icon--campeonatos"><i class="bi bi-trophy-fill"></i></span>
            <span class="action-card__text">
                <strong>Campeonatos</strong>
                <span>Crie e acompanhe campeonatos.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('estoque.index') }}" class="action-card">
            <span class="action-card__icon action-card__icon--estoque"><i class="bi bi-box-seam-fill"></i></span>
            <span class="action-card__text">
                <strong>Estoque</strong>
                <span>Consulte e atualize o estoque.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('vendas.index') }}" class="action-card">
            <span class="action-card__icon action-card__icon--vendas"><i class="bi bi-credit-card-fill"></i></span>
            <span class="action-card__text">
                <strong>Vendas</strong>
                <span>Registre e consulte vendas.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <a href="{{ route('config') }}" class="action-card">
            <span class="action-card__icon action-card__icon--config"><i class="bi bi-gear-fill"></i></span>
            <span class="action-card__text">
                <strong>Configurações</strong>
                <span>Ajuste a loja e o sistema.</span>
            </span>
            <span class="action-card__arrow"><i class="bi bi-chevron-right"></i></span>
        </a>
    </div>

@endsection
