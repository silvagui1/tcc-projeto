{{-- Pop-up de produto — o mesmo formulário serve para adicionar e para
     editar, igual ao de carta (partials/carta-form, ver comentário lá).
     Abre com qualquer botão data-form-open="produto". --}}
@php
    $erros = $errors->produto;
    $editandoId = $erros->any() ? old('_editar') : null;
    $padrao = ['nome' => '', 'categoria_id' => $categorias->first()?->id, 'descricao' => '',
               'quantidade' => 1, 'preco' => '', 'imagem' => '', 'imagem_preview' => ''];
@endphp

<dialog class="card-dialog" data-form-dialog="produto" @if ($erros->any()) data-open-on-load @endif
        data-form-padrao='@json($padrao)'
        data-action-criar="{{ route('estoque.produtos.store') }}"
        data-action-editar="{{ route('estoque.produtos.update', '__ID__') }}"
        aria-labelledby="product-dialog-title">
    <form method="POST" class="card-form" enctype="multipart/form-data"
          action="{{ $editandoId ? route('estoque.produtos.update', $editandoId) : route('estoque.produtos.store') }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-form-metodo @disabled(! $editandoId)>
        <input type="hidden" name="_editar" value="{{ $editandoId }}">

        <div class="card-form__header">
            <h2 id="product-dialog-title" data-form-titulo data-titulo-criar="Adicionar produto" data-titulo-editar="Editar produto">
                {{ $editandoId ? 'Editar produto' : 'Adicionar produto' }}
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

        <div class="card-form__grid">
            <label class="card-field card-field--full">
                <span>Nome do produto *</span>
                <input type="text" name="nome" required maxlength="150"
                       placeholder="ex.: Booster Pokémon" value="{{ old('nome') }}">
            </label>

            <label class="card-field">
                <span>Categoria *</span>
                <select name="categoria_id" required>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $padrao['categoria_id']) === $categoria->id)>
                            {{ $categoria->nome }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="card-field">
                <span>Quantidade *</span>
                <input type="number" name="quantidade" required min="0" max="99999" step="1"
                       value="{{ old('quantidade', 1) }}">
            </label>

            <label class="card-field">
                <span>Preço unitário (R$) *</span>
                <input type="number" name="preco" required min="0" step="0.01" inputmode="decimal"
                       placeholder="0,00" value="{{ old('preco') }}">
            </label>

            <label class="card-field card-field--full">
                <span>Descrição</span>
                <input type="text" name="descricao" maxlength="1000"
                       placeholder="ex.: lata de 350ml" value="{{ old('descricao') }}">
            </label>

            @include('pages.estoque.partials.imagem-campo', [
                'imagemInicial' => $editandoId ? \App\Models\Produto::find($editandoId)?->dadosFormulario()['imagem_preview'] : '',
            ])
        </div>

        <div class="card-form__actions">
            <button type="button" class="card-form__cancel" data-form-close>Cancelar</button>
            <button type="submit" class="card-form__submit">
                <i class="bi bi-check-lg"></i>
                Salvar
            </button>
        </div>
    </form>
</dialog>
