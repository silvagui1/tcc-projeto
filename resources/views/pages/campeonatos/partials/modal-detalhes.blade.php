{{-- Pop-up do CAMPEONATO. Participantes vêm primeiro (é o que a loja mexe
     no dia), com o caixa logo acima; informações, premiação planejada e o
     checklist dos oficiais ficam na coluna do lado (embaixo, no celular).
     $checkin = modo "dia do campeonato": só inscrição, pagamento e caixa,
     ocupando a tela toda. Cada ação é um form data-champ-form enviado por
     fetch; ao terminar, este conteúdo é buscado de novo (campeonatos.js). --}}
@php
    $finalizado = $campeonato['status'] === 'finalizado';
    $cobra = $campeonato['inscricao'] > 0;
    $participantes = $campeonato['participantesLista'];
    $inscritos = count($participantes);
    $provisorios = collect($participantes)->where('cadastro', \App\Models\Cliente::CADASTRO_PROVISORIO)->values();
    $checklist = $campeonato['checklist'];
    $feitos = collect($checklist)->where('feito', true)->count();
    $dinheiro = fn ($valor) => 'R$ '.number_format($valor, 2, ',', '.');
    $distribuido = collect($campeonato['vencedores'])->sum('credito');
    $urlVer = route('campeonatos.show', $campeonato['id']);
    $urlCheckin = route('campeonatos.show', ['campeonato' => $campeonato['id'], 'modo' => 'checkin']);
@endphp

<div class="champ-modal__painel champ-modal__painel--detalhe {{ $checkin ? 'champ-modal__painel--checkin' : '' }}" data-champ-fragmento
     data-estado="{{ ($checkin ? 'checkin=' : 'abrir=').$campeonato['id'] }}">

    <header class="champ-modal__topo champ-modal__topo--detalhe">
        @if ($checkin)
            <button type="button" class="champ-modal__voltar" aria-label="Voltar para os detalhes"
                    data-champ-ir="{{ $urlVer }}" data-champ-estado="abrir={{ $campeonato['id'] }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </button>
        @else
            <img class="champ-modal__miniatura {{ $campeonato['bannerSistema'] ? 'champ-banner--sistema-desenho' : '' }}"
                 src="{{ $campeonato['imagem'] }}" alt="" data-banner-reserva="{{ $campeonato['imagemPadrao'] }}">
        @endif

        <div class="champ-modal__cabeca">
            <span class="champ-modal__quando">
                @if ($checkin)
                    <span class="champ-quando champ-quando--hoje"><span class="champ-quando__ponto" aria-hidden="true"></span>Check-in</span>
                @elseif ($finalizado)
                    <span class="champ-quando">{{ $campeonato['data'] }}</span>
                @else
                    <span class="champ-quando {{ $campeonato['hoje'] ? 'champ-quando--hoje' : '' }} {{ $campeonato['atrasado'] ? 'champ-quando--atrasado' : '' }}">
                        @if ($campeonato['hoje'])
                            <span class="champ-quando__ponto" aria-hidden="true"></span>
                        @endif
                        {{ $campeonato['quando'] }}{{ $campeonato['horario'] ? ' · '.$campeonato['horario'] : '' }}
                    </span>
                @endif
                <span class="champ-tipo {{ $campeonato['oficial'] ? 'champ-tipo--oficial' : '' }}">
                    <i class="bi {{ $campeonato['tipoIcone'] }}" aria-hidden="true"></i>{{ $campeonato['tipoRotulo'] }}
                </span>
                @if ($finalizado)
                    <span class="champ-badge champ-badge--finalizado">Finalizado</span>
                @endif
            </span>
            <h2 class="champ-modal__titulo">{{ $campeonato['nome'] }}</h2>
            @if ($campeonato['recorrencia'])
                <span class="champ-modal__recorrencia"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> {{ $campeonato['recorrencia'] }}</span>
            @endif
        </div>

        <button type="button" class="champ-modal__fechar" data-champ-fechar aria-label="Fechar">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    <div class="champ-modal__corpo" data-champ-corpo>
        @if ($campeonato['atrasado'])
            <p class="champ-aviso champ-aviso--alerta">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>A data já passou. Premie para finalizar{{ $campeonato['semanal'] ? ' (o da semana seguinte é criado sozinho)' : '' }} ou apague o campeonato.</span>
            </p>
        @endif

        {{-- caixa do campeonato --}}
        <dl class="champ-numeros">
            <div>
                <dt>Inscritos</dt>
                <dd>{{ $inscritos }}</dd>
                @if ($provisorios->isNotEmpty())
                    <small>{{ $provisorios->count() }} {{ $provisorios->count() === 1 ? 'provisório' : 'provisórios' }}</small>
                @endif
            </div>
            <div>
                <dt>Pagos</dt>
                @if ($cobra)
                    <dd>{{ $campeonato['pagos'] }}<span>/{{ $inscritos }}</span></dd>
                    <small>inscrição {{ $dinheiro($campeonato['inscricao']) }}</small>
                @else
                    <dd class="champ-numeros__texto">Gratuita</dd>
                @endif
            </div>
            <div class="champ-numeros__caixa">
                <dt>Arrecadado</dt>
                <dd>{{ $dinheiro($campeonato['arrecadado']) }}</dd>
                @if ($cobra && $campeonato['aReceber'] > 0)
                    <small class="champ-numeros__falta">falta {{ $dinheiro($campeonato['aReceber']) }}</small>
                @elseif ($cobra && $inscritos)
                    <small class="champ-numeros__ok"><i class="bi bi-check-lg" aria-hidden="true"></i> tudo recebido</small>
                @endif
            </div>
            <div>
                <dt>{{ $finalizado ? 'Distribuído' : 'Prêmios' }}</dt>
                <dd>{{ $dinheiro($finalizado ? $distribuido : $campeonato['totalPremios']) }}</dd>
                <small>em créditos</small>
            </div>
        </dl>

        <div class="champ-modal__colunas champ-modal__colunas--detalhe">
            <div class="champ-modal__coluna">

                {{-- provisórios: a decisão aparece quando o campeonato acaba
                     (aí já se sabe se ganharam créditos) --}}
                @if ($finalizado && $provisorios->isNotEmpty())
                    <section class="champ-secao champ-provisorios-decisao">
                        <h3 class="champ-secao__titulo">
                            <i class="bi bi-person-exclamation" aria-hidden="true"></i>
                            {{ $provisorios->count() === 1 ? 'Vira cliente?' : 'Viram clientes?' }}
                        </h3>
                        <p class="champ-ajuda">Quem não entrar na lista de Clientes perde os créditos que não usou e continua só no histórico deste campeonato.</p>
                        @foreach ($provisorios as $provisorio)
                            <div class="champ-decisao">
                                <img class="champ-avatar" src="{{ $provisorio['avatar'] }}" alt="">
                                <span class="champ-decisao__info">
                                    <strong>{{ $provisorio['nome'] }}</strong>
                                    <span>{{ $provisorio['saldo'] > 0 ? 'Ganhou '.$dinheiro($provisorio['saldo']).' em créditos' : 'Sem créditos' }}</span>
                                </span>
                                <span class="champ-decisao__acoes">
                                    <form method="POST" action="{{ route('campeonatos.participantes.cadastro', [$campeonato['id'], $provisorio['id']]) }}" data-champ-form
                                          data-confirmar="{{ $provisorio['nome'] }} não vai para a lista de Clientes e continua só no histórico deste campeonato.{{ $provisorio['saldo'] > 0 ? ' Os '.$dinheiro($provisorio['saldo']).' em créditos não usados serão zerados.' : '' }}"
                                          data-confirmar-titulo="Não adicionar aos clientes?" data-confirmar-sim="Não adicionar">
                                        @csrf
                                        <input type="hidden" name="acao" value="descartar">
                                        <button type="submit" class="botao botao--neutro botao--pequeno">Não</button>
                                    </form>
                                    <form method="POST" action="{{ route('campeonatos.participantes.cadastro', [$campeonato['id'], $provisorio['id']]) }}" data-champ-form>
                                        @csrf
                                        <input type="hidden" name="acao" value="adicionar">
                                        <button type="submit" class="botao botao--principal botao--pequeno">
                                            <i class="bi bi-person-check" aria-hidden="true"></i> Virar cliente
                                        </button>
                                    </form>
                                </span>
                            </div>
                        @endforeach
                    </section>
                @endif

                @if ($finalizado)
                    <section class="champ-secao">
                        <h3 class="champ-secao__titulo">Vencedores</h3>
                        @include('pages.campeonatos.partials.vencedores', ['vencedores' => $campeonato['vencedores']])
                    </section>
                @endif

                <section class="champ-secao">
                    <div class="champ-secao__cabeca">
                        <h3 class="champ-secao__titulo">Participantes <span>{{ $inscritos }}</span></h3>
                        @if (! $finalizado && ! $checkin)
                            <a href="{{ $urlCheckin }}" class="champ-link-acao" data-champ-ir="{{ $urlCheckin }}" data-champ-estado="checkin={{ $campeonato['id'] }}">
                                <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Modo check-in
                            </a>
                        @endif
                    </div>

                    {{-- inscrição rápida (e já pagando, se quiser) --}}
                    @unless ($finalizado)
                        <div class="champ-inscrever" data-inscrever>
                            <div class="busca">
                                <i class="bi bi-search busca__icone" aria-hidden="true"></i>
                                <input type="search" class="busca__campo" placeholder="Inscrever: digite o nome" autocomplete="off"
                                       aria-label="Buscar cliente para inscrever" data-inscrever-busca>
                            </div>

                            @if ($cobra && count($formasPagamento))
                                <fieldset class="champ-pagar-junto" data-pagar-junto>
                                    <legend>Ao inscrever, receber em</legend>
                                    <label class="champ-segmento"><input type="radio" name="pagar_junto" value="" checked><span>Depois</span></label>
                                    @foreach ($formasPagamento as $valor => $rotulo)
                                        <label class="champ-segmento"><input type="radio" name="pagar_junto" value="{{ $valor }}"><span>{{ $rotulo }}</span></label>
                                    @endforeach
                                </fieldset>
                            @endif

                            <div class="champ-search__resultados" data-inscrever-resultados>
                                @foreach ($clientesSugeridos as $cliente)
                                    <form method="POST" action="{{ route('campeonatos.participantes.inscrever', $campeonato['id']) }}"
                                          data-champ-form data-participant-name="{{ $cliente['nome'] }}" hidden>
                                        @csrf
                                        <input type="hidden" name="cliente_id" value="{{ $cliente['id'] }}">
                                        <input type="hidden" name="forma_pagamento" value="" data-forma-junto>
                                        <button type="submit" class="champ-resultado">
                                            <img class="champ-avatar" src="{{ $cliente['avatar'] }}" alt="">
                                            <span class="champ-resultado__nome">{{ $cliente['nome'] }}</span>
                                            <span class="champ-resultado__acao"><i class="bi bi-plus-lg" aria-hidden="true"></i> Inscrever</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>

                            {{-- "fantasma": quem nunca veio à loja entra só com o nome --}}
                            <details class="champ-provisorio-form" data-provisorio-detalhes>
                                <summary class="champ-link-acao">
                                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                                    <span data-provisorio-resumo>Não é cliente? Inscrever como provisório</span>
                                </summary>
                                <form method="POST" action="{{ route('campeonatos.participantes.provisorio', $campeonato['id']) }}" data-champ-form>
                                    @csrf
                                    <input type="hidden" name="forma_pagamento" value="" data-forma-junto>
                                    <div class="champ-form__linha">
                                        @include('pages.campeonatos.partials.campo', [
                                            'nome' => 'nome', 'rotulo' => 'Nome', 'valor' => '',
                                            'atributos' => 'required maxlength="255" data-provisorio-nome',
                                        ])
                                        @include('pages.campeonatos.partials.campo', [
                                            'nome' => 'whatsapp', 'rotulo' => 'WhatsApp', 'opcional' => true, 'valor' => '',
                                            'atributos' => 'inputmode="tel" maxlength="16" placeholder="(00) 00000-0000"',
                                        ])
                                    </div>
                                    <p class="champ-ajuda">Pode ganhar créditos neste campeonato; no fim você decide se ele vira cliente.</p>
                                    <button type="submit" class="botao botao--contorno botao--pequeno">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i> Inscrever provisório
                                    </button>
                                </form>
                            </details>
                        </div>
                    @endunless

                    <ul class="champ-participantes">
                        @forelse ($participantes as $participante)
                            @php($ehProvisorio = $participante['cadastro'] === \App\Models\Cliente::CADASTRO_PROVISORIO)
                            <li class="champ-participante" data-participante>
                                <img class="champ-avatar" src="{{ $participante['avatar'] }}" alt="">
                                <span class="champ-participante__info">
                                    <strong>{{ $participante['nome'] }}</strong>
                                    @if ($ehProvisorio)
                                        <span class="champ-selo-provisorio">Provisório</span>
                                    @elseif ($participante['cadastro'] === \App\Models\Cliente::CADASTRO_AVULSO)
                                        <span class="champ-participante__nota">não virou cliente</span>
                                    @elseif ($participante['incompleto'])
                                        <a href="{{ route('clientes.index', ['abrir' => $participante['id']]) }}" class="champ-link-completar">
                                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> completar cadastro
                                        </a>
                                    @endif
                                </span>

                                @if ($cobra)
                                    @if ($participante['pago'])
                                        <span class="champ-pill champ-pill--pago" title="Para desfazer, cancele a venda em Vendas">
                                            <i class="bi bi-check-lg" aria-hidden="true"></i> Pago
                                        </span>
                                    @elseif (count($formasPagamento))
                                        <button type="button" class="champ-pill champ-pill--aberto {{ $finalizado ? 'champ-pill--discreto' : '' }}"
                                                aria-expanded="false" data-pagar-abrir title="Registrar pagamento">
                                            Em aberto <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                        </button>
                                    @else
                                        <span class="champ-pill" title="Ligue formas de pagamento em Configurações > Vendas">Em aberto</span>
                                    @endif
                                @endif

                                @if (! $finalizado && ! $participante['pago'])
                                    <form method="POST" action="{{ route('campeonatos.participantes.remover', [$campeonato['id'], $participante['id']]) }}" data-champ-form
                                          @if ($ehProvisorio) data-confirmar="{{ $participante['nome'] }} é provisório: tirando do campeonato, o registro dele é apagado." data-confirmar-sim="Tirar" @endif>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="champ-icone-botao" aria-label="Tirar {{ $participante['nome'] }}" title="Tirar do campeonato">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- receber: um toque na forma de pagamento --}}
                                @if ($cobra && ! $participante['pago'] && count($formasPagamento))
                                    <form method="POST" action="{{ route('campeonatos.participantes.pagamento', [$campeonato['id'], $participante['id']]) }}"
                                          class="champ-receber" data-champ-form hidden>
                                        @csrf
                                        <span>Receber {{ $dinheiro($campeonato['inscricao']) }} em</span>
                                        @foreach ($formasPagamento as $valor => $rotulo)
                                            <button type="submit" name="forma_pagamento" value="{{ $valor }}" class="champ-segmento-botao">{{ $rotulo }}</button>
                                        @endforeach
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="champ-vazio champ-vazio--compacto">Ninguém inscrito ainda. Busque pelo nome acima.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            @unless ($checkin)
                <div class="champ-modal__coluna champ-modal__coluna--lado">
                    <section class="champ-secao champ-cartao">
                        <h3 class="champ-secao__titulo">Informações</h3>
                        <dl class="champ-info">
                            <div><dt>Data</dt><dd>{{ $campeonato['dataCurta'] }}{{ $campeonato['horario'] ? ' · '.$campeonato['horario'] : '' }}</dd></div>
                            <div><dt>Jogo</dt><dd>{{ \App\Models\JogoCarta::nomeDe($campeonato['jogo']) }}</dd></div>
                            <div><dt>Formato</dt><dd>{{ $campeonato['deck'] }}</dd></div>
                            <div><dt>Inscrição</dt><dd>{{ $cobra ? $dinheiro($campeonato['inscricao']) : 'Gratuita' }}</dd></div>
                            @if ($campeonato['oficial'])
                                <div><dt>Estrutura</dt><dd>{{ $campeonato['tipoEstrutura'] }}</dd></div>
                                <div><dt>Código da sanção</dt><dd>{{ $campeonato['codigoSancao'] ?: 'Não informado' }}</dd></div>
                            @endif
                        </dl>
                        @if ($campeonato['descricao'] !== '')
                            <p class="champ-detail__desc">{!! nl2br(e($campeonato['descricao'])) !!}</p>
                        @endif
                    </section>

                    @if (! $finalizado && count($campeonato['premios']))
                        <section class="champ-secao champ-premiacao-plano">
                            <h3 class="champ-secao__titulo"><i class="bi bi-coin" aria-hidden="true"></i> Premiação em créditos</h3>
                            <ol class="champ-podio">
                                @foreach ($campeonato['premios'] as $colocacao => $valor)
                                    <li><span>{{ $colocacao }}°</span>{{ $dinheiro($valor) }}</li>
                                @endforeach
                            </ol>
                            @if ($campeonato['premioExtra'] !== '')
                                <p><i class="bi bi-gift" aria-hidden="true"></i> 1° também leva: {{ $campeonato['premioExtra'] }}</p>
                            @endif
                        </section>
                    @endif

                    {{-- burocracia dos oficiais: salva a cada clique --}}
                    @if (count($checklist))
                        <section class="champ-secao champ-cartao champ-checklist">
                            <h3 class="champ-secao__titulo">
                                Checklist do evento oficial
                                <span class="{{ $feitos === count($checklist) ? 'champ-checklist__completo' : '' }}">{{ $feitos }}/{{ count($checklist) }}</span>
                            </h3>
                            <div class="champ-barra" aria-hidden="true"><span style="width: {{ round($feitos / count($checklist) * 100) }}%"></span></div>
                            <form method="POST" action="{{ route('campeonatos.requisitos', $campeonato['id']) }}" data-champ-form data-champ-auto>
                                @csrf
                                @method('PUT')
                                @foreach ($checklist as $item)
                                    <label class="champ-check champ-check--tarefa">
                                        <input type="checkbox" name="requisitos[]" value="{{ $item['chave'] }}" @checked($item['feito'])>
                                        <span class="champ-check__caixa" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                        <span>{{ $item['rotulo'] }}</span>
                                    </label>
                                @endforeach
                            </form>
                        </section>
                    @endif
                </div>
            @endunless
        </div>
    </div>

    <footer class="champ-modal__rodape">
        @if ($checkin)
            <button type="button" class="botao botao--neutro"
                    data-champ-ir="{{ $urlVer }}" data-champ-estado="abrir={{ $campeonato['id'] }}">
                <i class="bi bi-layout-sidebar-inset-reverse" aria-hidden="true"></i> Detalhes
            </button>
        @else
            @include('pages.campeonatos.partials.apagar-botao', ['classe' => 'botao botao--fantasma champ-modal__apagar', 'rotulo' => 'Apagar'])
        @endif

        @if ($finalizado)
            @unless ($campeonato['semanal'])
                <button type="button" class="botao botao--neutro"
                        data-champ-ir="{{ route('campeonatos.criar', ['copiar' => $campeonato['id']]) }}" data-champ-estado="copiar={{ $campeonato['id'] }}">
                    <i class="bi bi-copy" aria-hidden="true"></i> Repetir
                </button>
            @endunless
            <button type="button" class="botao botao--principal" data-champ-fechar>Fechar</button>
        @else
            @unless ($checkin)
                <button type="button" class="botao botao--neutro"
                        data-champ-ir="{{ route('campeonatos.editar', $campeonato['id']) }}" data-champ-estado="editar={{ $campeonato['id'] }}">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Editar
                </button>
            @endunless
            <button type="button" class="botao botao--principal" @disabled(! $inscritos)
                    title="{{ $inscritos ? '' : 'Inscreva participantes antes de premiar' }}"
                    data-champ-ir="{{ route('campeonatos.premiacoes', $campeonato['id']) }}" data-champ-estado="premiar={{ $campeonato['id'] }}">
                <i class="bi bi-trophy" aria-hidden="true"></i> Premiar e finalizar
            </button>
        @endif
    </footer>
</div>
