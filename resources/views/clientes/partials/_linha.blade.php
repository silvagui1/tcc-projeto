<li class="cliente-linha" data-cliente-linha data-id="{{ $cliente->id }}">
    <span class="cliente-linha__checkbox" data-linha-checkbox>
        <input
            type="checkbox"
            value="{{ $cliente->id }}"
            aria-label="Selecionar {{ $cliente->nome }}"
            data-linha-checkbox-input
        >
    </span>

    <button type="button" class="cliente-linha__botao" data-abrir-editar data-id="{{ $cliente->id }}">
        <span
            class="avatar"
            @unless ($cliente->foto_url) style="background-color: {{ $cliente->cor_avatar }}" @endunless
        >
            @if ($cliente->foto_url)
                <img src="{{ $cliente->foto_url }}" alt="Foto de {{ $cliente->nome }}">
            @else
                {{ $cliente->iniciais }}
            @endif
        </span>

        <span class="cliente-linha__info">
            <span class="cliente-linha__nome">{{ $cliente->nome }}</span>
            <span class="cliente-linha__data">{{ $cliente->data_nascimento->format('d/m/Y') }}</span>
        </span>

        <span class="credito-badge {{ (float) $cliente->creditos > 0 ? 'credito-badge--positivo' : 'credito-badge--zero' }}">
            +R$ {{ number_format($cliente->creditos, 2, ',', '.') }}
        </span>

        <span class="cliente-linha__seta" aria-hidden="true">
            <i class="bi bi-chevron-right"></i>
        </span>
    </button>
</li>
