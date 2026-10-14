@php
    $filtrosAtivos = $filtros['busca'] !== ''
        || $filtros['periodo'] !== 'tudo'
        || $filtros['pagamento'] !== 'todos'
        || $filtros['tipo'] !== 'todos';

    // Vendas da página atual agrupadas pelo dia (a lista já vem do mais
    // recente para o mais antigo).
    $grupos = collect($vendas->items())->groupBy(fn ($venda) => $venda->created_at->format('Y-m-d'));

    $rotuloDia = function (string $dia): string {
        $data = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dia);

        if ($data->isToday()) {
            return 'Hoje';
        }
        if ($data->isYesterday()) {
            return 'Ontem';
        }

        $formato = $data->year === now()->year ? 'l, d \d\e F' : 'l, d \d\e F \d\e Y';

        return \Illuminate\Support\Str::ucfirst($data->locale('pt_BR')->translatedFormat($formato));
    };
@endphp

{{-- Cabeçalho de colunas: só a partir do tablet (ver .vendas-lista-cabecalho). --}}
<div class="vendas-lista-cabecalho" aria-hidden="true">
    <span>Hora</span>
    <span>Itens</span>
    <span>Cliente</span>
    <span>Pagamento</span>
    <span class="vendas-lista-cabecalho__fim">Total</span>
</div>

<div
    class="vendas-grupos"
    data-lista
    data-total="{{ $vendas->total() }}"
    data-primeiro="{{ $vendas->firstItem() ?? 0 }}"
    data-ultimo="{{ $vendas->lastItem() ?? 0 }}"
>
    @forelse ($grupos as $dia => $vendasDoDia)
        @php $totalDia = $totaisPorDia[$dia] ?? ['total' => 0, 'quantidade' => 0]; @endphp
        <section class="vendas-dia" aria-label="{{ $rotuloDia($dia) }}">
            <header class="vendas-dia__cabecalho">
                <h3 class="vendas-dia__titulo">{{ $rotuloDia($dia) }}</h3>
                <span class="vendas-dia__total">
                    @if ($totalDia['quantidade'] > 0)
                        {{ $totalDia['quantidade'] }} {{ $totalDia['quantidade'] === 1 ? 'venda' : 'vendas' }}
                        · <strong>R$ {{ number_format($totalDia['total'], 2, ',', '.') }}</strong>
                    @else
                        só vendas canceladas
                    @endif
                </span>
            </header>
            <ul class="vendas-lista">
                @foreach ($vendasDoDia as $venda)
                    @include('vendas.partials._linha', ['venda' => $venda])
                @endforeach
            </ul>
        </section>
    @empty
        <div class="clientes-vazio">
            @if ($filtrosAtivos)
                <i class="bi bi-funnel clientes-vazio__icone" aria-hidden="true"></i>
                <p class="clientes-vazio__titulo">Nenhuma venda encontrada</p>
                <p class="clientes-vazio__texto">Nenhuma venda corresponde à busca ou aos filtros. Tente ajustar ou limpar os filtros.</p>
            @else
                <i class="bi bi-receipt clientes-vazio__icone" aria-hidden="true"></i>
                <p class="clientes-vazio__titulo">Nenhuma venda registrada</p>
                <p class="clientes-vazio__texto">Registre a primeira venda — produtos, cartas avulsas ou o aluguel de uma mesa.</p>
                <button type="button" class="botao botao--principal" data-abrir-nova-venda>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Nova venda
                </button>
            @endif
        </div>
    @endforelse
</div>

{{-- Paginação: mesmo componente da lista de clientes. Os links funcionam sem
     JS; o vendas.js intercepta para trocar de página sem recarregar. --}}
@if ($vendas->hasPages())
    @php
        $paginaAtual = $vendas->currentPage();
        $ultimaPagina = $vendas->lastPage();
        $paginas = collect(range(max(1, $paginaAtual - 1), min($ultimaPagina, $paginaAtual + 1)))
            ->push(1)
            ->push($ultimaPagina)
            ->unique()
            ->sort()
            ->values();
    @endphp
    <nav class="paginacao" data-paginacao aria-label="Paginação de vendas">
        <a
            href="{{ $vendas->onFirstPage() ? '#' : $vendas->previousPageUrl() }}"
            class="paginacao__seta {{ $vendas->onFirstPage() ? 'paginacao__seta--desabilitado' : '' }}"
            data-pagina-link
            data-pagina="{{ $paginaAtual - 1 }}"
            aria-label="Página anterior"
            @if ($vendas->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
        >
            <i class="bi bi-chevron-left"></i>
        </a>

        <div class="paginacao__numeros">
            @foreach ($paginas as $indice => $numero)
                @if ($indice > 0 && $numero - $paginas[$indice - 1] > 1)
                    <span class="paginacao__reticencias" aria-hidden="true">…</span>
                @endif
                <a
                    href="{{ $vendas->url($numero) }}"
                    class="paginacao__numero {{ $numero === $paginaAtual ? 'paginacao__numero--atual' : '' }}"
                    data-pagina-link
                    data-pagina="{{ $numero }}"
                    @if ($numero === $paginaAtual) aria-current="page" @endif
                >{{ $numero }}</a>
            @endforeach
        </div>

        <a
            href="{{ $vendas->hasMorePages() ? $vendas->nextPageUrl() : '#' }}"
            class="paginacao__seta {{ $vendas->hasMorePages() ? '' : 'paginacao__seta--desabilitado' }}"
            data-pagina-link
            data-pagina="{{ $paginaAtual + 1 }}"
            aria-label="Próxima página"
            @unless ($vendas->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless
        >
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
