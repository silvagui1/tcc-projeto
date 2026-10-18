{{-- Confirmação antes de enviar um form com data-confirmar="texto" (ver
     campeonatos.js). Título e botão podem vir do form em
     data-confirmar-titulo e data-confirmar-sim. --}}
<dialog class="champ-dialog" id="dialog-confirmar" data-champ-confirmar aria-labelledby="dialog-confirmar-titulo">
    <h2 class="champ-dialog__titulo" id="dialog-confirmar-titulo" data-confirmar-titulo>Confirmar?</h2>
    <p class="champ-dialog__texto" data-confirmar-texto></p>

    <div class="champ-dialog__acoes">
        <button type="button" class="botao botao--neutro" data-dialog-fechar>Voltar</button>
        <button type="button" class="botao botao--principal" data-confirmar-sim>Confirmar</button>
    </div>
</dialog>
