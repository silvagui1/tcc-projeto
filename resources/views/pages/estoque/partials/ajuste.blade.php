{{-- Ajuste rápido de quantidade (detalhes do produto / carta): entrada de
     mercadoria ou saída (perda, uso, contagem), sem abrir o formulário
     inteiro. Vai para o histórico com o motivo. $tipo = 'produto' | 'carta'. --}}
@php
    $erros = $errors->ajuste;
    $rota = $tipo === 'produto' ? 'estoque.produtos.ajustar' : 'estoque.cartas.ajustar';
    $tipoAtual = old('tipo', 'entrada');
@endphp
<form method="POST" action="{{ route($rota, $item) }}" class="ajuste-estoque" aria-labelledby="ajuste-titulo">
    @csrf
    <h2 class="ajuste-estoque__titulo" id="ajuste-titulo">Ajustar quantidade</h2>

    <div class="ajuste-estoque__tipo" role="radiogroup" aria-label="Tipo de ajuste">
        <label>
            <input type="radio" name="tipo" value="entrada" @checked($tipoAtual === 'entrada')>
            <span><i class="bi bi-plus-lg" aria-hidden="true"></i> Entrada</span>
        </label>
        <label>
            <input type="radio" name="tipo" value="saida" @checked($tipoAtual === 'saida')>
            <span><i class="bi bi-dash-lg" aria-hidden="true"></i> Saída</span>
        </label>
    </div>

    <div class="ajuste-estoque__campos">
        <label class="ajuste-estoque__campo ajuste-estoque__campo--qtd">
            <span>Quantidade</span>
            <input type="number" name="quantidade" min="1" step="1" inputmode="numeric" required
                   value="{{ old('quantidade') }}" @if ($erros->has('quantidade')) aria-invalid="true" @endif>
        </label>
        <label class="ajuste-estoque__campo">
            <span>Motivo <small>(opcional)</small></span>
            <input type="text" name="motivo" maxlength="120" value="{{ old('motivo') }}"
                   placeholder="Ex.: chegada do fornecedor, avaria, contagem">
        </label>
        <button type="submit" class="botao botao--principal">Registrar</button>
    </div>

    @if ($erros->any())
        <p class="ajuste-estoque__erro" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> {{ $erros->first() }}
        </p>
    @endif
</form>
