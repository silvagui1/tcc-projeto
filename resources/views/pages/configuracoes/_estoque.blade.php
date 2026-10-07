<section class="config-secao" id="estoque" aria-labelledby="titulo-estoque">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
        <div>
            <h2 id="titulo-estoque">Estoque</h2>
            <p>Categorias de produto, jogos de carta e o aviso de estoque baixo.</p>
        </div>
    </header>

    {{-- Categorias e jogos de carta: cadastros salvos na hora (ver configuracoes.js) --}}
    <div class="config-colunas">
        <div class="config-card">
            <div class="config-bloco config-bloco--primeiro">
                <h3>Categorias de produto</h3>
                <p class="config-bloco__ajuda">Só dá para excluir uma categoria sem produtos.</p>
                <div class="config-cadastro" data-cadastro
                     data-url="{{ url('/config/categorias') }}"
                     data-itens='@json($categorias)'
                     data-singular="produto" data-plural="produtos"
                     data-placeholder="Nova categoria (ex.: Dados)">
                </div>
            </div>
        </div>

        <div class="config-card">
            <div class="config-bloco config-bloco--primeiro">
                <h3>Jogos de carta</h3>
                <p class="config-bloco__ajuda">Viram abas no estoque de cartas e filtros em campeonatos.</p>
                <div class="config-cadastro" data-cadastro
                     data-url="{{ url('/config/jogos-carta') }}"
                     data-itens='@json($jogosCarta)'
                     data-singular="carta" data-plural="cartas"
                     data-placeholder="Novo jogo (ex.: Yu-Gi-Oh!)">
                </div>
            </div>
        </div>
    </div>

    <form class="config-card" data-config-form action="{{ route('config.estoque') }}" novalidate>
        <input type="hidden" name="_method" value="PUT">

        <div class="config-linha">
            <div class="config-linha__texto">
                <strong>Avisar quando o estoque estiver baixo</strong>
                <span>Mostra um alerta no Estoque e na página inicial com os produtos que estão acabando.</span>
            </div>
            <label class="interruptor">
                <input type="hidden" name="alerta_ativo" value="0">
                <input type="checkbox" name="alerta_ativo" value="1" @checked($config['estoque.alerta_ativo']) aria-label="Avisar quando o estoque estiver baixo" data-liga="#estoque-alerta-minimo">
                <span class="interruptor__trilho" aria-hidden="true"></span>
            </label>
        </div>

        <div class="config-linha" id="estoque-alerta-minimo">
            <div class="config-linha__texto">
                <label for="estoque-minimo"><strong>Avisar a partir de</strong></label>
                <span>O produto entra no alerta quando tiver essa quantidade ou menos.</span>
                <span class="campo__erro" data-erro="alerta_minimo" hidden></span>
            </div>
            <div class="config-numero">
                <input type="number" id="estoque-minimo" name="alerta_minimo" min="0" max="9999" value="{{ $config['estoque.alerta_minimo'] }}">
                <span>unidades</span>
            </div>
        </div>

        <div class="config-bloco">
            <h3>Estados das cartas</h3>
            <p class="config-bloco__ajuda">Opções do campo "Estado" ao cadastrar uma carta. O primeiro já vem marcado.</p>
            @include('pages.configuracoes._lista_chips', [
                'nome' => 'estados', 'itens' => $config['estoque.estados_carta'],
                'placeholder' => 'Novo estado (ex.: Gradada)', 'rotulo' => 'Estados das cartas',
            ])
        </div>

        <div class="config-bloco">
            <h3>Idiomas das cartas</h3>
            <p class="config-bloco__ajuda">Opções do campo "Idioma". O primeiro já vem marcado.</p>
            @include('pages.configuracoes._lista_chips', [
                'nome' => 'idiomas', 'itens' => $config['estoque.idiomas_carta'],
                'placeholder' => 'Novo idioma (ex.: Coreano)', 'rotulo' => 'Idiomas das cartas',
            ])
        </div>

        @include('pages.configuracoes._rodape_salvar')
    </form>
</section>
