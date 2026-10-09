{{-- Lista de vencedores (Figma: editar_campeonato → Vencedores), a partir
     dos prêmios salvos ao finalizar. A faixa rosa à direita só vira link para
     a tela de premiações quando $editavel é true. --}}
@forelse ($campeonato->premios->sortBy('colocacao') as $premio)
    <div class="winner-row">
        <img class="winner-row__avatar" src="{{ $premio->usuario->foto }}" alt="">
        <span class="winner-row__info">
            <strong>{{ $premio->usuario->name }}</strong>
            <span>{{ $premio->colocacao }}° Lugar</span>
        </span>
        <span class="winner-row__value">+R$ {{ number_format($premio->valor, 2, ',', '.') }}</span>
        @if ($editavel)
            <a class="winner-row__action"
               href="{{ route('campeonatos.premios', $campeonato) }}"
               aria-label="Editar premiação de {{ $premio->usuario->name }}">
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
