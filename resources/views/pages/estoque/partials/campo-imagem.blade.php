{{-- Campo de imagem dos formulários do estoque: arrastar um arquivo, colar
     (Ctrl+V) uma imagem copiada ou clicar para escolher. Também aceita um
     link, como antes. O app.js ([data-image-drop]) cuida do arrastar/colar e
     da prévia. O form que usa este campo precisa de enctype="multipart/form-data".
     $erros: os erros de validação desse formulário (cada pop-up tem os seus). --}}
<div class="image-drop" data-image-drop>
    <label class="image-drop__zona" data-image-drop-zona>
        <input type="file" name="imagem_arquivo" accept="image/*" class="image-drop__input" data-image-drop-input>

        <span class="image-drop__vazio">
            <i class="bi bi-image" aria-hidden="true"></i>
            <strong>Arraste uma imagem aqui ou clique para escolher</strong>
            <small>Você também pode copiar uma imagem e colar com Ctrl+V</small>
        </span>

        <span class="image-drop__previa" hidden>
            <img src="" alt="Prévia da imagem escolhida" data-image-drop-previa>
            <span class="image-drop__nome" data-image-drop-nome></span>
        </span>
    </label>

    <button type="button" class="image-drop__remover" data-image-drop-remover hidden>
        <i class="bi bi-x-lg" aria-hidden="true"></i> Remover imagem
    </button>
</div>

@if ($erros->any() && ! $erros->has('imagem_arquivo'))
    <p class="image-drop__aviso">Se você tinha escolhido um arquivo, escolha de novo: o navegador não guarda o arquivo quando o formulário volta com erro.</p>
@endif

<label class="card-field card-field--full image-drop__url">
    <span>Ou cole o link da imagem</span>
    <input type="url" name="imagem" maxlength="500"
           placeholder="https://..." value="{{ old('imagem') }}">
</label>
