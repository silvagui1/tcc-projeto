{{-- Campo de imagem dos pop-ups de produto e de carta (ver app.js →
     "imagem do estoque"). A imagem pode ser escolhida do aparelho (no
     celular, também pela câmera), colada com Ctrl+V em qualquer lugar do
     pop-up, arrastada para a área tracejada ou informada por link. "Remover"
     tira a imagem do item (ele volta a usar a imagem genérica). --}}
<div class="imagem-campo card-field--full" data-imagem-campo data-imagem-inicial="{{ $imagemInicial ?? '' }}">
    <div class="imagem-campo__area" data-imagem-area>
        <img class="imagem-campo__preview" data-imagem-preview alt="Pré-visualização da imagem" hidden>

        <div class="imagem-campo__vazio" data-imagem-vazio>
            <i class="bi bi-image"></i>
            <span>Cole (Ctrl+V), arraste uma imagem para cá<br>ou escolha do aparelho</span>
        </div>
    </div>

    <div class="imagem-campo__acoes">
        <label class="imagem-campo__btn">
            <i class="bi bi-upload"></i>
            Escolher imagem
            <input type="file" name="imagem_arquivo" accept="image/*" data-imagem-arquivo hidden>
        </label>
        <button type="button" class="imagem-campo__btn imagem-campo__btn--remover" data-imagem-remover hidden>
            <i class="bi bi-trash"></i>
            Remover
        </button>
    </div>

    <input type="hidden" name="remover_imagem" value="0" data-imagem-remover-input>

    <label class="card-field">
        <span>ou cole o link da imagem</span>
        <input type="url" name="imagem" maxlength="500" placeholder="https://..."
               value="{{ old('imagem') }}" data-imagem-url>
    </label>
</div>
