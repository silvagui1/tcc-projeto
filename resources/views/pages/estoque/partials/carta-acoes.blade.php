{{-- Botões pequenos de editar / apagar no canto da carta. Editar abre o
     pop-up de carta preenchido (partials/carta-form); apagar abre o pop-up de
     confirmação (partials/apagar-popup). --}}
<span class="card-actions">
    <button type="button" class="card-actions__btn" data-form-open="carta"
            data-form-dados='@json($carta->dadosFormulario())'
            aria-label="Editar {{ $carta->nome }}" title="Editar">
        <i class="bi bi-pencil"></i>
    </button>
    <button type="button" class="card-actions__btn card-actions__btn--danger"
            data-delete-open="{{ route('estoque.cartas.destroy', $carta) }}"
            data-delete-title="Deseja apagar a carta &quot;{{ $carta->nome }}&quot;?"
            aria-label="Apagar {{ $carta->nome }}" title="Apagar">
        <i class="bi bi-trash"></i>
    </button>
</span>
