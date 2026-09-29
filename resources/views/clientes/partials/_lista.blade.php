@php
    $filtros ??= ['sort' => 'nome', 'dir' => 'asc'];
@endphp

{{-- Cabeçalho de colunas: só aparece no desktop (ver .clientes-lista-cabecalho
     no CSS), colunas alinhadas com .cliente-linha__botao. A seta de "ver
     mais" não tem coluna própria aqui — no desktop ela some (a linha toda já
     é clicável, então era só ruído repetido em cada linha). Nome, Nascimento
     e Créditos são clicáveis pra ordenar (ver clientes.js, que refaz a busca
     com ?sort=...&dir=...) — antes eram só rótulos decorativos, sugeriam uma
     tabela ordenável sem entregar isso de verdade. --}}
<div class="clientes-lista-cabecalho">
    <span></span>
    <button type="button" class="cabecalho-ordenar" data-sort-campo="nome">
        Nome
        <i class="bi cabecalho-ordenar__icone" data-sort-icone aria-hidden="true"></i>
    </button>
    <button type="button" class="cabecalho-ordenar" data-sort-campo="data_nascimento">
        Nascimento
        <i class="bi cabecalho-ordenar__icone" data-sort-icone aria-hidden="true"></i>
    </button>
    <button type="button" class="cabecalho-ordenar cabecalho-ordenar--fim" data-sort-campo="creditos">
        Créditos
        <i class="bi cabecalho-ordenar__icone" data-sort-icone aria-hidden="true"></i>
    </button>
</div>

<ul
    class="clientes-lista"
    data-lista
    data-total="{{ $clientes->total() }}"
    data-primeiro="{{ $clientes->firstItem() ?? 0 }}"
    data-ultimo="{{ $clientes->lastItem() ?? 0 }}"
    data-sort="{{ $filtros['sort'] }}"
    data-dir="{{ $filtros['dir'] }}"
>
    @forelse ($clientes as $cliente)
        @include('clientes.partials._linha', ['cliente' => $cliente])
    @empty
        <li class="clientes-vazio">
            @if (trim((string) ($termo ?? '')) !== '')
                <i class="bi bi-search clientes-vazio__icone" aria-hidden="true"></i>
                <p class="clientes-vazio__titulo">Nenhum cliente encontrado</p>
                <p class="clientes-vazio__texto">Não encontramos ninguém para "{{ $termo }}". Tente buscar por outro nome.</p>
            @else
                <i class="bi bi-people clientes-vazio__icone" aria-hidden="true"></i>
                <p class="clientes-vazio__titulo">Nenhum cliente cadastrado</p>
                <p class="clientes-vazio__texto">Adicione o primeiro cliente para começar a controlar cadastros e créditos.</p>
                <button type="button" class="botao botao--principal botao--pill" data-abrir-criar-vazio>
                    <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                    Adicionar cliente
                </button>
            @endif
        </li>
    @endforelse
</ul>

{{-- Paginação (20 clientes por página — ver ClienteController::CLIENTES_POR_PAGINA).
     Os links funcionam sem JS (apontam para a URL real da página), mas o
     clientes.js intercepta o clique para trocar de página sem recarregar,
     do mesmo jeito que a busca já funciona. Mostra números de página (com
     reticências quando há muitas) em vez de só "Anterior/Próxima", para
     permitir pular direto para uma página distante. --}}
@if ($clientes->hasPages())
    @php
        $paginaAtual = $clientes->currentPage();
        $ultimaPagina = $clientes->lastPage();

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
    <nav class="paginacao" data-paginacao aria-label="Paginação de clientes">
        <a
            href="{{ $clientes->onFirstPage() ? '#' : $clientes->previousPageUrl() }}"
            class="paginacao__seta {{ $clientes->onFirstPage() ? 'paginacao__seta--desabilitado' : '' }}"
            data-pagina-link
            data-pagina="{{ $paginaAtual - 1 }}"
            aria-label="Página anterior"
            @if ($clientes->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
        >
            <i class="bi bi-chevron-left"></i>
        </a>

        <div class="paginacao__numeros">
            @foreach ($paginas as $indice => $numero)
                @if ($indice > 0 && $numero - $paginas[$indice - 1] > 1)
                    <span class="paginacao__reticencias" aria-hidden="true">…</span>
                @endif
                <a
                    href="{{ $clientes->url($numero) }}"
                    class="paginacao__numero {{ $numero === $paginaAtual ? 'paginacao__numero--atual' : '' }}"
                    data-pagina-link
                    data-pagina="{{ $numero }}"
                    @if ($numero === $paginaAtual) aria-current="page" @endif
                >{{ $numero }}</a>
            @endforeach
        </div>

        <a
            href="{{ $clientes->hasMorePages() ? $clientes->nextPageUrl() : '#' }}"
            class="paginacao__seta {{ $clientes->hasMorePages() ? '' : 'paginacao__seta--desabilitado' }}"
            data-pagina-link
            data-pagina="{{ $paginaAtual + 1 }}"
            aria-label="Próxima página"
            @unless ($clientes->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless
        >
            <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
@endif
