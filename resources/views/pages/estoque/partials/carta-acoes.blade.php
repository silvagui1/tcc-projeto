{{-- Botões pequenos de editar / apagar no canto da carta.
     Somente frontend por enquanto: ainda não fazem nada — os atributos
     data-card-edit / data-card-delete ficam prontos para ligar a lógica. --}}
<span class="card-actions">
    <button type="button" class="card-actions__btn" data-card-edit
            aria-label="Editar {{ $carta['nome'] }}" title="Editar">
        <i class="bi bi-pencil"></i>
    </button>
    <button type="button" class="card-actions__btn card-actions__btn--danger" data-card-delete
            aria-label="Apagar {{ $carta['nome'] }}" title="Apagar">
        <i class="bi bi-trash"></i>
    </button>
</span>
