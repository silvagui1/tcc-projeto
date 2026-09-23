{{-- Cabeçalho de colunas: só aparece no desktop (ver .clientes-lista-cabecalho
     no CSS), colunas alinhadas com .cliente-linha__botao. --}}
<div class="clientes-lista-cabecalho" aria-hidden="true">
    <span></span>
    <span>Nome</span>
    <span>Nascimento</span>
    <span>Créditos</span>
    <span></span>
</div>

<ul class="clientes-lista" data-lista data-total="{{ $clientes->total() }}">
    @forelse ($clientes as $cliente)
        @include('clientes.partials._linha', ['cliente' => $cliente])
    @empty
        <li class="clientes-vazio">Nenhum cliente encontrado.</li>
    @endforelse
</ul>

{{-- Paginação (20 clientes por página — ver ClienteController::CLIENTES_POR_PAGINA).
     Os links funcionam sem JS (apontam para a URL real da página), mas o
     clientes.js intercepta o clique para trocar de página sem recarregar,
     do mesmo jeito que a busca já funciona. --}}
@if ($clientes->hasPages())
    <nav class="paginacao" data-paginacao aria-label="Paginação de clientes">
        <a
            href="{{ $clientes->onFirstPage() ? '#' : $clientes->previousPageUrl() }}"
            class="paginacao__link {{ $clientes->onFirstPage() ? 'paginacao__link--desabilitado' : '' }}"
            data-pagina-link
            data-pagina="{{ $clientes->currentPage() - 1 }}"
            @if ($clientes->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
        >
            <i class="bi bi-chevron-left"></i>
            Anterior
        </a>

        <span class="paginacao__info">Página {{ $clientes->currentPage() }} de {{ $clientes->lastPage() }}</span>

        <a
            href="{{ $clientes->hasMorePages() ? $clientes->nextPageUrl() : '#' }}"
            class="paginacao__link {{ $clientes->hasMorePages() ? '' : 'paginacao__link--desabilitado' }}"
            data-pagina-link
            data-pagina="{{ $clientes->currentPage() + 1 }}"
            @unless ($clientes->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless
        >
            Próxima
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
