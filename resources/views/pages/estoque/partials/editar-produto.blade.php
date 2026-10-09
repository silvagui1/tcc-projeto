{{-- Pop-ups "Editar produto" — um por produto da lista, aberto pelo lápis do
     card (data-card-dialog-open="produto-editar-{id}", ver app.js). Mesmo
     visual do pop-up de adicionar, já preenchido com os dados do produto.
     Os erros ficam no grupo $errors->produtoEditar; o campo produto_id diz
     qual pop-up reabrir (com o que foi digitado) quando a validação falha. --}}
@php
    $errosEditar = $errors->getBag('produtoEditar');
    $semErros = new \Illuminate\Support\MessageBag();
@endphp

@if (session('produtoEditado'))
    <p class="card-added-alert" role="status">
        <i class="bi bi-check-circle-fill"></i>
        "{{ session('produtoEditado') }}" foi atualizado.
    </p>
@endif

@foreach ($produtos as $produto)
    @php
        // este pop-up voltou com erro? então mostra o que foi digitado
        $voltouComErro = $errosEditar->any() && (string) old('produto_id') === (string) $produto['id'];
        $valor = fn ($campo, $atual) => $voltouComErro ? old($campo, '') : $atual;
    @endphp

    <dialog class="card-dialog" data-card-dialog="produto-editar-{{ $produto['id'] }}"
            @if ($voltouComErro) data-open-on-load @endif
            aria-labelledby="product-edit-title-{{ $produto['id'] }}">
        <form method="POST" action="{{ route('estoque.produtos.editar', $produto['id']) }}" class="card-form"
              enctype="multipart/form-data" data-envio-unico>
            @csrf
            @method('PUT')
            <input type="hidden" name="produto_id" value="{{ $produto['id'] }}">

            <div class="card-form__header">
                <h2 id="product-edit-title-{{ $produto['id'] }}">Editar produto</h2>
                <button type="button" class="icon-btn" data-card-dialog-close aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            @if ($voltouComErro)
                <div class="card-form__errors" role="alert">
                    <strong>Confira os campos abaixo:</strong>
                    <ul>
                        @foreach ($errosEditar->all() as $erro)
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
                           value="{{ $valor('nome', $produto['nome']) }}">
                </label>

                <label class="card-field">
                    <span>Categoria *</span>
                    <select name="categoria" required>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria }}"
                                    @selected(mb_strtolower($valor('categoria', $produto['categoria'])) === mb_strtolower($categoria))>{{ Str::ucfirst($categoria) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="card-field">
                    <span>Preço (R$) *</span>
                    <input type="number" name="preco" required min="0" step="0.01" inputmode="decimal"
                           value="{{ $valor('preco', $produto['preco']) }}">
                </label>

                <label class="card-field card-field--full">
                    <span>Descrição</span>
                    <textarea name="descricao" rows="3" maxlength="300">{{ $valor('descricao', $produto['descricao']) }}</textarea>
                </label>
            </div>

            <h3>Imagem</h3>

            {{-- o link atual já vem preenchido: deixar como está mantém a imagem;
                 apagar o link volta para a imagem genérica da categoria --}}
            @include('pages.estoque.partials.campo-imagem', [
                'erros' => $voltouComErro ? $errosEditar : $semErros,
                'valorImagem' => $valor('imagem', $produto['imagemSalva']),
            ])

            <div class="card-form__actions">
                <button type="button" class="card-form__cancel" data-card-dialog-close>Cancelar</button>
                <button type="submit" class="card-form__submit">
                    <i class="bi bi-check-lg"></i>
                    Salvar alterações
                </button>
            </div>
        </form>
    </dialog>
@endforeach
