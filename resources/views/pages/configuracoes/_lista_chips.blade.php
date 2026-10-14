{{-- Editor de lista curta (estados de carta, idiomas, valores rápidos,
     motivos): cada item é um chip com "×"; digitar e Enter adiciona.
     Parâmetros: $nome (campo, sem []), $itens, $placeholder, $rotulo,
     $numerico (bool) e $prefixo (ex.: "R$ "). Comportamento em configuracoes.js. --}}
@php
    $numerico ??= false;
    $prefixo ??= '';
@endphp
<div class="lista-chips" data-lista-chips data-nome="{{ $nome }}[]" data-prefixo="{{ $prefixo }}" @if ($numerico) data-numerico @endif>
    <ul class="lista-chips__itens" data-lista-chips-itens aria-label="{{ $rotulo }}">
        @foreach ($itens as $item)
            <li class="lista-chips__item">
                <span>{{ $prefixo }}{{ $item }}</span>
                <input type="hidden" name="{{ $nome }}[]" value="{{ $item }}">
                <button type="button" class="lista-chips__remover" data-remover-chip aria-label="Remover {{ $prefixo }}{{ $item }}">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </li>
        @endforeach
    </ul>
    <div class="lista-chips__nova">
        <input type="{{ $numerico ? 'number' : 'text' }}" class="lista-chips__entrada" placeholder="{{ $placeholder }}"
               @if ($numerico) min="1" step="1" inputmode="numeric" @else maxlength="60" @endif
               aria-label="{{ $placeholder }}" data-lista-chips-entrada>
        <button type="button" class="botao botao--neutro lista-chips__adicionar" data-lista-chips-adicionar>
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span class="sr-only">Adicionar</span>
        </button>
    </div>
    <span class="campo__erro" data-erro="{{ $nome }}" hidden></span>
</div>
