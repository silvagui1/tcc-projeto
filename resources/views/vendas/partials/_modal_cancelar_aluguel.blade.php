{{-- Confirmação de cancelamento de reserva. Mesmo visual do modal de
     confirmação de Clientes, com uma escolha extra quando a reserva faz parte
     de uma série semanal: só esta data ou esta e as próximas. --}}
<div class="modal-overlay modal-overlay--confirmar" data-modal="cancelar-aluguel" hidden>
    <div class="modal-confirmar" role="alertdialog" aria-modal="true" aria-labelledby="cancelar-aluguel-titulo" aria-describedby="cancelar-aluguel-texto">
        <i class="bi bi-calendar-x modal-confirmar__icone" aria-hidden="true"></i>
        <h2 id="cancelar-aluguel-titulo">Cancelar reserva?</h2>
        <p id="cancelar-aluguel-texto" data-cancelar-aluguel-texto></p>

        <div class="opcoes-cancelamento" role="radiogroup" aria-label="O que cancelar" data-cancelar-aluguel-opcoes hidden>
            <label class="opcao-cancelamento">
                <input type="radio" name="escopo_cancelamento" value="este" checked>
                <span>
                    <strong>Só esta data</strong>
                    <small data-cancelar-aluguel-este></small>
                </span>
            </label>
            <label class="opcao-cancelamento">
                <input type="radio" name="escopo_cancelamento" value="proximos">
                <span>
                    <strong>Esta e as próximas</strong>
                    <small data-cancelar-aluguel-proximos></small>
                </span>
            </label>
        </div>

        <div class="modal-confirmar__acoes">
            <button type="button" class="botao botao--neutro botao--full" data-fechar-modal>Voltar</button>
            <button type="button" class="botao botao--perigo botao--full" data-cancelar-aluguel-confirmar>Cancelar reserva</button>
        </div>
    </div>
</div>
