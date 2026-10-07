@php
    $iconesTipo = ['produto' => 'bi-box-seam', 'carta' => 'bi-stack', 'aluguel' => 'bi-dice-5'];
@endphp
{{-- Uma venda. No telefone: hora | itens + (cliente · pagamento) | total.
     A partir do tablet os blocos viram colunas alinhadas com o cabeçalho
     (display: contents — ver .venda-linha no CSS). Clique abre os detalhes. --}}
<li class="venda-linha {{ $venda->cancelada ? 'venda-linha--cancelada' : '' }}">
    <button type="button" class="venda-linha__botao" data-abrir-venda="{{ $venda->id }}">
        <span class="venda-linha__hora">
            {{ $venda->created_at->format('H:i') }}
            <small>#{{ $venda->id }}</small>
        </span>

        <span class="venda-linha__principal">
            <span class="venda-linha__itens">
                <span class="venda-linha__icones" aria-hidden="true">
                    @foreach ($venda->tipos_itens as $tipo)
                        <i class="bi {{ $iconesTipo[$tipo] ?? 'bi-bag' }} venda-linha__icone--{{ $tipo }}"></i>
                    @endforeach
                </span>
                <span class="venda-linha__resumo">{{ $venda->resumo_itens }}</span>
                @if ($venda->cancelada)
                    <span class="status-badge status-badge--cancelada">Cancelada</span>
                @endif
            </span>

            <span class="venda-linha__meta">
                <span class="venda-linha__cliente">
                    @if ($venda->cliente)
                        <span
                            class="avatar avatar--mini"
                            @unless ($venda->cliente->foto_url) style="background-color: {{ $venda->cliente->cor_avatar }}" @endunless
                            aria-hidden="true"
                        >
                            @if ($venda->cliente->foto_url)
                                <img src="{{ $venda->cliente->foto_url }}" alt="">
                            @else
                                {{ $venda->cliente->iniciais }}
                            @endif
                        </span>
                        <span class="venda-linha__cliente-nome">{{ $venda->cliente->nome }}</span>
                    @else
                        <span class="venda-linha__sem-cliente">Sem cliente</span>
                    @endif
                </span>
                <span class="venda-linha__pagamento">
                    <i class="bi {{ $venda->pagamento_icone }}" aria-hidden="true"></i>
                    {{ $venda->pagamento_rotulo }}
                </span>
            </span>
        </span>

        <span class="venda-linha__total">R$ {{ number_format($venda->total, 2, ',', '.') }}</span>
    </button>
</li>
