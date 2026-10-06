{{-- Filtros laterais do catálogo de cartas. No desktop ocupam a primeira
     coluna da grade (no lugar de uma carta) e, quando acabam, as cartas
     voltam a usar a coluna toda (ver app.js). No mobile viram um bloco
     recolhível acima da lista. --}}
<details class="cartas-filtros" data-cartas-filtros {{ $filtrosAtivos ? 'open' : '' }}>
    <summary class="cartas-filtros__summary">
        <span><i class="bi bi-funnel"></i> Filtros</span>
        @if ($filtrosAtivos)
            <span class="cartas-filtros__badge">{{ $filtrosAtivos }}</span>
        @endif
    </summary>

    <form method="GET" action="{{ route('estoque.cartas') }}" class="cartas-filtros__form">
        <input type="hidden" name="jogo" value="{{ $jogoAtual }}">
        @if ($filtros['busca'] !== '')
            <input type="hidden" name="busca" value="{{ $filtros['busca'] }}">
        @endif

        @foreach (['estado' => 'estado', 'raridade' => 'raridade', 'idioma' => 'idioma'] as $campo => $titulo)
            @if ($opcoes[$campo])
                <fieldset class="cartas-filtros__grupo">
                    <legend>{{ $titulo }}</legend>
                    <div class="chip-row">
                        @foreach ($opcoes[$campo] as $valor)
                            <label class="chip chip--check">
                                <input type="checkbox" name="{{ $campo }}[]" value="{{ $valor }}"
                                       {{ in_array($valor, $filtros[$campo]) ? 'checked' : '' }}>
                                {{ mb_convert_case($valor, MB_CASE_TITLE) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
        @endforeach

        <fieldset class="cartas-filtros__grupo">
            <legend>acabamento</legend>
            <div class="chip-row">
                <label class="chip chip--check">
                    <input type="checkbox" name="foil" value="1" {{ $filtros['foil'] ? 'checked' : '' }}>
                    Foil
                </label>
            </div>
        </fieldset>

        <fieldset class="cartas-filtros__grupo">
            <legend>preço (R$)</legend>
            <div class="cartas-filtros__preco">
                <input type="number" name="preco_min" min="0" step="0.01" placeholder="mín."
                       value="{{ $filtros['preco_min'] }}" aria-label="Preço mínimo">
                <span>–</span>
                <input type="number" name="preco_max" min="0" step="0.01" placeholder="máx."
                       value="{{ $filtros['preco_max'] }}" aria-label="Preço máximo">
            </div>
        </fieldset>

        <fieldset class="cartas-filtros__grupo">
            <legend>ordenar</legend>
            <select name="ordenar" class="cartas-filtros__select">
                @foreach (['recentes' => 'mais recentes', 'menor_preco' => 'menor preço', 'maior_preco' => 'maior preço', 'nome' => 'nome (A–Z)'] as $valor => $rotulo)
                    <option value="{{ $valor }}" {{ $filtros['ordenar'] === $valor ? 'selected' : '' }}>{{ $rotulo }}</option>
                @endforeach
            </select>
        </fieldset>

        <div class="cartas-filtros__acoes">
            <a href="{{ route('estoque.cartas', ['jogo' => $jogoAtual]) }}" class="cartas-filtros__limpar">Limpar</a>
            <button type="submit" class="cartas-filtros__aplicar">Aplicar</button>
        </div>
    </form>
</details>
