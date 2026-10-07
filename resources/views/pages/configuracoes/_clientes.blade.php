<section class="config-secao" id="clientes" aria-labelledby="titulo-clientes">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-people"></i></span>
        <div>
            <h2 id="titulo-clientes">Clientes e créditos</h2>
            <p>Atalhos do ajuste de créditos e o código do país do WhatsApp.</p>
        </div>
    </header>

    <form class="config-card" data-config-form action="{{ route('config.clientes') }}" novalidate>
        <input type="hidden" name="_method" value="PUT">

        <div class="config-bloco config-bloco--primeiro">
            <h3>Valores rápidos de crédito</h3>
            <p class="config-bloco__ajuda">Botões que preenchem o valor ao ajustar créditos de um cliente. Até 6, em reais inteiros.</p>
            @include('pages.configuracoes._lista_chips', [
                'nome' => 'valores_rapidos', 'itens' => $config['clientes.valores_rapidos'],
                'placeholder' => 'Novo valor (ex.: 30)', 'rotulo' => 'Valores rápidos de crédito',
                'numerico' => true, 'prefixo' => 'R$ ',
            ])
        </div>

        <div class="config-bloco">
            <h3>Motivos sugeridos</h3>
            <p class="config-bloco__ajuda">Aparecem como sugestão no campo "Motivo" ao movimentar créditos. O campo continua livre.</p>
            @include('pages.configuracoes._lista_chips', [
                'nome' => 'motivos', 'itens' => $config['clientes.motivos_credito'],
                'placeholder' => 'Novo motivo (ex.: Prêmio de campeonato)', 'rotulo' => 'Motivos sugeridos',
            ])
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="clientes-ddi"><strong>DDI do WhatsApp</strong></label>
                <span>Código do país usado no botão "Chamar no WhatsApp". 55 = Brasil.</span>
                <span class="campo__erro" data-erro="whatsapp_ddi" hidden></span>
            </div>
            <div class="config-numero config-numero--prefixo">
                <span>+</span>
                <input type="text" id="clientes-ddi" name="whatsapp_ddi" inputmode="numeric" maxlength="4" value="{{ $config['clientes.whatsapp_ddi'] }}">
            </div>
        </div>

        @include('pages.configuracoes._rodape_salvar')
    </form>
</section>
