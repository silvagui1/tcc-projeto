{{-- Barra de filtros da lista, em duas faixas:
       1) busca por nome + ordenação + "Criar campeonato";
       2) jogo, tipo e status (selects, como os filtros de Clientes), com o
          total de resultados e "limpar".
     Escolher uma opção já aplica o filtro (data-auto-submit no app.js); a
     busca aplica com Enter. Selects em vez de chips: cabem numa faixa no
     desktop e numa grade 2x2 no celular, sem rolagem lateral (os chips
     antigos alargavam a página inteira no celular). --}}
<form method="GET" action="{{ route('campeonatos.index') }}" class="champ-filtros" data-auto-submit role="search">
    <div class="champ-filtros__topo">
        <div class="busca">
            <i class="bi bi-search busca__icone" aria-hidden="true"></i>
            <input type="search" name="busca" class="busca__campo" value="{{ $filtros['busca'] }}"
                   placeholder="Buscar campeonato" aria-label="Buscar campeonato pelo nome">
        </div>

        <a href="{{ route('campeonatos.criar') }}" class="botao botao--principal champ-filtros__criar"
           data-champ-ir="{{ route('campeonatos.criar') }}" data-champ-estado="criar=1">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Criar <span class="champ-filtros__criar-extra">campeonato</span>
        </a>
    </div>

    <div class="champ-filtros__base">
        <label class="champ-filtro {{ $filtros['jogo'] !== '' ? 'champ-filtro--ativo' : '' }}">
            <span>Jogo</span>
            <select name="jogo">
                <option value="">Todos</option>
                @foreach ($jogosDisponiveis as $jogo => $nomeJogo)
                    <option value="{{ $jogo }}" @selected($filtros['jogo'] === $jogo)>{{ $nomeJogo }}</option>
                @endforeach
            </select>
        </label>

        <label class="champ-filtro {{ $filtros['tipo'] !== '' ? 'champ-filtro--ativo' : '' }}">
            <span>Tipo</span>
            <select name="tipo">
                <option value="">Todos</option>
                @foreach (\App\Models\Campeonato::opcoesTipo() as $tipo => $nomeTipo)
                    <option value="{{ $tipo }}" @selected($filtros['tipo'] === $tipo)>{{ $nomeTipo }}</option>
                @endforeach
            </select>
        </label>

        <label class="champ-filtro {{ $filtros['status'] !== '' ? 'champ-filtro--ativo' : '' }}">
            <span>Status</span>
            <select name="status">
                @foreach (['' => 'Todos', 'ativo' => 'Em andamento', 'finalizado' => 'Finalizados'] as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($filtros['status'] === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>

        {{-- vale para a lista de finalizados (em andamento vem sempre do mais próximo) --}}
        <label class="champ-filtro champ-filtro--ordenar">
            <span>Finalizados</span>
            <select name="ordenar">
                <option value="recentes" @selected($filtros['ordenar'] === 'recentes')>Mais recentes</option>
                <option value="antigos" @selected($filtros['ordenar'] === 'antigos')>Mais antigos</option>
            </select>
        </label>

        <div class="champ-filtros__resumo">
            <span>{{ $totalResultados }} {{ $totalResultados === 1 ? 'campeonato' : 'campeonatos' }}</span>
            @if ($filtrosAtivos)
                <a href="{{ route('campeonatos.index') }}" class="champ-filtros__limpar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Limpar
                </a>
            @endif
        </div>
    </div>
</form>
