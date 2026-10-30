{{-- Paginação das grades do estoque (produtos e cartas). Recebe $paginador
     (o LengthAwarePaginator) e $rotulo ("produtos" / "cartas"). Mesmo visual
     da paginação de clientes (.paginacao), mas os links recarregam a página. --}}
@if ($paginador->hasPages())
    @php
        $paginaAtual = $paginador->currentPage();
        $ultimaPagina = $paginador->lastPage();

        // Janela de páginas ao redor da atual + sempre primeira/última —
        // ex: 1 … 4 5 [6] 7 8 … 12 — para não listar dezenas de números.
        $janela = 1;
        $paginas = collect(range(max(1, $paginaAtual - $janela), min($ultimaPagina, $paginaAtual + $janela)))
            ->push(1)
            ->push($ultimaPagina)
            ->unique()
            ->sort()
            ->values();
    @endphp
    <nav class="paginacao cartas-paginacao" aria-label="Paginação de {{ $rotulo }}">
        <a
            href="{{ $paginador->onFirstPage() ? '#' : $paginador->previousPageUrl() }}"
            class="paginacao__seta {{ $paginador->onFirstPage() ? 'paginacao__seta--desabilitado' : '' }}"
            aria-label="Página anterior"
            @if ($paginador->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
        >
            <i class="bi bi-chevron-left"></i>
        </a>

        <div class="paginacao__numeros">
            @foreach ($paginas as $indice => $numero)
                @if ($indice > 0 && $numero - $paginas[$indice - 1] > 1)
                    <span class="paginacao__reticencias" aria-hidden="true">…</span>
                @endif
                <a
                    href="{{ $paginador->url($numero) }}"
                    class="paginacao__numero {{ $numero === $paginaAtual ? 'paginacao__numero--atual' : '' }}"
                    @if ($numero === $paginaAtual) aria-current="page" @endif
                >{{ $numero }}</a>
            @endforeach
        </div>

        <a
            href="{{ $paginador->hasMorePages() ? $paginador->nextPageUrl() : '#' }}"
            class="paginacao__seta {{ $paginador->hasMorePages() ? '' : 'paginacao__seta--desabilitado' }}"
            aria-label="Próxima página"
            @unless ($paginador->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless
        >
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
