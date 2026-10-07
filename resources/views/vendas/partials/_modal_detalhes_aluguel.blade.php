{{-- Detalhes de uma reserva. A ação principal muda conforme o status:
     agendada → "Registrar pagamento" (abre a Nova venda já com o aluguel e
     o cliente); paga → atalho para a venda que pagou. Preenchido via JS. --}}
<div class="modal-overlay" data-modal="detalhes-aluguel" hidden>
    <div class="modal-cliente modal-reserva" role="dialog" aria-modal="true" aria-labelledby="modal-detalhes-aluguel-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-detalhes-aluguel-titulo">Reserva</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__corpo">
            <section class="reserva__cabecalho">
                <span class="aluguel-status" data-reserva-status></span>
                <p class="reserva__horario" data-reserva-horario></p>
                <p class="reserva__data" data-reserva-data></p>
                <p class="reserva__mesa" data-reserva-mesa></p>

                <div class="perfil__acoes reserva__acoes">
                    <button type="button" class="botao botao--neutro" data-reserva-editar>
                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                        Editar
                    </button>
                    <button type="button" class="botao botao--principal" data-reserva-pagar>
                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                        Registrar pagamento
                    </button>
                    <button type="button" class="botao botao--neutro" data-reserva-ver-venda hidden>
                        <i class="bi bi-receipt" aria-hidden="true"></i>
                        <span data-reserva-venda-rotulo>Ver venda</span>
                    </button>
                </div>
            </section>

            <section class="perfil__secao">
                <dl class="perfil__lista">
                    <div class="perfil__item">
                        <dt>Quem</dt>
                        <dd class="reserva__quem" data-reserva-quem></dd>
                    </div>
                    <div class="perfil__item">
                        <dt>Jogo</dt>
                        <dd data-reserva-jogo></dd>
                    </div>
                    <div class="perfil__item" data-reserva-serie-item hidden>
                        <dt>Repetição</dt>
                        <dd data-reserva-serie></dd>
                    </div>
                    <div class="perfil__item">
                        <dt>Valor</dt>
                        <dd data-reserva-valor></dd>
                    </div>
                </dl>
                <p class="perfil__observacoes" data-reserva-observacoes hidden></p>
            </section>
        </div>

        <footer class="perfil__rodape" data-reserva-rodape>
            <button type="button" class="perfil__excluir" data-reserva-cancelar>
                <i class="bi bi-calendar-x" aria-hidden="true"></i>
                Cancelar reserva
            </button>
        </footer>
    </div>
</div>
