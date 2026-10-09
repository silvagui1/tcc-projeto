{{-- Pop-up "Adicionar produto" — aba "estoque produtos". Abre pelo card
     "Adicionar produto" (data-card-dialog-open="produto", ver app.js) e usa o
     mesmo visual do pop-up de carta. Os erros ficam num grupo próprio
     ($errors->produto) para não se misturarem com os do form de carta. --}}
@php
    $errosProduto = $errors->getBag('produto');
    // só reaproveita o que foi digitado quando foi ESTE form que voltou com erro
    // (e não o de editar produto, que usa os mesmos nomes de campo)
    $antigo = fn ($campo) => $errosProduto->any() ? old($campo, '') : '';
@endphp

@if (session('produtoAdicionado'))
    <p class="card-added-alert" role="status">
        <i class="bi bi-check-circle-fill"></i>
        "{{ session('produtoAdicionado') }}" foi adicionado ao estoque.
    </p>
@endif

<dialog class="card-dialog" data-card-dialog="produto" @if ($errosProduto->any()) data-open-on-load @endif
        aria-labelledby="product-dialog-title">
    <form method="POST" action="{{ route('estoque.produtos.adicionar') }}" class="card-form"
          enctype="multipart/form-data" data-envio-unico>
        @csrf

        <div class="card-form__header">
            <h2 id="product-dialog-title">Adicionar produto</h2>
            <button type="button" class="icon-btn" data-card-dialog-close aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @if ($errosProduto->any())
            <div class="card-form__errors" role="alert">
                <strong>Confira os campos abaixo:</strong>
                <ul>
                    @foreach ($errosProduto->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3>Produto</h3>

        <div class="card-form__grid">
            <label class="card-field card-field--full">
                <span>Nome do produto *</span>
                <input type="text" name="nome" required maxlength="120"
                       placeholder="ex.: Booster Pokémon" value="{{ $antigo('nome') }}">
            </label>

            <label class="card-field">
                <span>Categoria *</span>
                <select name="categoria" required>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria }}" @selected($antigo('categoria') === $categoria)>{{ Str::ucfirst($categoria) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="card-field">
                <span>Preço (R$) *</span>
                <input type="number" name="preco" required min="0" step="0.01" inputmode="decimal"
                       placeholder="0,00" value="{{ $antigo('preco') }}">
            </label>

            <label class="card-field card-field--full">
                <span>Descrição</span>
                <textarea name="descricao" rows="3" maxlength="300"
                          placeholder="ex.: booster da coleção Evolving Skies">{{ $antigo('descricao') }}</textarea>
            </label>
        </div>

        <h3>Imagem</h3>

        @include('pages.estoque.partials.campo-imagem', ['erros' => $errosProduto, 'valorImagem' => $antigo('imagem')])

        <div class="card-form__actions">
            <button type="button" class="card-form__cancel" data-card-dialog-close>Cancelar</button>
            <button type="submit" class="card-form__submit">
                <i class="bi bi-plus-lg"></i>
                Adicionar produto
            </button>
        </div>
    </form>
</dialog>
