{{-- Lixeira que abre o pop-up "Apagar campeonato" (apagar-popup). Semanal
     em andamento mostra a opção de parar de repetir. Com $rotulo, o texto
     some no celular e fica só o ícone (ver .champ-apagar-texto). --}}
<button type="button" class="{{ $classe ?? '' }}"
        data-champ-apagar="{{ route('campeonatos.apagar', $campeonato['id']) }}"
        data-champ-apagar-titulo="Apagar “{{ $campeonato['nome'] }}”?"
        data-champ-apagar-semanal="{{ $campeonato['semanal'] && $campeonato['status'] !== 'finalizado' ? '1' : '' }}"
        aria-label="Apagar {{ $campeonato['nome'] }}" title="Apagar">
    <i class="bi bi-trash" aria-hidden="true"></i>
    @isset($rotulo)
        <span class="champ-apagar-texto">{{ $rotulo }}</span>
    @endisset
</button>
