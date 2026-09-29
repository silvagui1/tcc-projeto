<li class="cliente-linha" data-cliente-linha data-id="{{ $cliente->id }}">
    <span class="cliente-linha__checkbox" data-linha-checkbox>
        <input
            type="checkbox"
            value="{{ $cliente->id }}"
            aria-label="Selecionar {{ $cliente->nome }}"
            data-linha-checkbox-input
        >
    </span>

    {{-- Clique na linha abre os DETALHES (somente leitura), não mais direto
         pra edição — ver _modal_detalhes.blade.php. --}}
    <button type="button" class="cliente-linha__botao" data-abrir-detalhes data-id="{{ $cliente->id }}">
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
            <span class="cliente-linha__nome">
                {{ $cliente->nome }}
                @if ($cliente->status === 'inativo')
                    <span class="status-badge status-badge--inativo">Inativo</span>
                @endif
            </span>
            <span class="cliente-linha__data">{{ $cliente->data_nascimento->format('d/m/Y') }}</span>
        </span>

        {{-- Texto colorido, não badge/pill: a cor já indica saldo
             positivo/zerado, uma cápsula verde chamativa aqui só reforçava
             aparência de "chip" genérico. O ícone (carteira vs. traço)
             mantém a diferença perceptível sem depender só da cor. --}}
        <span class="credito-valor {{ (float) $cliente->creditos > 0 ? 'credito-valor--positivo' : 'credito-valor--zero' }}">
            <i class="bi {{ (float) $cliente->creditos > 0 ? 'bi-wallet2' : 'bi-dash-circle' }}" aria-hidden="true"></i>
            R$ {{ number_format($cliente->creditos, 2, ',', '.') }}
        </span>

        <span class="cliente-linha__seta" aria-hidden="true">
            <i class="bi bi-chevron-right"></i>
        </span>
    </button>

    {{-- Atalho de contato rápido: antes só dava pra chamar no WhatsApp
         depois de abrir o modal inteiro. Escondido no modo de seleção (ver
         CSS) pra não competir com o toque de marcar/desmarcar a linha.

         Sempre ocupa o mesmo espaço, tenha ou não WhatsApp cadastrado — um
         <span> vazio no lugar do link quando não há, pra linha não mudar de
         largura e a coluna de créditos não desalinhar de cliente pra
         cliente (ver comentário em .cliente-linha__whatsapp no CSS). --}}
    @if ($cliente->whatsapp_url)
        <a
            href="{{ $cliente->whatsapp_url }}"
            target="_blank"
            rel="noopener"
            class="cliente-linha__whatsapp"
            aria-label="Chamar {{ $cliente->nome }} no WhatsApp"
        >
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    @else
        <span class="cliente-linha__whatsapp" aria-hidden="true"></span>
    @endif
</li>
