{{-- Modal de EDIÇÃO de cliente. Estrutura própria (não compartilhada com a
     criação) — mantém o estilo original de foto (avatar com iniciais/foto +
     botão "Editar foto"), já que aqui sempre existe um cliente (e portanto
     uma cor de avatar e possivelmente uma foto) para mostrar. O id do
     cliente em edição e a URL de envio são preenchidos via JS ao abrir o
     modal (ver resources/js/clientes.js). --}}
<div class="modal-overlay" data-modal-editar hidden>
    <div class="modal-cliente" role="dialog" aria-modal="true" aria-labelledby="modal-editar-titulo">
        <form data-form-cliente data-url-base="{{ url('/clientes') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="creditos" value="0" data-form-creditos>

            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h2 id="modal-editar-titulo">Editar cliente</h2>
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
                        <button type="button" class="botao botao--principal botao--pill" data-selecionar-foto>
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
                        <label for="cliente-editar-nome">Nome</label>
                        <input type="text" id="cliente-editar-nome" name="nome" placeholder="Nome do cliente" required data-input-nome>
                    </div>
                    <div class="campo">
                        <label for="cliente-editar-nascimento">Data aniversário</label>
                        <input type="date" id="cliente-editar-nascimento" name="data_nascimento" required data-input-nascimento>
                    </div>
                </section>

                <section class="cartao">
                    <div class="campo campo--textarea">
                        <label for="cliente-editar-observacoes">Observações</label>
                        <textarea id="cliente-editar-observacoes" name="observacoes" rows="3" placeholder="Observações" data-input-observacoes></textarea>
                    </div>
                </section>

                <section class="cartao cartao--creditos">
                    <h3 class="cartao__titulo">Créditos cliente</h3>
                    <p class="creditos__rotulo">créditos atuais</p>
                    <p class="creditos__valor" data-creditos-exibicao>R$ 0,00</p>
                    <div class="creditos__acoes">
                        <button type="button" class="botao botao--principal botao--pill" data-ajustar-creditos="adicionar">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Adicionar
                        </button>
                        <button type="button" class="botao botao--perigo botao--pill" data-ajustar-creditos="descontar">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                            Descontar
                        </button>
                    </div>
                </section>
            </div>

            <footer class="modal-cliente__rodape">
                <button type="submit" class="botao botao--principal botao--full" data-botao-salvar>
                    Salvar alterações
                </button>
            </footer>
        </form>
    </div>
</div>
