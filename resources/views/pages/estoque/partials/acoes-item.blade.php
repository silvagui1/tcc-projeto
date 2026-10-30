{{-- Botões no canto do card de produto / carta, por cima do link que cobre
     o card. $tipo = 'produto' | 'carta', $item = o modelo.
     - Ativo: editar (pop-up do formulário) e arquivar (pop-up de confirmação).
     - Arquivado: restaurar e excluir de vez. --}}
@php
    $rota = $tipo === 'produto' ? 'estoque.produtos' : 'estoque.cartas';
    $o = $tipo === 'produto' ? 'o' : 'a';
@endphp
<span class="card-actions">
    @if ($item->trashed())
        <form method="POST" action="{{ route("{$rota}.restaurar", $item) }}">
            @csrf
            <button type="submit" class="card-actions__btn" aria-label="Restaurar {{ $item->nome }}" title="Restaurar">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </form>
        <button type="button" class="card-actions__btn card-actions__btn--danger"
                data-delete-open="{{ route("{$rota}.excluir", $item) }}"
                data-delete-title="Excluir &quot;{{ $item->nome }}&quot; definitivamente?"
                data-delete-texto="O histórico de movimentações e a imagem saem do sistema. As vendas antigas continuam com o nome e o preço. Não dá para desfazer."
                data-delete-botao="Excluir definitivamente"
                aria-label="Excluir {{ $item->nome }} definitivamente" title="Excluir definitivamente">
            <i class="bi bi-trash"></i>
        </button>
    @else
        <button type="button" class="card-actions__btn" data-form-open="{{ $tipo }}"
                data-form-dados='@json($item->dadosFormulario())'
                aria-label="Editar {{ $item->nome }}" title="Editar">
            <i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="card-actions__btn card-actions__btn--danger"
                data-delete-open="{{ route("{$rota}.destroy", $item) }}"
                data-delete-title="Arquivar &quot;{{ $item->nome }}&quot;?"
                data-delete-texto="{{ $tipo === 'produto' ? 'O produto' : 'A carta' }} sai das listas e das vendas, mas o histórico fica guardado. Dá para restaurar depois em Arquivad{{ $o }}s."
                data-delete-botao="Arquivar"
                aria-label="Arquivar {{ $item->nome }}" title="Arquivar">
            <i class="bi bi-archive"></i>
        </button>
    @endif
</span>
