{{-- Modal de PERFIL (somente leitura) — abre ao clicar numa linha da lista.
     Estilo de perfil aberto, como numa rede social: foto circular grande e
     centralizada, nome logo abaixo, ações na sequência e as informações em
     seções com divisórias (sem cards). Todo o conteúdo é preenchido via JS
     (fetch em /clientes/{id}) — ver clientes.js. --}}
<div class="modal-overlay" data-modal="detalhes" hidden>
    <div class="modal-cliente modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="modal-detalhes-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-detalhes-titulo">Perfil</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__corpo">
            <section class="perfil__cabecalho">
                <span class="perfil__avatar" data-detalhes-avatar>
                    <img data-detalhes-imagem hidden alt="">
                    <span data-detalhes-iniciais>--</span>
                </span>
                <p class="perfil__nome" data-detalhes-nome>—</p>
                <span class="status-badge status-badge--inativo" data-detalhes-status hidden>Inativo</span>

                <div class="perfil__acoes">
                    <a
                        href="#"
                        target="_blank"
                        rel="noopener"
                        class="botao botao--whatsapp"
                        data-detalhes-whatsapp-botao
                        hidden
                    >
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        WhatsApp
                    </a>
                    <button type="button" class="botao botao--principal" data-detalhes-editar>
                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                        Editar
                    </button>
                </div>
            </section>

            <section class="perfil__secao">
                <h3 class="perfil__titulo">Informações</h3>
                <dl class="perfil__lista">
                    <div class="perfil__item">
                        <dt>Nascimento</dt>
                        <dd data-detalhes-nascimento>—</dd>
                    </div>
                    <div class="perfil__item">
                        <dt>WhatsApp</dt>
                        <dd data-detalhes-whatsapp>Não informado</dd>
                        <button type="button" class="perfil__acao-link" data-detalhes-editar data-detalhes-whatsapp-adicionar hidden>
                            Adicionar WhatsApp
                        </button>
                    </div>
                    <div class="perfil__item">
                        <dt>Cadastrado em</dt>
                        <dd data-detalhes-criado>—</dd>
                    </div>
                </dl>
                <p class="perfil__observacoes" data-detalhes-observacoes hidden></p>
            </section>

            <section class="perfil__secao">
                <div class="perfil__cabecalho-secao">
                    <h3 class="perfil__titulo">Créditos</h3>
                    <button type="button" class="perfil__acao-link" data-detalhes-editar>
                        Ajustar créditos
                    </button>
                </div>
                <p class="perfil__rotulo">saldo atual</p>
                <p class="perfil__saldo" data-detalhes-saldo>R$ 0,00</p>

                <h4 class="perfil__subtitulo">Últimas movimentações</h4>
                <ul class="creditos__historico-lista" data-detalhes-historico></ul>
                <p class="perfil__vazio" data-detalhes-sem-historico hidden>
                    Nenhuma movimentação registrada ainda.
                </p>
            </section>
        </div>

        <footer class="perfil__rodape">
            <button type="button" class="perfil__excluir" data-detalhes-excluir>
                <i class="bi bi-trash3" aria-hidden="true"></i>
                Excluir cliente
            </button>
        </footer>
    </div>
</div>
