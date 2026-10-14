<section class="config-secao" id="dados" aria-labelledby="titulo-dados">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
        <div>
            <h2 id="titulo-dados">Dados e atividades</h2>
            <p>Planilhas para abrir no Excel e o histórico do que foi feito no sistema.</p>
        </div>
    </header>

    <div class="config-colunas">
        {{-- Exportações: formulários GET comuns (o navegador baixa o arquivo) --}}
        <form class="config-card config-exportar" method="GET" action="{{ route('config.exportar.vendas') }}" data-exportar-vendas>
            <div class="config-exportar__titulo">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                <div>
                    <h3>Exportar vendas</h3>
                    <p>Uma linha por venda, com itens, pagamento e cancelamentos.</p>
                </div>
            </div>
            <div class="campo">
                <label for="exportar-periodo">Período</label>
                <select id="exportar-periodo" name="periodo" data-exportar-periodo>
                    <option value="mes">Este mês</option>
                    <option value="mes_passado">Mês passado</option>
                    <option value="30dias">Últimos 30 dias</option>
                    <option value="intervalo">Escolher datas…</option>
                    <option value="tudo">Todas as vendas</option>
                </select>
            </div>
            <div class="config-exportar__datas" data-exportar-datas hidden>
                <div class="campo">
                    <label for="exportar-de">De</label>
                    <input type="date" id="exportar-de" name="de" value="{{ today()->startOfMonth()->format('Y-m-d') }}" disabled>
                </div>
                <div class="campo">
                    <label for="exportar-ate">Até</label>
                    <input type="date" id="exportar-ate" name="ate" value="{{ today()->format('Y-m-d') }}" disabled>
                </div>
            </div>
            <button type="submit" class="botao botao--neutro botao--full">
                <i class="bi bi-download" aria-hidden="true"></i>
                Baixar planilha (CSV)
            </button>
        </form>

        <form class="config-card config-exportar" method="GET" action="{{ route('config.exportar.estoque') }}">
            <div class="config-exportar__titulo">
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                <div>
                    <h3>Exportar estoque</h3>
                    <p>Produtos e cartas avulsas, com quantidade, preço e valor total.</p>
                </div>
            </div>
            <p class="config-bloco__ajuda">A planilha mostra o estoque como está agora.</p>
            <button type="submit" class="botao botao--neutro botao--full">
                <i class="bi bi-download" aria-hidden="true"></i>
                Baixar planilha (CSV)
            </button>
        </form>
    </div>

    {{-- Registro de atividades: o que foi feito e quando (ainda sem "quem",
         porque o sistema não tem login) --}}
    <div class="config-card">
        <div class="config-bloco config-bloco--primeiro">
            <div class="config-bloco__cabecalho">
                <h3>Registro de atividades</h3>
                <label class="filtro">
                    <span class="filtro__rotulo">Área</span>
                    <select data-atividades-area>
                        <option value="">Todas</option>
                        @foreach ($areas as $valor => $area)
                            <option value="{{ $valor }}">{{ $area['rotulo'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="config-bloco__ajuda">O que foi feito no sistema e quando. Quem fez passa a aparecer quando o sistema tiver login.</p>

            <ol class="atividades" data-atividades data-url="{{ route('config.atividades') }}" data-inicial='@json($atividades)'></ol>
            <p class="perfil__vazio atividades__vazio" data-atividades-vazio hidden>Nenhuma atividade registrada ainda.</p>
            <button type="button" class="botao botao--neutro botao--full" data-atividades-mais hidden>Ver mais</button>
        </div>
    </div>
</section>
