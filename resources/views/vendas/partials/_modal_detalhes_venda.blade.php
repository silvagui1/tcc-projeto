{{-- Detalhes de uma venda (somente leitura), como um recibo. Preenchido via
     JS (GET /vendas/{id}). Cancelar devolve estoque e créditos e mantém a
     venda no histórico, riscada. --}}
<div class="modal-overlay" data-modal="detalhes-venda" hidden>
    <div class="modal-cliente modal-recibo" role="dialog" aria-modal="true" aria-labelledby="modal-detalhes-venda-titulo">
        <header class="modal-cliente__topo">
            <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <h2 id="modal-detalhes-venda-titulo" data-recibo-titulo>Venda</h2>
            <span class="modal-cliente__espaco" aria-hidden="true"></span>
        </header>

        <div class="modal-cliente__corpo">
            <section class="recibo__cabecalho">
                <p class="perfil__rotulo">total</p>
                <p class="recibo__total" data-recibo-total>R$ 0,00</p>
                <p class="recibo__data" data-recibo-data></p>
                <span class="status-badge status-badge--cancelada" data-recibo-cancelada hidden></span>
                <p class="recibo__motivo" data-recibo-motivo hidden></p>
            </section>

            <section class="perfil__secao">
                <h3 class="perfil__titulo">Itens</h3>
                <ul class="recibo__itens" data-recibo-itens></ul>
            </section>

            <section class="perfil__secao">
                <h3 class="perfil__titulo">Pagamento</h3>
                <dl class="recibo__pagamento" data-recibo-pagamento></dl>
            </section>

            <section class="perfil__secao">
                <h3 class="perfil__titulo">Cliente</h3>
                <div class="recibo__cliente" data-recibo-cliente></div>
            </section>

            <section class="perfil__secao" data-recibo-observacoes-secao hidden>
                <h3 class="perfil__titulo">Observações</h3>
                <p class="perfil__observacoes" data-recibo-observacoes></p>
            </section>

            {{-- Dados da loja (Configurações > Loja), como no rodapé de um cupom. --}}
            @php
                $configLoja = app(\App\Services\Configuracoes::class);
                $linhasLoja = array_filter([
                    $configLoja->get('loja.cnpj') ? 'CNPJ '.\App\Support\Formatar::cnpj($configLoja->get('loja.cnpj')) : null,
                    $configLoja->get('loja.endereco'),
                    $configLoja->get('loja.telefone') ? 'Tel. '.\App\Support\Formatar::telefone($configLoja->get('loja.telefone')) : null,
                ]);
            @endphp
            <footer class="recibo__loja">
                <strong>{{ $configLoja->get('loja.nome') }}</strong>
                @foreach ($linhasLoja as $linha)
                    <span>{{ $linha }}</span>
                @endforeach
            </footer>
        </div>

        <footer class="perfil__rodape" data-recibo-rodape>
            <button type="button" class="perfil__excluir" data-cancelar-venda>
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                Cancelar venda
            </button>
        </footer>
    </div>
</div>
