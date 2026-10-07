@php
    $opcoesDuracaoPadrao = [60, 90, 120, 150, 180, 240, 300, 360];
    $opcoesDuracaoMaxima = [180, 240, 360, 480, 600, 720, 1440];
@endphp
<section class="config-secao" id="alugueis" aria-labelledby="titulo-alugueis">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-calendar-week"></i></span>
        <div>
            <h2 id="titulo-alugueis">Mesas e aluguéis</h2>
            <p>Regras da agenda de mesas e os tipos de jogo que aparecem ao reservar.</p>
        </div>
    </header>

    {{-- Atalho para o cadastro de mesas (que mora na tela de Vendas) --}}
    <div class="config-card">
        <div class="config-linha config-linha--sem-borda">
            <div class="config-linha__texto">
                <strong>Mesas</strong>
                <span>
                    @if ($mesas['total'] === 0)
                        Nenhuma mesa cadastrada ainda.
                    @else
                        {{ $mesas['total'] }} {{ $mesas['total'] === 1 ? 'mesa cadastrada' : 'mesas cadastradas' }}
                        · {{ $mesas['ativas'] }} {{ $mesas['ativas'] === 1 ? 'ativa' : 'ativas' }}
                    @endif
                </span>
            </div>
            <a href="{{ route('vendas.index', ['aba' => 'alugueis', 'mesas' => 1]) }}" class="botao botao--neutro">
                <i class="bi bi-grid-3x2-gap" aria-hidden="true"></i>
                Gerenciar mesas
            </a>
        </div>
    </div>

    <form class="config-card" data-config-form action="{{ route('config.alugueis') }}" novalidate>
        <input type="hidden" name="_method" value="PUT">

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="alugueis-duracao-padrao"><strong>Duração padrão</strong></label>
                <span>Já vem marcada ao abrir "Novo aluguel".</span>
            </div>
            <select id="alugueis-duracao-padrao" name="duracao_padrao" class="config-select">
                @foreach ($opcoesDuracaoPadrao as $minutos)
                    <option value="{{ $minutos }}" @selected($config['alugueis.duracao_padrao'] === $minutos)>{{ $textoDuracao($minutos) }}</option>
                @endforeach
            </select>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="alugueis-duracao-maxima"><strong>Duração máxima</strong></label>
                <span>Reservas mais longas são recusadas.</span>
                <span class="campo__erro" data-erro="duracao_maxima" hidden></span>
            </div>
            <select id="alugueis-duracao-maxima" name="duracao_maxima" class="config-select">
                @foreach ($opcoesDuracaoMaxima as $minutos)
                    <option value="{{ $minutos }}" @selected($config['alugueis.duracao_maxima'] === $minutos)>{{ $textoDuracao($minutos) }}</option>
                @endforeach
            </select>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="alugueis-max-semanas"><strong>Limite de semanas</strong></label>
                <span>Até quantas datas um aluguel "toda semana" pode criar.</span>
                <span class="campo__erro" data-erro="max_semanas" hidden></span>
            </div>
            <div class="config-numero">
                <input type="number" id="alugueis-max-semanas" name="max_semanas" min="1" max="52" value="{{ $config['alugueis.max_semanas'] }}">
                <span>semanas</span>
            </div>
        </div>

        <div class="config-linha">
            <div class="config-linha__texto">
                <label for="alugueis-intervalo"><strong>Intervalo entre reservas</strong></label>
                <span>Tempo livre obrigatório entre duas reservas da mesma mesa (arrumação, limpeza).</span>
            </div>
            <select id="alugueis-intervalo" name="intervalo" class="config-select">
                @foreach ($opcoesIntervalo as $minutos)
                    <option value="{{ $minutos }}" @selected($config['alugueis.intervalo'] === $minutos)>{{ $minutos ? "{$minutos} min" : 'Nenhum' }}</option>
                @endforeach
            </select>
        </div>

        <div class="config-bloco">
            <h3>Tipos de jogo</h3>
            <p class="config-bloco__ajuda">Aparecem ao reservar uma mesa e colorem o quadro de ocupação. Tipos já usados em reservas podem ser renomeados, mas não removidos.</p>

            <div class="tipos-jogo" data-tipos-jogo>
                @foreach ($config['alugueis.tipos_jogo'] as $i => $tipo)
                    @include('pages.configuracoes._tipo_jogo', ['i' => $i, 'tipo' => $tipo, 'emUso' => in_array($tipo['chave'], $tiposEmUso, true)])
                @endforeach
            </div>
            <template data-tipo-modelo>
                @include('pages.configuracoes._tipo_jogo', ['i' => '__I__', 'tipo' => ['chave' => '', 'nome' => '', 'cor' => 'verde', 'icone' => 'bi-controller'], 'emUso' => false])
            </template>
            <span class="campo__erro" data-erro="tipos" hidden></span>
            <button type="button" class="botao-texto config-adicionar" data-adicionar-tipo>
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar tipo de jogo
            </button>
        </div>

        @include('pages.configuracoes._rodape_salvar')
    </form>
</section>
