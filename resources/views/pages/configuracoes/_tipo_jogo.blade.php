{{-- Uma linha do editor de tipos de jogo (também usada como modelo, com
     $i = "__I__"). O configuracoes.js renumera os índices antes de salvar.
     A chave fica escondida: renomear mantém as reservas ligadas ao tipo. --}}
<div class="tipo-jogo" data-tipo-jogo>
    <span class="jogo-chip jogo-chip--{{ $tipo['cor'] }} tipo-jogo__previa" data-tipo-previa aria-hidden="true">
        <i class="bi {{ $tipo['icone'] }}" data-tipo-previa-icone></i>
        <span data-tipo-previa-nome>{{ $tipo['nome'] ?: 'Novo tipo' }}</span>
    </span>

    <input type="hidden" name="tipos[{{ $i }}][chave]" value="{{ $tipo['chave'] }}" data-tipo-campo="chave">
    <input type="text" name="tipos[{{ $i }}][nome]" value="{{ $tipo['nome'] }}" maxlength="30" placeholder="Nome (ex.: Wargame)"
           class="tipo-jogo__nome" aria-label="Nome do tipo de jogo" data-tipo-campo="nome">

    <select name="tipos[{{ $i }}][cor]" class="config-select" aria-label="Cor" data-tipo-campo="cor">
        @foreach ($cores as $valor => $rotulo)
            <option value="{{ $valor }}" @selected($tipo['cor'] === $valor)>{{ $rotulo }}</option>
        @endforeach
    </select>

    <select name="tipos[{{ $i }}][icone]" class="config-select" aria-label="Ícone" data-tipo-campo="icone">
        @foreach ($icones as $valor => $rotulo)
            <option value="{{ $valor }}" @selected($tipo['icone'] === $valor)>{{ $rotulo }}</option>
        @endforeach
    </select>

    <button type="button" class="config-icone-botao config-icone-botao--perigo" data-remover-tipo
            @if ($emUso) disabled title="Já usado em reservas: pode renomear, mas não remover" @else title="Remover tipo" @endif
            aria-label="Remover tipo de jogo">
        <i class="bi bi-trash3" aria-hidden="true"></i>
    </button>

    <span class="campo__erro tipo-jogo__erro" data-erro="tipos.{{ $i }}.nome" hidden></span>
</div>
