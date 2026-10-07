{{-- Cadastro das mesas: formulário no topo (o mesmo serve para adicionar e
     editar) e a lista abaixo, desenhada pelo JS. Mesa com histórico de
     reservas é desativada em vez de excluída. --}}
<div class="modal-overlay" data-modal="mesas" hidden>
    <div class="modal-cliente modal-mesas" role="dialog" aria-modal="true" aria-labelledby="modal-mesas-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-mesas-titulo">Mesas</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__corpo">
            <p class="modal-mesas__intro">As mesas que a loja aluga. O preço por hora é usado para calcular o valor de cada aluguel.</p>

            <form class="mesa-form" data-form-mesa novalidate>
                <h3 class="perfil__titulo" data-mesa-form-titulo>Nova mesa</h3>
                <div class="mesa-form__campos">
                    <div class="campo mesa-form__nome">
                        <label for="mesa-nome">Nome</label>
                        <input type="text" id="mesa-nome" name="nome" maxlength="60" placeholder="Ex.: Mesa 1" required data-mesa-nome>
                        <span class="campo__erro" data-erro="nome" hidden></span>
                    </div>
                    <div class="campo">
                        <label for="mesa-capacidade">Lugares</label>
                        <input type="number" id="mesa-capacidade" name="capacidade" min="1" max="50" value="4" required data-mesa-capacidade>
                        <span class="campo__erro" data-erro="capacidade" hidden></span>
                    </div>
                    <div class="campo">
                        <label for="mesa-preco">Preço por hora</label>
                        <div class="campo__prefixado">
                            <span aria-hidden="true">R$</span>
                            <input type="text" id="mesa-preco" name="preco_hora" inputmode="decimal" placeholder="0,00" required data-mesa-preco>
                        </div>
                        <span class="campo__erro" data-erro="preco_hora" hidden></span>
                    </div>
                </div>
                <div class="mesa-form__acoes">
                    <button type="submit" class="botao botao--principal" data-mesa-salvar>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span data-mesa-salvar-texto>Adicionar mesa</span>
                    </button>
                    <button type="button" class="perfil__cancelar" data-mesa-cancelar-edicao hidden>Cancelar edição</button>
                </div>
            </form>

            <section class="perfil__secao">
                <h3 class="perfil__titulo">Cadastradas</h3>
                <ul class="mesas-lista" data-mesas-lista></ul>
                <p class="perfil__vazio" data-mesas-vazio hidden>Nenhuma mesa cadastrada ainda.</p>
            </section>
        </div>
    </div>
</div>
