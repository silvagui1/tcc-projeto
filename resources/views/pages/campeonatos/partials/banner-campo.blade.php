{{-- Banner do campeonato. Sem imagem própria aparece o banner do sistema
     (muda ao vivo com jogo e tipo). A imagem pode vir do aparelho, de Ctrl+V,
     de arrastar ou de um link — o mesmo comportamento do campo de imagem do
     Estoque (iniciarCampoDeImagem em comum.js usa os data-imagem-*), com o
     visual dos campos daqui. --}}
<div class="champ-banner-campo" data-imagem-campo data-imagem-inicial="{{ $preview }}">
    <span class="champ-premios__rotulo">Banner <span class="opcional">(opcional)</span></span>

    <div class="champ-banner-campo__area" data-imagem-area role="button" tabindex="0" aria-label="Escolher imagem do banner">
        <img class="champ-banner-campo__preview" data-imagem-preview alt="Pré-visualização do banner" hidden>

        {{-- sem imagem: o banner do sistema, com um selo no canto (nada por cima do texto) --}}
        <div class="champ-banner-campo__sistema" data-imagem-vazio>
            <img class="champ-banner--sistema" src="{{ \App\Models\Campeonato::urlBanner($jogo, $tipo) }}" alt="" data-banner-sistema>
            <span class="champ-banner-campo__selo"><i class="bi bi-magic" aria-hidden="true"></i> Banner do sistema</span>
        </div>
    </div>

    <div class="champ-banner-campo__acoes">
        <label class="botao botao--neutro botao--pequeno">
            <i class="bi bi-upload" aria-hidden="true"></i>
            Enviar imagem
            <input type="file" name="imagem_arquivo" accept="image/*" data-imagem-arquivo hidden>
        </label>
        <button type="button" class="botao botao--fantasma botao--pequeno champ-banner-campo__remover" data-imagem-remover hidden>
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            Voltar ao do sistema
        </button>
        <span class="champ-banner-campo__dica">ou cole (Ctrl+V) / arraste aqui</span>
    </div>

    <input type="hidden" name="remover_imagem" value="0" data-imagem-remover-input>

    <div class="campo">
        <label for="campeonato-imagem">Ou o link da imagem <span class="opcional">(opcional)</span></label>
        <input id="campeonato-imagem" type="url" name="imagem" maxlength="500" placeholder="https://..."
               value="{{ $link }}" data-imagem-url>
    </div>
</div>
