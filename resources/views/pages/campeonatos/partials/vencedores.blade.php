{{-- Vencedores (1°, 2° e 3° lugar) com o crédito que cada um recebeu. --}}
@forelse ($vencedores as $vencedor)
    <div class="winner-row">
        {{-- a colocação fica só no selo ("1°"), sem repetir embaixo do nome --}}
        <span class="winner-row__posicao" aria-label="{{ $vencedor['posicao'] }}">{{ (int) $vencedor['posicao'] }}°</span>
        <img class="winner-row__avatar" src="{{ $vencedor['avatar'] }}" alt="">
        <span class="winner-row__info">
            <strong>{{ $vencedor['nome'] }}</strong>
            @if ($vencedor['extra'] !== '')
                <span><i class="bi bi-gift" aria-hidden="true"></i> {{ $vencedor['extra'] }}</span>
            @endif
        </span>
        @if ($vencedor['credito'] > 0)
            <span class="winner-row__value" title="Em créditos">+R$ {{ number_format($vencedor['credito'], 2, ',', '.') }}</span>
        @endif
    </div>
@empty
    <p class="champ-vazio">Os vencedores aparecem aqui depois que o campeonato for finalizado.</p>
@endforelse
