<section class="config-secao" id="vendas" aria-labelledby="titulo-vendas">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <div>
            <h2 id="titulo-vendas">Vendas</h2>
            <p>Como a loja recebe e o que é pedido ao cancelar uma venda.</p>
        </div>
    </header>

    <form class="config-card" data-config-form action="{{ route('config.vendas') }}" novalidate>
        <input type="hidden" name="_method" value="PUT">

        <div class="config-bloco config-bloco--primeiro">
            <h3>Formas de pagamento aceitas</h3>
            <p class="config-bloco__ajuda">As desligadas somem da nova venda; vendas antigas não mudam.</p>
            <div class="config-opcoes">
                @foreach ($formasPagamento as $valor => $forma)
                    <label class="config-opcao">
                        <input type="checkbox" name="formas_pagamento[]" value="{{ $valor }}" @checked(in_array($valor, $config['vendas.formas_pagamento'], true))>
                        <span>
                            <i class="bi {{ $forma['icone'] }}" aria-hidden="true"></i>
                            {{ $forma['rotulo'] }}
                            <i class="bi bi-check-circle-fill config-opcao__marca" aria-hidden="true"></i>
                        </span>
                    </label>
                @endforeach
            </div>
            <span class="campo__erro" data-erro="formas_pagamento" hidden></span>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <strong>Pagar com créditos do cliente</strong>
                <span>Mostra "Usar créditos do cliente" na nova venda quando o cliente tem saldo.</span>
            </div>
            <label class="interruptor">
                <input type="hidden" name="permitir_creditos" value="0">
                <input type="checkbox" name="permitir_creditos" value="1" @checked($config['vendas.permitir_creditos']) aria-label="Pagar com créditos do cliente">
                <span class="interruptor__trilho" aria-hidden="true"></span>
            </label>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <strong>Exigir motivo ao cancelar uma venda</strong>
                <span>O motivo fica no recibo e no registro de atividades.</span>
            </div>
            <label class="interruptor">
                <input type="hidden" name="exigir_motivo_cancelamento" value="0">
                <input type="checkbox" name="exigir_motivo_cancelamento" value="1" @checked($config['vendas.exigir_motivo_cancelamento']) aria-label="Exigir motivo ao cancelar uma venda">
                <span class="interruptor__trilho" aria-hidden="true"></span>
            </label>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="vendas-por-pagina"><strong>Vendas por página</strong></label>
                <span>Quantas vendas a lista mostra antes de paginar.</span>
            </div>
            <select id="vendas-por-pagina" name="por_pagina" class="config-select">
                @foreach ($opcoesPorPagina as $opcao)
                    <option value="{{ $opcao }}" @selected($config['vendas.por_pagina'] === $opcao)>{{ $opcao }}</option>
                @endforeach
            </select>
        </div>

        @include('pages.configuracoes._rodape_salvar')
    </form>
</section>
