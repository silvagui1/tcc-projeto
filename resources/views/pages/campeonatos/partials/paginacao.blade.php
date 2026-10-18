{{-- Paginação dos campeonatos finalizados — mesmo visual (.paginacao) de
     Clientes e Vendas: números com reticências e setas. --}}
@if ($pagina->hasPages())
    @php
        $atual = $pagina->currentPage();
        $ultima = $pagina->lastPage();
        $numeros = collect(range(max(1, $atual - 1), min($ultima, $atual + 1)))
            ->push(1)->push($ultima)->unique()->sort()->values();
    @endphp
    <nav class="paginacao" aria-label="Páginas de campeonatos finalizados">
        <a href="{{ $pagina->onFirstPage() ? '#' : $pagina->previousPageUrl() }}"
           class="paginacao__seta {{ $pagina->onFirstPage() ? 'paginacao__seta--desabilitado' : '' }}"
           aria-label="Página anterior" @if ($pagina->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
            <i class="bi bi-chevron-left"></i>
        </a>

        <div class="paginacao__numeros">
            @foreach ($numeros as $indice => $numero)
                @if ($indice > 0 && $numero - $numeros[$indice - 1] > 1)
                    <span class="paginacao__reticencias" aria-hidden="true">…</span>
                @endif
                <a href="{{ $pagina->url($numero) }}"
                   class="paginacao__numero {{ $numero === $atual ? 'paginacao__numero--atual' : '' }}"
                   @if ($numero === $atual) aria-current="page" @endif>{{ $numero }}</a>
            @endforeach
        </div>

        <a href="{{ $pagina->hasMorePages() ? $pagina->nextPageUrl() : '#' }}"
           class="paginacao__seta {{ $pagina->hasMorePages() ? '' : 'paginacao__seta--desabilitado' }}"
           aria-label="Próxima página" @unless ($pagina->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
