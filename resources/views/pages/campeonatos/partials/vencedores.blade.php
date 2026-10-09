{{-- Lista de vencedores (Figma: editar_campeonato → Vencedores). A faixa rosa
     à direita só vira link para a tela de premiações quando $editavel é true
     e $campeonatoId foi passado. --}}
@forelse ($vencedores as $vencedor)
    <div class="winner-row">
        <img class="winner-row__avatar" src="{{ $vencedor['avatar'] }}" alt="">
        <span class="winner-row__info">
            <strong>{{ $vencedor['nome'] }}</strong>
            <span>{{ $vencedor['posicao'] }}</span>
        </span>
        <span class="winner-row__value">+R$ {{ number_format($vencedor['credito'], 2, ',', '.') }}</span>
        @if ($editavel && isset($campeonatoId))
            <a class="winner-row__action"
               href="{{ route('campeonatos.premiacoes', $campeonatoId) }}"
               aria-label="Editar premiação de {{ $vencedor['nome'] }}">
                <img src="{{ asset('images/campeonatos/chevron-right.svg') }}" alt="" width="10" height="15">
            </a>
        @else
            <span class="winner-row__action" aria-hidden="true">
                <img src="{{ asset('images/campeonatos/chevron-right.svg') }}" alt="" width="10" height="15">
            </span>
        @endif
    </div>
@empty
    <p class="champ-empty">Os vencedores aparecem aqui depois que o campeonato for finalizado.</p>
@endforelse
