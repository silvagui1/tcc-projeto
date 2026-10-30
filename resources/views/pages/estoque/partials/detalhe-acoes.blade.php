{{-- Botões da página de detalhes do produto / carta. Ativo: editar e
     arquivar. Arquivado: restaurar e excluir de vez. $tipo = 'produto' | 'carta'. --}}
@php
    $rota = $tipo === 'produto' ? 'estoque.produtos' : 'estoque.cartas';
    $o = $tipo === 'produto' ? 'o' : 'a';
@endphp
<div class="card-detail__actions">
    @if ($item->trashed())
        <form method="POST" action="{{ route("{$rota}.restaurar", $item) }}">
            @csrf
            <button type="submit" class="botao botao--principal">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restaurar
            </button>
        </form>
        <button type="button" class="botao botao--neutro botao--texto-perigo"
                data-delete-open="{{ route("{$rota}.excluir", $item) }}"
                data-delete-title="Excluir &quot;{{ $item->nome }}&quot; definitivamente?"
                data-delete-texto="O histórico de movimentações e a imagem saem do sistema. As vendas antigas continuam com o nome e o preço. Não dá para desfazer."
                data-delete-botao="Excluir definitivamente">
            <i class="bi bi-trash" aria-hidden="true"></i> Excluir definitivamente
        </button>
    @else
        <button type="button" class="botao botao--principal" data-form-open="{{ $tipo }}"
                data-form-dados='@json($item->dadosFormulario())'>
            <i class="bi bi-pencil" aria-hidden="true"></i> Editar {{ $tipo }}
        </button>
        <button type="button" class="botao botao--neutro"
                data-delete-open="{{ route("{$rota}.destroy", $item) }}"
                data-delete-title="Arquivar &quot;{{ $item->nome }}&quot;?"
                data-delete-texto="{{ $tipo === 'produto' ? 'O produto' : 'A carta' }} sai das listas e das vendas, mas o histórico fica guardado. Dá para restaurar depois."
                data-delete-botao="Arquivar">
            <i class="bi bi-archive" aria-hidden="true"></i> Arquivar
        </button>
    @endif
</div>
