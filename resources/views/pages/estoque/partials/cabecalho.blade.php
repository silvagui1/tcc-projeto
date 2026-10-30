{{-- Cabeçalho das duas abas do estoque (Produtos e Cartas): título, números
     do estoque inteiro e o alerta de estoque baixo. Os dados vêm do view
     composer App\View\Composers\CabecalhoDoEstoque. --}}
<div class="page-topbar">
    <h1 class="page-topbar__title">Estoque</h1>
    <span class="page-topbar__contador">
        {{ $resumo['quantidadeProdutos'] }} {{ $resumo['quantidadeProdutos'] === 1 ? 'produto' : 'produtos' }}
        · {{ $resumo['cartasAvulsas'] }} {{ $resumo['cartasAvulsas'] === 1 ? 'carta' : 'cartas' }}
    </span>
</div>

{{-- no mobile os cartões ficam empilhados; no desktop viram uma fileira só --}}
<div class="estoque-resumo">
    <div class="stat-card stat-card--split">
        <div>
            <span class="stat-card__label">Valor em estoque</span>
            <p class="stat-card__value">R$ {{ number_format($resumo['valorEstoque'], 2, ',', '.') }}</p>
        </div>
        <div>
            <span class="stat-card__label">Quantidade de produtos</span>
            <p class="stat-card__value">{{ $resumo['quantidadeProdutos'] }}</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-card__label">Cartas avulsas no estoque</span>
            <p class="stat-card__value stat-card__value--lg">{{ $resumo['cartasAvulsas'] }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-card__label">Valor total em cartas avulsas</span>
            <p class="stat-card__value stat-card__value--lg">R$ {{ number_format($resumo['valorCartasAvulsas'], 2, ',', '.') }}</p>
        </div>
    </div>
</div>

{{-- Alerta de estoque baixo (Configurações > Estoque): cada nome abre o
     produto; "ver todos" filtra a grade --}}
@if ($estoqueBaixo->isNotEmpty())
    <div class="alerta-estoque" role="status">
        <i class="bi bi-exclamation-triangle-fill alerta-estoque__icone" aria-hidden="true"></i>
        <div class="alerta-estoque__texto">
            <strong>
                {{ $estoqueBaixo->count() }} {{ $estoqueBaixo->count() === 1 ? 'produto está' : 'produtos estão' }}
                com estoque baixo
            </strong>
            <span>
                @foreach ($estoqueBaixo->take(4) as $produtoBaixo)
                    <a href="{{ route('estoque.produtos.show', $produtoBaixo) }}">{{ $produtoBaixo->nome }} ({{ $produtoBaixo->quantidade }})</a>@if (! $loop->last) · @endif
                @endforeach
                @if ($estoqueBaixo->count() > 4)
                    · e mais {{ $estoqueBaixo->count() - 4 }}
                @endif
            </span>
        </div>
        <a href="{{ route('estoque.index', ['situacao' => 'baixo']) }}" class="alerta-estoque__link"
           title="Padrão: alerta a partir de {{ $alertaMinimo }} unidades (Configurações > Estoque). Produtos com limite próprio usam o deles.">
            Ver todos
        </a>
    </div>
@endif
