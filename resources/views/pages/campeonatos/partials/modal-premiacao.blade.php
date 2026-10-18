{{-- Pop-up de PREMIAÇÃO: um bloco por colocação, já com os créditos
     planejados no cadastro. O 1° lugar é obrigatório e sempre recebe
     créditos. Pede confirmação (data-confirmar) porque finalizar lança os
     créditos e não pode ser desfeito. --}}
@php
    $confirmacao = 'Os créditos serão lançados no saldo dos vencedores e o campeonato não poderá mais ser alterado.'
        .($campeonato['semanal'] ? ' O da semana seguinte já fica pré-cadastrado.' : '');
@endphp

<div class="champ-modal__painel" data-champ-fragmento data-estado="premiar={{ $campeonato['id'] }}">
    <header class="champ-modal__topo">
        <button type="button" class="champ-modal__voltar" aria-label="Voltar para o campeonato"
                data-champ-ir="{{ route('campeonatos.show', $campeonato['id']) }}" data-champ-estado="abrir={{ $campeonato['id'] }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </button>
        <div class="champ-modal__cabeca">
            <h2 class="champ-modal__titulo">Premiação</h2>
            <span class="champ-modal__sub">{{ $campeonato['nome'] }}</span>
        </div>
        <button type="button" class="champ-modal__fechar" data-champ-fechar aria-label="Fechar">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    <form method="POST" action="{{ route('campeonatos.premiacoes.salvar', $campeonato['id']) }}" class="champ-modal__form"
          data-champ-form data-confirmar="{{ $confirmacao }}" data-confirmar-titulo="Finalizar o campeonato?" data-confirmar-sim="Finalizar">
        @csrf

        <div class="champ-modal__corpo" data-champ-corpo>
            <p class="champ-intro">
                Escolha quem ficou em cada colocação. Os créditos entram no saldo ao finalizar — o 1° lugar é obrigatório.
            </p>

            @if (count($pendencias))
                <div class="champ-aviso champ-aviso--alerta">
                    <i class="bi bi-patch-exclamation" aria-hidden="true"></i>
                    <span>
                        Checklist do evento oficial ainda com pendências:
                        <ul>
                            @foreach ($pendencias as $pendencia)
                                <li>{{ $pendencia }}</li>
                            @endforeach
                        </ul>
                    </span>
                </div>
            @endif

            <div class="prize-grid" data-premiacao>
            @foreach ($colocacoes as $indice => $colocacao)
                <section class="prize-block {{ $indice === 0 ? 'prize-block--principal' : '' }}">
                    <h3 class="prize-block__header">
                        <i class="bi bi-trophy-fill" aria-hidden="true"></i>
                        {{ $colocacao['posicao'] }}
                        @if ($indice === 0)
                            <span class="prize-block__obrigatorio">obrigatório</span>
                        @endif
                    </h3>

                    <div class="prize-block__body">
                        <div class="prize-block__linha">
                            <div class="campo">
                                <label for="premio-{{ $indice }}-participante">Participante</label>
                                {{-- quem já está em outra colocação some daqui (campeonatos.js) --}}
                                <select id="premio-{{ $indice }}-participante" name="premiacoes[{{ $indice }}][participante]" @if ($indice === 0) required @endif data-premio-participante>
                                    <option value="">{{ $indice === 0 ? 'Escolha o campeão' : 'Ninguém nesta colocação' }}</option>
                                    @foreach ($participantes as $participante)
                                        <option value="{{ $participante['id'] }}" @if (! $participante['pago'] && $campeonato['inscricao'] > 0) data-em-aberto @endif>
                                            {{ $participante['nome'] }}{{ $participante['cadastro'] === \App\Models\Cliente::CADASTRO_PROVISORIO ? ' (provisório)' : '' }}{{ ! $participante['pago'] && $campeonato['inscricao'] > 0 ? ' · inscrição em aberto' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="campo__aviso" data-premio-aviso hidden>
                                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                    Inscrição em aberto. Dá para premiar, mas registre o pagamento no campeonato.
                                </p>
                            </div>

                            <div class="campo">
                                <label for="premio-{{ $indice }}-valor">Créditos</label>
                                <div class="prize-amount" data-stepper>
                                    <span class="prize-amount__input">
                                        <span>R$</span>
                                        <input id="premio-{{ $indice }}-valor" type="text" inputmode="decimal"
                                               name="premiacoes[{{ $indice }}][valor]" placeholder="0,00"
                                               value="{{ $colocacao['valor'] > 0 ? number_format($colocacao['valor'], 2, ',', '.') : '' }}" data-stepper-input>
                                    </span>
                                    <button type="button" class="prize-amount__step" data-stepper-step="-1" aria-label="Diminuir R$ 1,00">
                                        <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="prize-amount__step" data-stepper-step="1" aria-label="Aumentar R$ 1,00">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="premio-{{ $indice }}-descricao">Prêmio extra <span class="opcional">(opcional)</span></label>
                            <input id="premio-{{ $indice }}-descricao" type="text" maxlength="250"
                                   name="premiacoes[{{ $indice }}][descricao]" placeholder="Ex.: carta promocional"
                                   value="{{ $colocacao['descricao'] }}">
                        </div>
                    </div>
                </section>
            @endforeach
            </div>
        </div>

        <footer class="champ-modal__rodape">
            <button type="button" class="botao botao--neutro"
                    data-champ-ir="{{ route('campeonatos.show', $campeonato['id']) }}" data-champ-estado="abrir={{ $campeonato['id'] }}">Voltar</button>
            <button type="submit" class="botao botao--principal">
                <i class="bi bi-flag-fill" aria-hidden="true"></i>
                Finalizar campeonato
            </button>
        </footer>
    </form>
</div>
