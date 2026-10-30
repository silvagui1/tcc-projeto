{{-- Pop-up de confirmação do estoque (arquivar / excluir de vez), no padrão
     .dialogo-confirmar do sistema. Abre ao clicar em qualquer botão com
     data-delete-open="<url>": o app.js coloca a url como action do form e
     troca título, texto e rótulo do botão pelos data-delete-title,
     data-delete-texto e data-delete-botao do botão clicado. --}}
<dialog class="dialogo-confirmar" data-delete-dialog aria-labelledby="estoque-confirmar-titulo">
    <form method="POST" action="" data-delete-form>
        @csrf
        @method('DELETE')

        <h2 class="dialogo-confirmar__titulo" id="estoque-confirmar-titulo" data-delete-title>Arquivar este item?</h2>
        <p class="dialogo-confirmar__texto" data-delete-texto></p>

        <div class="dialogo-confirmar__acoes">
            <button type="button" class="botao botao--neutro" data-delete-cancel>Manter</button>
            <button type="submit" class="botao botao--perigo" data-delete-botao>Arquivar</button>
        </div>
    </form>
</dialog>
