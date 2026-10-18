{{-- Um campo do formulário no padrão .campo (rótulo visível + erro embaixo),
     o mesmo dos modais de Clientes e Vendas.
     Parâmetros: $nome, $rotulo, $valor, $tipo (text, date, time, url,
     textarea), $opcional, $dica (texto embaixo), $atributos (string com
     atributos extras). Nos pop-ups, os erros do envio por fetch são
     colocados aqui pelo campeonatos.js. --}}
@php
    $tipo = $tipo ?? 'text';
    $id = 'campeonato-' . $nome;
@endphp

<div class="campo {{ $tipo === 'textarea' ? 'campo--textarea' : '' }} @error($nome) campo--invalido @enderror">
    <label for="{{ $id }}">
        {{ $rotulo }}
        @if ($opcional ?? false)
            <span class="opcional">(opcional)</span>
        @endif
    </label>

    @if ($tipo === 'textarea')
        <textarea id="{{ $id }}" name="{{ $nome }}" @error($nome) aria-invalid="true" aria-describedby="{{ $id }}-erro" @enderror {!! $atributos ?? '' !!}>{{ $valor }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $tipo }}" name="{{ $nome }}" value="{{ $valor }}"
               @error($nome) aria-invalid="true" aria-describedby="{{ $id }}-erro" @enderror {!! $atributos ?? '' !!}>
    @endif

    @isset($dica)
        <p class="campo__dica">{{ $dica }}</p>
    @endisset

    @error($nome)
        <span class="campo__erro" id="{{ $id }}-erro">{{ $message }}</span>
    @enderror
</div>
