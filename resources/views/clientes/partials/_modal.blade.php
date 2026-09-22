<div class="modal-overlay" data-modal-overlay hidden>
    <div class="modal-cliente" role="dialog" aria-modal="true" aria-labelledby="modal-cliente-titulo">
        <form data-form-cliente enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST" data-form-method>
            <input type="hidden" name="id" data-form-id>
            <input type="hidden" name="creditos" value="0" data-form-creditos>

            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <h2 id="modal-cliente-titulo" data-modal-titulo>Adicionar cliente</h2>
                <span class="modal-cliente__espaco" aria-hidden="true"></span>
            </header>

            <div class="modal-cliente__corpo">
                <div class="modal-cliente__erros" data-modal-erros hidden></div>

                <section class="cartao">
                    <h3 class="cartao__titulo">Foto cliente</h3>
                    <div class="cartao__linha-foto">
                        <span class="avatar avatar--grande" data-preview-avatar>
                            <img data-preview-imagem hidden alt="Pré-visualização da foto">
                            <span data-preview-iniciais>--</span>
                        </span>
                        <button type="button" class="botao botao--azul botao--pill" data-selecionar-foto>
                            Editar foto
                        </button>
                        <input
                            type="file"
                            name="foto"
                            accept="image/png,image/jpeg,image/webp"
                            hidden
                            data-input-foto
                        >
                    </div>
                </section>

                <section class="cartao">
                    <h3 class="cartao__titulo">Informações cliente</h3>
                    <div class="campo">
                        <label for="cliente-nome">Nome</label>
                        <input type="text" id="cliente-nome" name="nome" placeholder="Nome do cliente" required data-input-nome>
                    </div>
                    <div class="campo">
                        <label for="cliente-nascimento">Data aniversário</label>
                        <input type="date" id="cliente-nascimento" name="data_nascimento" required data-input-nascimento>
                    </div>
                </section>

                <section class="cartao">
                    <div class="campo campo--textarea">
                        <label for="cliente-observacoes">Observações</label>
                        <textarea id="cliente-observacoes" name="observacoes" rows="3" placeholder="Observações" data-input-observacoes></textarea>
                    </div>
                </section>

                <section class="cartao cartao--creditos">
                    <h3 class="cartao__titulo">Créditos cliente</h3>
                    <p class="creditos__rotulo">créditos atuais</p>
                    <p class="creditos__valor" data-creditos-exibicao>R$ 0,00</p>
                    <div class="creditos__acoes">
                        <button type="button" class="botao botao--azul botao--pill" data-ajustar-creditos="adicionar">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="m6 14 6-6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Adicionar
                        </button>
                        <button type="button" class="botao botao--vermelho botao--pill" data-ajustar-creditos="descontar">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="m6 10 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Descontar
                        </button>
                    </div>
                </section>
            </div>

            <footer class="modal-cliente__rodape">
                <button type="submit" class="botao botao--azul botao--full" data-botao-salvar>
                    Salvar alterações
                </button>
            </footer>
        </form>
    </div>
</div>
