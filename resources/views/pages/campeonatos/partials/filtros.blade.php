{{-- Barra de filtros da lista de campeonatos, em duas faixas:
       1) busca por nome + ordenação;
       2) jogo e status (chips), com o total de resultados e "limpar".
     Escolher uma opção já aplica o filtro (data-auto-submit no app.js); a
     busca aplica com Enter ou no botão. --}}
@php
    $totalResultados = count($ativos) + count($outras);
@endphp

<form method="GET" action="{{ route('campeonatos.index') }}" class="champ-filtros" data-auto-submit>
    <div class="champ-filtros__topo">
        <div class="champ-filtros__busca">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="busca" value="{{ $filtros['busca'] }}" placeholder="Buscar campeonato"
                   aria-label="Buscar campeonato pelo nome">
            <button type="submit">Buscar</button>
        </div>

        <label class="champ-filtros__ordenar">
            <span>Ordenar por</span>
            <select name="ordenar">
                <option value="recentes" {{ $filtros['ordenar'] === 'recentes' ? 'selected' : '' }}>Mais recentes</option>
                <option value="antigos" {{ $filtros['ordenar'] === 'antigos' ? 'selected' : '' }}>Mais antigos</option>
            </select>
        </label>
    </div>

    <div class="champ-filtros__base">
        <div class="champ-filtros__grupo" role="group" aria-labelledby="filtro-jogo">
            <span class="champ-filtros__rotulo" id="filtro-jogo">Jogo</span>
            <div class="chip-row">
                <label class="chip chip--check">
                    <input type="radio" name="jogo" value="" {{ $filtros['jogo'] === '' ? 'checked' : '' }}>
                    Todos
                </label>
                @foreach ($jogosDisponiveis as $jogo)
                    <label class="chip chip--check">
                        <input type="radio" name="jogo" value="{{ $jogo }}" {{ $filtros['jogo'] === $jogo ? 'checked' : '' }}>
                        {{ nomeDoJogo($jogo) }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="champ-filtros__grupo" role="group" aria-labelledby="filtro-status">
            <span class="champ-filtros__rotulo" id="filtro-status">Status</span>
            <div class="chip-row">
                @foreach (['' => 'Todos', 'ativo' => 'Em andamento', 'finalizado' => 'Finalizados'] as $valor => $rotulo)
                    <label class="chip chip--check">
                        <input type="radio" name="status" value="{{ $valor }}" {{ $filtros['status'] === $valor ? 'checked' : '' }}>
                        @if ($valor)
                            <span class="champ-filtros__dot champ-filtros__dot--{{ $valor }}" aria-hidden="true"></span>
                        @endif
                        {{ $rotulo }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="champ-filtros__resumo">
            <span>{{ $totalResultados }} {{ $totalResultados === 1 ? 'campeonato' : 'campeonatos' }}</span>
            @if ($filtrosAtivos)
                <a href="{{ route('campeonatos.index') }}" class="champ-filtros__limpar">
                    <i class="bi bi-x-lg"></i> Limpar filtros
                </a>
            @endif
        </div>
    </div>
</form>
