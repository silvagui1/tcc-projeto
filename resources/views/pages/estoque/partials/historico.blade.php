{{-- Histórico de movimentações (detalhes do produto / carta): cadastro,
     edições, ajustes, vendas e cancelamentos. Movimentações de venda levam
     à venda (/vendas?venda=ID abre os detalhes dela). --}}
<section class="estoque-historico" aria-labelledby="historico-titulo">
    <div class="estoque-historico__cabecalho">
        <h2 id="historico-titulo">Histórico de movimentações</h2>
        @if ($totalMovimentacoes > $movimentacoes->count())
            <span>últimas {{ $movimentacoes->count() }} de {{ $totalMovimentacoes }}</span>
        @endif
    </div>

    @if ($movimentacoes->isEmpty())
        <p class="estoque-historico__vazio">Nenhuma movimentação registrada ainda.</p>
    @else
        <ul class="estoque-historico__lista">
            @foreach ($movimentacoes as $mov)
                <li class="estoque-historico__item estoque-historico__item--{{ $mov->tipo }}">
                    <span class="estoque-historico__icone" aria-hidden="true">
                        <i class="bi {{ $mov->tipo === 'entrada' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                    </span>
                    <span class="estoque-historico__texto">
                        @if ($mov->venda_id)
                            <a href="{{ route('vendas.index', ['venda' => $mov->venda_id]) }}" class="estoque-historico__venda">
                                {{ $mov->descricao }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        @else
                            <strong>{{ $mov->descricao }}</strong>
                        @endif
                        <small>{{ $mov->created_at->format('d/m/Y \à\s H:i') }}</small>
                    </span>
                    <span class="estoque-historico__qtd">
                        <strong>{{ $mov->tipo === 'entrada' ? '+' : '−' }}{{ $mov->quantidade }}</strong>
                        <small>{{ $mov->quantidade_anterior }} → {{ $mov->quantidade_nova }}</small>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
