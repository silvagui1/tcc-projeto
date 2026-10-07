{{-- Nova venda — uma tela só, sem etapas, no jeito de um caixa:
     1) itens (busca de produtos, cartas ou aluguéis de mesa + carrinho),
     2) cliente (opcional), 3) pagamento (créditos do cliente, se tiver
     saldo, e a forma de pagamento do restante). O rodapé fixo mostra o
     total e só libera "Finalizar" quando a venda está completa, dizendo o
     que falta. No desktop vira duas colunas. Lógica em vendas.js. --}}
<div class="modal-overlay" data-modal="venda" hidden>
    <div class="modal-cliente modal-venda" role="dialog" aria-modal="true" aria-labelledby="modal-venda-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-venda-titulo">Nova venda</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__erros" data-venda-erros role="alert" hidden></div>

        <div class="modal-venda__grade">
            <section class="modal-venda__secao modal-venda__secao--itens">
                <h3 class="perfil__titulo">Itens</h3>

                <div class="segmentado" role="radiogroup" aria-label="O que adicionar">
                    <label class="segmentado__opcao">
                        <input type="radio" name="catalogo_tipo" value="produto" checked data-catalogo-tipo>
                        <span><i class="bi bi-box-seam" aria-hidden="true"></i> Produtos</span>
                    </label>
                    <label class="segmentado__opcao">
                        <input type="radio" name="catalogo_tipo" value="carta" data-catalogo-tipo>
                        <span><i class="bi bi-stack" aria-hidden="true"></i> Cartas</span>
                    </label>
                    <label class="segmentado__opcao">
                        <input type="radio" name="catalogo_tipo" value="aluguel" data-catalogo-tipo>
                        <span><i class="bi bi-dice-5" aria-hidden="true"></i> Mesas</span>
                    </label>
                </div>

                <div class="busca catalogo__busca">
                    <i class="bi bi-search busca__icone" aria-hidden="true"></i>
                    <input type="search" class="busca__campo" placeholder="Buscar produto" autocomplete="off" aria-label="Buscar item" data-catalogo-busca>
                    <span class="busca__spinner" data-catalogo-spinner hidden aria-hidden="true"></span>
                </div>

                <ul class="catalogo" data-catalogo-resultados aria-label="Resultados"></ul>
                <p class="catalogo__vazio" data-catalogo-vazio hidden></p>

                <div class="carrinho">
                    <div class="carrinho__cabecalho">
                        <h4 class="perfil__subtitulo">Nesta venda</h4>
                        <span class="carrinho__contagem" data-carrinho-contagem></span>
                    </div>
                    <ul class="carrinho__lista" data-carrinho></ul>
                    <p class="carrinho__vazio" data-carrinho-vazio>
                        <i class="bi bi-bag" aria-hidden="true"></i>
                        Nenhum item ainda. Toque num item acima para adicionar.
                    </p>
                </div>
            </section>

            <div class="modal-venda__lateral">
                <section class="modal-venda__secao">
                    <h3 class="perfil__titulo">Cliente <span class="campo__opcional">(opcional)</span></h3>
                    @include('vendas.partials._seletor_cliente', [
                        'nome' => 'venda',
                        'permitirAvulso' => false,
                        'placeholder' => 'Buscar cliente pelo nome',
                    ])
                </section>

                <section class="modal-venda__secao">
                    <h3 class="perfil__titulo">Pagamento</h3>

                    {{-- Só aparece com um cliente que tem saldo. --}}
                    <label class="usar-creditos" data-usar-creditos-bloco hidden>
                        <input type="checkbox" data-usar-creditos>
                        <span class="usar-creditos__chave" aria-hidden="true"></span>
                        <span class="usar-creditos__texto">
                            <strong>Usar créditos do cliente</strong>
                            <small data-usar-creditos-saldo></small>
                        </span>
                    </label>

                    <div class="formas-pagamento" role="radiogroup" aria-label="Forma de pagamento" data-formas-pagamento>
                        @foreach ($formasPagamento as $valor => $forma)
                            <label class="forma-pagamento">
                                <input type="radio" name="forma_pagamento" value="{{ $valor }}" data-forma-pagamento>
                                <span>
                                    <i class="bi {{ $forma['icone'] }}" aria-hidden="true"></i>
                                    {{ $forma['rotulo'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="formas-pagamento__aviso" data-pago-com-creditos hidden>
                        <i class="bi bi-wallet2" aria-hidden="true"></i>
                        Os créditos do cliente cobrem a venda inteira.
                    </p>

                    <button type="button" class="perfil__acao-link venda-observacoes__link" data-mostrar-observacoes>
                        <i class="bi bi-plus" aria-hidden="true"></i> Adicionar observação
                    </button>
                    <div class="campo campo--textarea" data-observacoes-campo hidden>
                        <label for="venda-observacoes">Observações</label>
                        <textarea id="venda-observacoes" rows="2" maxlength="255" placeholder="Ex.: troca combinada, encomenda…" data-venda-observacoes></textarea>
                    </div>
                </section>
            </div>
        </div>

        <footer class="modal-venda__rodape">
            <dl class="totais">
                <div class="totais__linha">
                    <dt>Subtotal</dt>
                    <dd data-total-subtotal>R$ 0,00</dd>
                </div>
                <div class="totais__linha totais__linha--creditos" data-total-creditos-linha hidden>
                    <dt>Créditos do cliente</dt>
                    <dd data-total-creditos>− R$ 0,00</dd>
                </div>
                <div class="totais__linha totais__linha--final">
                    <dt>Total a pagar</dt>
                    <dd data-total-pagar>R$ 0,00</dd>
                </div>
            </dl>
            <button type="button" class="botao botao--principal botao--full botao--grande" data-finalizar-venda disabled>
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                Finalizar venda
            </button>
            <p class="creditos__dica modal-venda__dica" data-venda-dica>Adicione ao menos um item.</p>
        </footer>
    </div>
</div>
