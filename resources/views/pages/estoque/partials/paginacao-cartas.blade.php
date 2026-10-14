{{-- Paginação do catálogo de cartas (11 por página — ver CartaController::CARTAS_POR_PAGINA).
     Mesmo visual da paginação de clientes (.paginacao, em clientes.css), mas
     aqui os links recarregam a página normalmente. --}}
@if ($cartas->hasPages())
    @php
        $paginaAtual = $cartas->currentPage();
        $ultimaPagina = $cartas->lastPage();

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
    <nav class="paginacao cartas-paginacao" aria-label="Paginação de cartas">
        <a
            href="{{ $cartas->onFirstPage() ? '#' : $cartas->previousPageUrl() }}"
            class="paginacao__seta {{ $cartas->onFirstPage() ? 'paginacao__seta--desabilitado' : '' }}"
            aria-label="Página anterior"
            @if ($cartas->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
        >
            <i class="bi bi-chevron-left"></i>
        </a>

        <div class="paginacao__numeros">
            @foreach ($paginas as $indice => $numero)
                @if ($indice > 0 && $numero - $paginas[$indice - 1] > 1)
                    <span class="paginacao__reticencias" aria-hidden="true">…</span>
                @endif
                <a
                    href="{{ $cartas->url($numero) }}"
                    class="paginacao__numero {{ $numero === $paginaAtual ? 'paginacao__numero--atual' : '' }}"
                    @if ($numero === $paginaAtual) aria-current="page" @endif
                >{{ $numero }}</a>
            @endforeach
        </div>

        <a
            href="{{ $cartas->hasMorePages() ? $cartas->nextPageUrl() : '#' }}"
            class="paginacao__seta {{ $cartas->hasMorePages() ? '' : 'paginacao__seta--desabilitado' }}"
            aria-label="Próxima página"
            @unless ($cartas->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless
        >
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
