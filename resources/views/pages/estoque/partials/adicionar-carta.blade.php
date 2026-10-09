{{-- Pop-up "Adicionar carta" — usado na aba "estoque cartas" e na página de
     cartas. Abre ao clicar em qualquer botão com data-card-dialog-open="carta" (ver
     app.js). Se a validação falhar, a página volta com o pop-up já aberto
     (data-open-on-load) mostrando os erros e os valores digitados.
     $jogoPadrao: jogo que já vem selecionado no campo "jogo". --}}
@if (session('cartaAdicionada'))
    <p class="card-added-alert" role="status">
        <i class="bi bi-check-circle-fill"></i>
        "{{ session('cartaAdicionada') }}" foi adicionada ao estoque.
    </p>
@endif

<dialog class="card-dialog" data-card-dialog="carta" @if ($errors->any()) data-open-on-load @endif
        aria-labelledby="card-dialog-title">
    <form method="POST" action="{{ route('estoque.cartas.adicionar') }}" class="card-form"
          enctype="multipart/form-data" data-envio-unico>
        @csrf

        <div class="card-form__header">
            <h2 id="card-dialog-title">Adicionar carta</h2>
            <button type="button" class="icon-btn" data-card-dialog-close aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @if ($errors->any())
            <div class="card-form__errors" role="alert">
                <strong>Confira os campos abaixo:</strong>
                <ul>
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3>Identificação</h3>

        <div class="card-form__grid">
            <label class="card-field card-field--full">
                <span>Nome da carta *</span>
                <input type="text" name="nome" required maxlength="120"
                       placeholder="ex.: Charizard ex 199/165" value="{{ old('nome') }}">
            </label>

            <label class="card-field">
                <span>Jogo *</span>
                <select name="jogo" required>
                    @foreach ($jogosDisponiveis as $jogo)
                        <option value="{{ $jogo }}" @selected(old('jogo', $jogoPadrao) === $jogo)>{{ nomeDoJogo($jogo) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="card-field">
                <span>Coleção</span>
                <input type="text" name="colecao" maxlength="120"
                       placeholder="ex.: Destined Rivals" value="{{ old('colecao') }}">
            </label>

            <label class="card-field">
                <span>Raridade</span>
                <input type="text" name="raridade" maxlength="60" list="card-raridades"
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
                    @foreach (['Português', 'Inglês', 'Japonês', 'Espanhol', 'Outro'] as $idioma)
                        <option value="{{ $idioma }}" @selected(old('idioma', 'Português') === $idioma)>{{ $idioma }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <h3>Condição e estoque</h3>

        <div class="card-form__grid">
            <label class="card-field">
                <span>Estado *</span>
                <select name="estado" required>
                    @foreach (['Novo', 'Semi-Novo', 'Usado', 'Danificado'] as $estado)
                        <option value="{{ $estado }}" @selected(old('estado', 'Novo') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </label>

            <label class="card-field card-field--check">
                <input type="checkbox" name="foil" value="1" @checked(old('foil'))>
                <span>Carta foil (brilhante)</span>
            </label>

            <label class="card-field">
                <span>Quantidade *</span>
                <input type="number" name="quantidade" required min="1" max="9999" step="1"
                       value="{{ old('quantidade', 1) }}">
            </label>

            <label class="card-field">
                <span>Preço unitário (R$) *</span>
                <input type="number" name="preco" required min="0" step="0.01" inputmode="decimal"
                       placeholder="0,00" value="{{ old('preco') }}">
            </label>
        </div>

        <h3>Imagem</h3>

        @include('pages.estoque.partials.campo-imagem', ['erros' => $errors->getBag('default')])

        <div class="card-form__actions">
            <button type="button" class="card-form__cancel" data-card-dialog-close>Cancelar</button>
            <button type="submit" class="card-form__submit">
                <i class="bi bi-plus-lg"></i>
                Adicionar carta
            </button>
        </div>
    </form>
</dialog>
