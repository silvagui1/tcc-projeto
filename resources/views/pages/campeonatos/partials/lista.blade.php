{{-- Lista: em andamento (o mais próximo primeiro; no celular vira um
     carrossel lateral) e finalizados (paginados). --}}
@if (! count($ativos) && ! count($outras))
    <p class="champ-vazio">
        <i class="bi bi-trophy" aria-hidden="true"></i>
        @if ($filtrosAtivos)
            Nenhum campeonato encontrado com esses filtros.
        @else
            Nenhum campeonato cadastrado ainda. Use “Criar campeonato” para começar.
        @endif
    </p>
@endif

@if (count($ativos))
    <h2 class="champ-secao-titulo">Em andamento <span>{{ count($ativos) }}</span></h2>

    <div class="champ-andamento {{ count($ativos) > 1 ? 'champ-andamento--varios' : '' }}">
        @foreach ($ativos as $ativo)
            @include('pages.campeonatos.partials.card', ['campeonato' => $ativo])
        @endforeach
    </div>
@endif

@if (count($outras))
    <h2 class="champ-secao-titulo">Finalizados <span>{{ $paginaFinalizados->total() }}</span></h2>

    {{-- no celular os cards ficam empilhados; 2 colunas no tablet e 3 no desktop --}}
    <div class="champ-finalizados">
        @foreach ($outras as $finalizado)
            @include('pages.campeonatos.partials.card', ['campeonato' => $finalizado])
        @endforeach
    </div>

    @include('pages.campeonatos.partials.paginacao', ['pagina' => $paginaFinalizados])
@endif
