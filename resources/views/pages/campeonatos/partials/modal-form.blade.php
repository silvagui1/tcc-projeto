{{-- Pop-up de CRIAR / EDITAR campeonato (conteúdo trocado dentro do
     .champ-modal da lista, ver campeonatos.js). Com $campeonato = null os
     campos vêm vazios — ou com os dados de $modelo, no "Repetir". O envio é
     por fetch: erros de validação voltam em JSON e aparecem embaixo de cada
     campo. Participantes só aqui ao criar; depois eles são inscritos direto
     no pop-up do campeonato. O que é menos usado (prêmio extra, banner,
     descrição) fica recolhido em "Mais opções". --}}
@php
    $editando = $campeonato !== null;
    $modelo = $modelo ?? null;
    $base = $campeonato ?? $modelo;
    $tipoAtual = $base['tipo'] ?? 'liga';
    $jogoAtual = $base['jogo'] ?? array_key_first(\App\Models\JogoCarta::opcoes());
    $dinheiro = fn ($valor) => $valor > 0 ? number_format($valor, 2, ',', '.') : '';
    $tipos = \App\Models\Campeonato::TIPOS;
    $temExtras = ($base['premioExtra'] ?? '') !== '' || ($base['descricao'] ?? '') !== '' || ($base['imagemPreview'] ?? '') !== '';
@endphp

<div class="champ-modal__painel" data-champ-fragmento
     data-estado="{{ $editando ? 'editar='.$campeonato['id'] : ($modelo ? 'copiar='.$modelo['id'] : 'criar=1') }}">
    <header class="champ-modal__topo">
        @if ($editando)
            <button type="button" class="champ-modal__voltar" aria-label="Voltar para o campeonato"
                    data-champ-ir="{{ route('campeonatos.show', $campeonato['id']) }}" data-champ-estado="abrir={{ $campeonato['id'] }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </button>
        @endif
        <div class="champ-modal__cabeca">
            <h2 class="champ-modal__titulo">{{ $editando ? 'Editar campeonato' : ($modelo ? 'Repetir campeonato' : 'Criar campeonato') }}</h2>
            @if ($modelo)
                <span class="champ-modal__sub">Com os dados de “{{ $modelo['copiaDe'] }}”. Confira a data.</span>
            @endif
        </div>
        <button type="button" class="champ-modal__fechar" data-champ-fechar aria-label="Fechar">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    <form method="POST" enctype="multipart/form-data" novalidate class="champ-modal__form" data-champ-form data-imagem-escopo
          action="{{ $editando ? route('campeonatos.update', $campeonato['id']) : route('campeonatos.store') }}"
          data-banner-url="{{ route('campeonatos.banner', ['v' => \App\Models\Campeonato::VERSAO_BANNER]) }}">
        @csrf
        @if ($editando)
            @method('PUT')
        @endif
        @if ($modelo)
            {{-- "Repetir": sem trocar o banner, usa o mesmo do original --}}
            <input type="hidden" name="copiar_de" value="{{ $modelo['id'] }}">
        @endif

        <div class="champ-modal__corpo" data-champ-corpo>
            <div class="champ-modal__colunas">
                <div class="champ-modal__coluna">

                    {{-- 1. Tipo: define a burocracia (oficial ou liguinha) --}}
                    <fieldset class="champ-secao">
                        <legend class="champ-secao__titulo">Tipo de campeonato</legend>
                        <div class="champ-tipos" data-campo-nome="tipo">
                            @foreach ($tipos as $chave => $infoTipo)
                                <label class="champ-segmento champ-segmento--tipo">
                                    <input type="radio" name="tipo" value="{{ $chave }}" @checked($tipoAtual === $chave) data-tipo-radio
                                           data-oficial="{{ $infoTipo['oficial'] ? '1' : '' }}"
                                           data-descricao="{{ $infoTipo['resumo'] }} Ex.: {{ $infoTipo['exemplos'] }}.">
                                    <span><i class="bi {{ $infoTipo['icone'] }}" aria-hidden="true"></i>{{ $infoTipo['rotulo'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="champ-tipo-descricao" data-tipo-descricao aria-live="polite">
                            {{ $tipos[$tipoAtual]['resumo'] }} Ex.: {{ $tipos[$tipoAtual]['exemplos'] }}.
                        </p>

                        {{-- só para os oficiais --}}
                        <div class="champ-oficial" data-oficial-bloco @unless ($tipos[$tipoAtual]['oficial']) hidden @endunless>
                            <p class="champ-oficial__texto">
                                <i class="bi bi-patch-check" aria-hidden="true"></i>
                                <span>Peça a sanção com antecedência, todos precisam de ID de jogador e os resultados são enviados depois. O checklist fica no campeonato.</span>
                            </p>
                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'codigo_sancao', 'rotulo' => 'Código da sanção', 'opcional' => true,
                                'valor' => $editando ? ($campeonato['codigoSancao'] ?? '') : '',
                                'atributos' => 'maxlength="60" placeholder="Ex.: 25-10-000123"',
                            ])
                        </div>
                    </fieldset>

                    {{-- 2. Quando e o quê --}}
                    <fieldset class="champ-secao">
                        <legend class="champ-secao__titulo">Informações</legend>

                        @include('pages.campeonatos.partials.campo', [
                            'nome' => 'nome', 'rotulo' => 'Nome do campeonato',
                            'valor' => $base['nome'] ?? '',
                            'atributos' => 'required maxlength="255" placeholder="Ex.: Liguinha de quinta"',
                        ])

                        <div class="champ-form__linha">
                            <div class="campo">
                                <label for="campeonato-jogo">Jogo</label>
                                {{-- jogos cadastrados em Configurações > Estoque --}}
                                <select id="campeonato-jogo" name="jogo" required data-jogo-select>
                                    @foreach (\App\Models\JogoCarta::opcoes() as $jogo => $nomeJogo)
                                        <option value="{{ $jogo }}" @selected($jogoAtual === $jogo)>{{ $nomeJogo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'deck', 'rotulo' => 'Formato / deck',
                                'valor' => $base['deck'] ?? '',
                                'atributos' => 'required maxlength="255" list="campeonato-formatos" placeholder="Ex.: Padrão"',
                            ])
                            <datalist id="campeonato-formatos">
                                @foreach (['Padrão (Standard)', 'Expandido', 'Livre', 'Commander', 'Selado', 'Draft', 'Deck temático'] as $formato)
                                    <option value="{{ $formato }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="champ-form__linha">
                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'data', 'rotulo' => 'Data', 'tipo' => 'date',
                                'valor' => $base['dataIso'] ?? '', 'atributos' => 'required data-data-campo',
                            ])
                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'horario', 'rotulo' => 'Horário', 'tipo' => 'time',
                                'valor' => $base['horario'] ?? '', 'atributos' => 'data-horario-campo',
                            ])
                        </div>

                        {{-- recorrência: o da semana seguinte nasce pré-cadastrado ao finalizar --}}
                        <label class="champ-switch">
                            <input type="hidden" name="semanal" value="0">
                            <input type="checkbox" name="semanal" value="1" role="switch" @checked($editando && $campeonato['semanal']) data-semanal-switch
                                   data-semanal-mexido="{{ $editando ? '1' : '' }}">
                            <span class="champ-switch__trilho" aria-hidden="true"></span>
                            <span class="champ-switch__texto">
                                <strong>Repete toda semana</strong>
                                <small data-semanal-dica>Ao finalizar, o da semana seguinte já fica pré-cadastrado.</small>
                            </span>
                        </label>
                    </fieldset>
                </div>

                <div class="champ-modal__coluna">
                    {{-- 3. Dinheiro: inscrição entra no caixa, prêmio sai em créditos --}}
                    <fieldset class="champ-secao">
                        <legend class="champ-secao__titulo">Inscrição e prêmios</legend>

                        @include('pages.campeonatos.partials.campo', [
                            'nome' => 'inscricao', 'rotulo' => 'Inscrição (R$)',
                            'valor' => $base ? number_format($base['inscricao'], 2, ',', '.') : '',
                            'atributos' => 'required inputmode="decimal" placeholder="0,00 = gratuita" data-inscricao-campo',
                            'dica' => 'Paga em dinheiro, Pix ou cartão. Créditos não valem para a inscrição.',
                        ])

                        <div class="champ-premios" data-campo-nome="premio_1">
                            <span class="champ-premios__rotulo">Prêmios em créditos</span>
                            <div class="champ-premios__linha">
                                @foreach ([1, 2, 3] as $colocacao)
                                    <div class="campo champ-premio">
                                        <label for="campeonato-premio_{{ $colocacao }}">
                                            {{ $colocacao }}°
                                            @if ($colocacao === 1)
                                                <span class="obrigatorio" title="Obrigatório">*</span>
                                            @endif
                                        </label>
                                        <span class="champ-premio__input">
                                            <span aria-hidden="true">R$</span>
                                            <input id="campeonato-premio_{{ $colocacao }}" type="text" inputmode="decimal"
                                                   name="premio_{{ $colocacao }}" placeholder="{{ $colocacao === 1 ? '0,00' : '—' }}"
                                                   value="{{ $dinheiro($base['premios'][$colocacao] ?? 0) }}" data-premio-campo
                                                   @if ($colocacao === 1) required @endif>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="champ-premios__total" data-premios-total>O prêmio do 1° lugar é obrigatório e sempre em créditos.</p>
                        </div>
                    </fieldset>

                    @unless ($editando)
                        <fieldset class="champ-secao">
                            <legend class="champ-secao__titulo">Participantes <span class="champ-secao__opcional">(opcional)</span></legend>
                            @include('pages.campeonatos.partials.busca-clientes')
                        </fieldset>
                    @endunless

                    {{-- 4. Menos usados --}}
                    <details class="champ-mais" @if ($temExtras) open @endif>
                        <summary>
                            <span>Mais opções</span>
                            <small>Prêmio extra, banner e descrição</small>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </summary>

                        <div class="champ-mais__corpo">
                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'premio_extra', 'rotulo' => 'Prêmio extra do 1° lugar', 'opcional' => true,
                                'valor' => $base['premioExtra'] ?? '',
                                'atributos' => 'maxlength="250" placeholder="Ex.: carta promo + booster"',
                            ])

                            @include('pages.campeonatos.partials.banner-campo', [
                                'jogo' => $jogoAtual, 'tipo' => $tipoAtual,
                                'preview' => $base['imagemPreview'] ?? '',
                                'link' => $base['imagemLink'] ?? '',
                            ])

                            @include('pages.campeonatos.partials.campo', [
                                'nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'opcional' => true,
                                'valor' => $base['descricao'] ?? '',
                                'atributos' => 'maxlength="350" rows="3" placeholder="Ex.: check-in até 17:45, partidas de 30 minutos"',
                            ])
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <footer class="champ-modal__rodape">
            @if ($editando)
                <button type="button" class="botao botao--neutro"
                        data-champ-ir="{{ route('campeonatos.show', $campeonato['id']) }}" data-champ-estado="abrir={{ $campeonato['id'] }}">Cancelar</button>
            @else
                <button type="button" class="botao botao--neutro" data-champ-fechar>Cancelar</button>
            @endif
            <button type="submit" class="botao botao--principal">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                {{ $editando ? 'Salvar alterações' : 'Criar campeonato' }}
            </button>
        </footer>
    </form>
</div>
