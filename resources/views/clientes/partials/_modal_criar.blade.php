{{-- Modal de CRIAÇÃO de cliente. Estrutura própria (não compartilhada com a
     edição) — a principal diferença visual é o campo de foto: um círculo
     vazio que o usuário preenche clicando nele, no estilo comum de redes
     sociais, em vez do avatar + botão "Editar foto" usado na edição
     (ver _modal_editar.blade.php). --}}
<div class="modal-overlay" data-modal-criar hidden>
    <div class="modal-cliente" role="dialog" aria-modal="true" aria-labelledby="modal-criar-titulo">
        <form data-form-cliente action="{{ route('clientes.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="creditos" value="0" data-form-creditos>

            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h2 id="modal-criar-titulo">Adicionar cliente</h2>
                <span class="modal-cliente__espaco" aria-hidden="true"></span>
            </header>

            <div class="modal-cliente__corpo">
                <div class="modal-cliente__erros" data-modal-erros hidden></div>

                <section class="cartao cartao--foto-upload">
                    <button type="button" class="avatar-upload" data-selecionar-foto aria-label="Adicionar foto do cliente">
                        <span class="avatar-upload__preview" data-preview-avatar>
                            <img data-preview-imagem hidden alt="Pré-visualização da foto">
                            <i class="bi bi-camera-fill" data-preview-icone-vazio></i>
                        </span>
                        <span class="avatar-upload__badge" aria-hidden="true">
                            <i class="bi bi-camera-fill"></i>
                        </span>
                    </button>
                    <input
                        type="file"
                        name="foto"
                        accept="image/png,image/jpeg,image/webp"
                        hidden
                        data-input-foto
                    >
                    <p class="avatar-upload__legenda">toque para adicionar uma foto</p>
                </section>

                <section class="cartao">
                    <h3 class="cartao__titulo">Informações cliente</h3>
                    <div class="campo">
                        <label for="cliente-criar-nome">Nome</label>
                        <input type="text" id="cliente-criar-nome" name="nome" placeholder="Nome do cliente" required data-input-nome>
                    </div>
                    <div class="campo">
                        <label for="cliente-criar-nascimento">Data aniversário</label>
                        <input type="date" id="cliente-criar-nascimento" name="data_nascimento" required data-input-nascimento>
                    </div>
                </section>

                <section class="cartao">
                    <div class="campo campo--textarea">
                        <label for="cliente-criar-observacoes">Observações</label>
                        <textarea id="cliente-criar-observacoes" name="observacoes" rows="3" placeholder="Observações" data-input-observacoes></textarea>
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
                    Adicionar cliente
                </button>
            </footer>
        </form>
    </div>
</div>
