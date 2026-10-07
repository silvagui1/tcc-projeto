{{-- Aba "Vendas": busca + filtros (somam-se entre si) e a lista agrupada por
     dia. Busca, filtros e paginação recarregam só a lista, via AJAX (ver
     vendas.js, carregarLista). --}}
<div class="vendas-filtros-barra">
    <form class="busca" data-busca-form role="search">
        <i class="bi bi-search busca__icone" aria-hidden="true"></i>
        <input
            type="search"
            name="busca"
            class="busca__campo"
            placeholder="Buscar por cliente, item ou nº da venda"
            autocomplete="off"
            value="{{ $filtros['busca'] }}"
            data-busca-campo
        >
        <button type="button" class="busca__limpar" data-busca-limpar @if ($filtros['busca'] === '') hidden @endif aria-label="Limpar busca">
            <i class="bi bi-x-circle-fill"></i>
        </button>
        <span class="busca__spinner" data-busca-spinner hidden aria-hidden="true"></span>
    </form>

    <div class="clientes-filtros vendas-filtros" data-filtros>
        <label class="filtro">
            <span class="filtro__rotulo">Período</span>
            <select data-filtro="periodo">
                @foreach (['tudo' => 'Tudo', 'hoje' => 'Hoje', '7dias' => 'Últimos 7 dias', 'mes' => 'Este mês'] as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros['periodo'] === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>
        <label class="filtro">
            <span class="filtro__rotulo">Pagamento</span>
            <select data-filtro="pagamento">
                <option value="todos">Todos</option>
                @foreach ($formasPagamento as $valor => $forma)
                    <option value="{{ $valor }}" @selected($filtros['pagamento'] === $valor)>{{ $forma['rotulo'] }}</option>
                @endforeach
                <option value="creditos" @selected($filtros['pagamento'] === 'creditos')>Créditos do cliente</option>
            </select>
        </label>
        <label class="filtro">
            <span class="filtro__rotulo">Itens</span>
            <select data-filtro="tipo">
                @foreach (['todos' => 'Todos', 'produto' => 'Produtos', 'carta' => 'Cartas', 'aluguel' => 'Aluguéis de mesa'] as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros['tipo'] === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>

        <button type="button" class="botao-texto" data-limpar-filtros hidden>Limpar filtros</button>
    </div>
</div>

<div class="vendas-lista-wrapper" data-lista-wrapper>
    @include('vendas.partials._lista')
</div>
