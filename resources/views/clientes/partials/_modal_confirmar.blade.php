{{-- Modal de confirmação genérico — substitui o window.confirm() nativo do
     navegador (usado antes só na exclusão em massa), que quebrava o padrão
     visual do resto da tela e não podia ser estilizado. Título, texto e o
     rótulo do botão de confirmação são preenchidos via JS antes de abrir
     (ver função `confirmar()` em clientes.js), então serve tanto pra
     exclusão em massa quanto pra exclusão a partir do modal de detalhes. --}}
<div class="modal-overlay modal-overlay--confirmar" data-modal-confirmar hidden>
    <div class="modal-confirmar" role="alertdialog" aria-modal="true" aria-labelledby="modal-confirmar-titulo" aria-describedby="modal-confirmar-texto">
        <i class="bi bi-exclamation-triangle-fill modal-confirmar__icone" aria-hidden="true"></i>
        <h2 id="modal-confirmar-titulo" data-confirmar-titulo>Tem certeza?</h2>
        <p id="modal-confirmar-texto" data-confirmar-texto></p>
        <div class="modal-confirmar__acoes">
            <button type="button" class="botao botao--neutro botao--pill botao--full" data-confirmar-cancelar>
                Cancelar
            </button>
            <button type="button" class="botao botao--perigo botao--pill botao--full" data-confirmar-ok>
                Excluir
            </button>
        </div>
    </div>
</div>
