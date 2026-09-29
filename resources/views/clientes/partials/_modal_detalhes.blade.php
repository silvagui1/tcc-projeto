{{-- Modal de DETALHES (somente leitura) — o que abre ao clicar numa linha
     da lista. Antes o clique ia direto pro formulário de edição, misturando
     "ver" com "editar"; agora esse modal mostra os dados do cliente e o
     extrato recente de créditos, com "Editar" como uma ação explícita, não
     o único caminho pra consultar um cadastro. Todo o conteúdo é preenchido
     via JS (fetch em /clientes/{id}) — ver clientes.js. --}}
<div class="modal-overlay" data-modal="detalhes" hidden>
    <div class="modal-cliente modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="modal-detalhes-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-detalhes-titulo">Detalhes do cliente</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__corpo">
            <section class="modal-detalhes__cabecalho">
                <span class="avatar avatar--grande" data-detalhes-avatar>
                    <img data-detalhes-imagem hidden alt="">
                    <span data-detalhes-iniciais>--</span>
                </span>
                <div class="modal-detalhes__identificacao">
                    <p class="modal-detalhes__nome" data-detalhes-nome>—</p>
                    <span class="status-badge status-badge--inativo" data-detalhes-status hidden>Inativo</span>
                </div>
            </section>

            <section class="cartao">
                <h3 class="cartao__titulo">Informações</h3>
                <dl class="modal-detalhes__lista">
                    <div>
                        <dt>Nascimento</dt>
                        <dd data-detalhes-nascimento>—</dd>
                    </div>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd data-detalhes-whatsapp>Não informado</dd>
                    </div>
                    <div>
                        <dt>Cliente desde</dt>
                        <dd data-detalhes-criado>—</dd>
                    </div>
                </dl>
                <p class="modal-detalhes__observacoes" data-detalhes-observacoes hidden></p>
            </section>

            <section class="cartao cartao--creditos">
                <h3 class="cartao__titulo">Créditos</h3>
                <p class="creditos__rotulo">saldo atual</p>
                <p class="creditos__valor" data-detalhes-saldo>R$ 0,00</p>

                <p class="modal-detalhes__historico-titulo">Últimas movimentações</p>
                <ul class="creditos__historico-lista" data-detalhes-historico></ul>
                <p class="modal-detalhes__sem-historico" data-detalhes-sem-historico hidden>
                    Nenhuma movimentação registrada ainda.
                </p>
            </section>
        </div>

        <footer class="modal-cliente__rodape modal-detalhes__acoes">
            <a
                href="#"
                target="_blank"
                rel="noopener"
                class="botao botao--whatsapp botao--pill"
                data-detalhes-whatsapp-botao
                hidden
            >
                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                WhatsApp
            </a>
            <button type="button" class="botao botao--principal botao--pill" data-detalhes-editar>
                <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                Editar
            </button>
            <button type="button" class="botao botao--perigo botao--pill" data-detalhes-excluir>
                <i class="bi bi-trash3-fill" aria-hidden="true"></i>
                Excluir
            </button>
        </footer>
    </div>
</div>
