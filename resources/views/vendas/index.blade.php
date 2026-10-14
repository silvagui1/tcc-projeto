@extends('layouts.app')

@section('title', 'Vendas')

{{-- mesma área larga de Clientes/Estoque no desktop (lista em formato de tabela) --}}
@section('main_class', 'app-content--wide')

@section('content')
<div
    class="vendas-page"
    data-vendas-page
    data-aba="{{ $aba }}"
    data-url-base="{{ url('/vendas') }}"
    @if ($abrirNovaVenda) data-iniciar-nova-venda @endif
>
    {{-- Dados que o vendas.js precisa e que vêm do banco/back-end: mesas
         (formulário de aluguel e modal de mesas), rótulos e o dia aberto
         na agenda. --}}
    @php $ajustes = app(\App\Services\Configuracoes::class); @endphp
    <script type="application/json" data-vendas-config>{!! json_encode([
        'mesas' => $mesas->map->dadosJson()->values(),
        'tiposJogo' => $tiposJogo,
        'formasPagamento' => collect($formasPagamento)->map(fn ($f) => $f['rotulo']),
        'hoje' => today()->format('Y-m-d'),
        'dia' => isset($dia) ? $dia->format('Y-m-d') : today()->format('Y-m-d'),
        // Configurações > Vendas e > Mesas e aluguéis
        'permitirCreditos' => (bool) $ajustes->get('vendas.permitir_creditos'),
        'exigirMotivoCancelamento' => (bool) $ajustes->get('vendas.exigir_motivo_cancelamento'),
        'maxSemanas' => (int) $ajustes->get('alugueis.max_semanas'),
        'duracaoPadrao' => (int) $ajustes->get('alugueis.duracao_padrao'),
        'duracaoMaxima' => (int) $ajustes->get('alugueis.duracao_maxima'),
        'horario' => $ajustes->get('loja.horario'),
    ], JSON_HEX_TAG) !!}</script>

    <div class="page-topbar">
        <p class="page-topbar__title">Vendas</p>
        <span class="vendas-contador">
            {{ $resumo['mesQuantidade'] }} {{ $resumo['mesQuantidade'] === 1 ? 'venda' : 'vendas' }} este mês
        </span>
    </div>

    {{-- Panorama geral (não muda com filtros) — mesma tira de números da tela
         de Clientes. "Mesas hoje" leva direto para a agenda. --}}
    <div class="resumo-tira">
        <div class="resumo-item {{ $resumo['hojeQuantidade'] > 0 ? 'resumo-item--destaque' : '' }}">
            <span class="resumo-item__valor">R$ {{ number_format($resumo['hojeTotal'], 2, ',', '.') }}</span>
            <span class="resumo-item__rotulo">
                vendido hoje{{ $resumo['hojeQuantidade'] > 0 ? ' · '.$resumo['hojeQuantidade'].($resumo['hojeQuantidade'] === 1 ? ' venda' : ' vendas') : '' }}
            </span>
        </div>
        <div class="resumo-item">
            <span class="resumo-item__valor">R$ {{ number_format($resumo['mesTotal'], 2, ',', '.') }}</span>
            <span class="resumo-item__rotulo">no mês</span>
        </div>
        <div class="resumo-item">
            <span class="resumo-item__valor">R$ {{ number_format($resumo['ticketMedio'], 2, ',', '.') }}</span>
            <span class="resumo-item__rotulo">ticket médio</span>
        </div>
        <a href="{{ route('vendas.index', ['aba' => 'alugueis']) }}" class="resumo-item resumo-item--interativo">
            <span class="resumo-item__valor">{{ $resumo['reservasHoje'] }}</span>
            <span class="resumo-item__rotulo">
                {{ $resumo['reservasHoje'] === 1 ? 'mesa reservada hoje' : 'mesas reservadas hoje' }}@if ($resumo['proximaReserva']) · próxima às {{ $resumo['proximaReserva']->inicio->format('H:i') }}@endif
            </span>
        </a>
    </div>

    {{-- Abas + ação principal de cada aba. No telefone ficam empilhadas; a
         partir do tablet, lado a lado (igual à toolbar de Clientes). --}}
    <div class="vendas-toolbar">
        <nav class="vendas-abas" aria-label="Seções de vendas">
            <a href="{{ route('vendas.index') }}" class="vendas-abas__item {{ $aba === 'vendas' ? 'is-active' : '' }}" @if ($aba === 'vendas') aria-current="page" @endif>
                <i class="bi bi-receipt" aria-hidden="true"></i>
                Vendas
            </a>
            <a href="{{ route('vendas.index', ['aba' => 'alugueis']) }}" class="vendas-abas__item {{ $aba === 'alugueis' ? 'is-active' : '' }}" @if ($aba === 'alugueis') aria-current="page" @endif>
                <i class="bi bi-calendar-week" aria-hidden="true"></i>
                Aluguéis de mesas
            </a>
        </nav>

        <div class="clientes-acoes vendas-acoes">
            @if ($aba === 'vendas')
                <button type="button" class="botao botao--principal" data-abrir-nova-venda>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Nova venda
                </button>
            @else
                <button type="button" class="botao botao--principal" data-abrir-novo-aluguel>
                    <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                    Novo aluguel
                </button>
                <button type="button" class="botao botao--fantasma" data-abrir-mesas>
                    <i class="bi bi-grid-3x2-gap" aria-hidden="true"></i>
                    Mesas
                </button>
            @endif
        </div>
    </div>

    @if ($aba === 'vendas')
        @include('vendas.partials._aba_vendas')
    @else
        @include('vendas.partials._aba_alugueis')
    @endif

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>

    @include('vendas.partials._modal_venda')
    @include('vendas.partials._modal_detalhes_venda')
    @include('vendas.partials._modal_aluguel')
    @include('vendas.partials._modal_detalhes_aluguel')
    @include('vendas.partials._modal_cancelar_aluguel')
    @include('vendas.partials._modal_cancelar_venda')
    @include('vendas.partials._modal_mesas')
    @include('clientes.partials._modal_confirmar')
</div>
@endsection
