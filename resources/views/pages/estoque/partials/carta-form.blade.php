{{-- Pop-up de carta — o mesmo formulário serve para adicionar e para editar.
     Usado na aba "estoque cartas", na página de cartas e na de detalhes.

     Abre ao clicar em qualquer botão com data-form-open="carta" (ver app.js):
     sem data-form-dados abre vazio, para adicionar; com data-form-dados (json
     da carta) abre preenchido, para editar. Se a validação falhar, a página
     volta com o pop-up já aberto (data-open-on-load), no mesmo modo, mostrando
     os erros e os valores digitados.
     $jogoPadrao: jogo que já vem selecionado no campo "jogo" ao adicionar. --}}
@php
    $erros = $errors->carta;
    $editandoId = $erros->any() ? old('_editar') : null;
    // estados e idiomas vêm de Configurações > Estoque
    $estados = \App\Services\Configuracoes::valor('estoque.estados_carta');
    $idiomas = \App\Services\Configuracoes::valor('estoque.idiomas_carta');
    $padrao = ['nome' => '', 'jogo' => $jogoPadrao, 'colecao' => '', 'raridade' => '', 'idioma' => $idiomas[0] ?? '',
               'estado' => $estados[0] ?? '', 'foil' => false, 'quantidade' => 1, 'preco' => '', 'imagem' => '', 'imagem_preview' => ''];
@endphp

<dialog class="card-dialog" data-form-dialog="carta" @if ($erros->any()) data-open-on-load @endif
        data-form-padrao='@json($padrao)'
        data-action-criar="{{ route('estoque.cartas.store') }}"
        data-action-editar="{{ route('estoque.cartas.update', '__ID__') }}"
        aria-labelledby="card-dialog-title">
    <form method="POST" class="card-form" enctype="multipart/form-data"
          action="{{ $editandoId ? route('estoque.cartas.update', $editandoId) : route('estoque.cartas.store') }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-form-metodo @disabled(! $editandoId)>
        <input type="hidden" name="_editar" value="{{ $editandoId }}">

        <div class="card-form__header">
            <h2 id="card-dialog-title" data-form-titulo data-titulo-criar="Adicionar carta" data-titulo-editar="Editar carta">
                {{ $editandoId ? 'Editar carta' : 'Adicionar carta' }}
            </h2>
            <button type="button" class="icon-btn" data-form-close aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @if ($erros->any())
            <div class="card-form__errors" role="alert" data-form-erros>
                <strong>Confira os campos abaixo:</strong>
                <ul>
                    @foreach ($erros->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3>Identificação</h3>

        <div class="card-form__grid">
            <label class="card-field card-field--full">
                <span>Nome da carta *</span>
                <input type="text" name="nome" required maxlength="150"
                       placeholder="ex.: Charizard ex 199/165" value="{{ old('nome') }}">
            </label>

            <label class="card-field">
                <span>Jogo *</span>
                <select name="jogo" required>
                    @foreach ($jogosDisponiveis as $chave => $nomeJogo)
                        <option value="{{ $chave }}" @selected(old('jogo', $jogoPadrao) === $chave)>{{ $nomeJogo }}</option>
                    @endforeach
                </select>
            </label>

            <label class="card-field">
                <span>Coleção</span>
                <input type="text" name="colecao" maxlength="150"
                       placeholder="ex.: Destined Rivals" value="{{ old('colecao') }}">
            </label>

            <label class="card-field">
                <span>Raridade</span>
                <input type="text" name="raridade" maxlength="100" list="card-raridades"
                       placeholder="ex.: Rara Secreta" value="{{ old('raridade') }}">
                <datalist id="card-raridades">
                    <option value="Comum">
                    <option value="Incomum">
                    <option value="Rara">
                    <option value="Rara Comum">
                    <option value="Rara Holo">
                    <option value="Ultra Rara">
                    <option value="Rara Secreta">
                </datalist>
            </label>

            <label class="card-field">
                <span>Idioma *</span>
                <select name="idioma" required>
                    @foreach ($idiomas as $idioma)
                        <option value="{{ $idioma }}" @selected(old('idioma', $padrao['idioma']) === $idioma)>{{ $idioma }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <h3>Condição e estoque</h3>

        <div class="card-form__grid">
            <label class="card-field">
                <span>Estado *</span>
                <select name="estado" required>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" @selected(old('estado', $padrao['estado']) === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </label>

            <label class="card-field card-field--check">
                <input type="checkbox" name="foil" value="1" @checked(old('foil'))>
                <span>Carta foil (brilhante)</span>
            </label>

            <label class="card-field">
                <span>Quantidade *</span>
                <input type="number" name="quantidade" required min="0" max="9999" step="1"
                       value="{{ old('quantidade', 1) }}">
            </label>

            <label class="card-field">
                <span>Preço unitário (R$) *</span>
                <input type="number" name="preco" required min="0" step="0.01" inputmode="decimal"
                       placeholder="0,00" value="{{ old('preco') }}">
            </label>
        </div>

        <h3>Imagem</h3>

        @include('pages.estoque.partials.imagem-campo', [
            'imagemInicial' => $editandoId ? \App\Models\Carta::find($editandoId)?->dadosFormulario()['imagem_preview'] : '',
        ])

        <div class="card-form__actions">
            <button type="button" class="card-form__cancel" data-form-close>Cancelar</button>
            <button type="submit" class="card-form__submit">
                <i class="bi bi-check-lg"></i>
                Salvar
            </button>
        </div>
    </form>
</dialog>
