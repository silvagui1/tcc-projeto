@php
    // chips de duração até o máximo de Configurações > Mesas e aluguéis
    $duracaoMaxima = (int) \App\Services\Configuracoes::valor('alugueis.duracao_maxima');
    $duracoes = array_filter([60 => '1h', 120 => '2h', 180 => '3h', 240 => '4h'], fn ($minutos) => $minutos <= $duracaoMaxima, ARRAY_FILTER_USE_KEY);
@endphp
{{-- Novo aluguel / editar uma data. Ordem do formulário segue a conversa no
     balcão: qual mesa → quando e por quanto tempo → se repete → quem → que
     jogo → valor (calculado sozinho pelo preço/hora da mesa, mas editável).
     As mesas são desenhadas pelo JS a partir do config da página. --}}
<div class="modal-overlay" data-modal="aluguel" hidden>
    <div class="modal-cliente modal-aluguel" role="dialog" aria-modal="true" aria-labelledby="modal-aluguel-titulo">
        <form data-form-aluguel novalidate>
            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h2 id="modal-aluguel-titulo" data-aluguel-titulo>Novo aluguel</h2>
                <span class="modal-cliente__espaco" aria-hidden="true"></span>
            </header>

            <div class="modal-cliente__corpo">
                <div class="modal-cliente__erros" data-aluguel-erros role="alert" hidden></div>

                <section class="modal-venda__secao">
                    <h3 class="perfil__titulo">Mesa</h3>
                    <div class="mesas-opcoes" role="radiogroup" aria-label="Mesa" data-mesas-opcoes></div>
                    <p class="perfil__vazio" data-sem-mesas hidden>
                        Nenhuma mesa ativa.
                        <button type="button" class="perfil__acao-link" data-abrir-mesas>Cadastrar mesas</button>
                    </p>
                    <span class="campo__erro" data-erro="mesa_id" hidden></span>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Quando</h3>
                    <div class="campo-grade">
                        <div class="campo">
                            <label for="aluguel-data">Data</label>
                            <input type="date" id="aluguel-data" name="data" required data-aluguel-data>
                            <span class="campo__erro" data-erro="data" hidden></span>
                        </div>
                        <div class="campo">
                            <label for="aluguel-inicio">Início</label>
                            <input type="time" id="aluguel-inicio" name="hora_inicio" step="900" required data-aluguel-inicio>
                            <span class="campo__erro" data-erro="hora_inicio" hidden></span>
                        </div>
                    </div>

                    <div class="campo">
                        <span class="campo__rotulo" id="aluguel-duracao-rotulo">Duração</span>
                        <div class="chips-opcoes" role="radiogroup" aria-labelledby="aluguel-duracao-rotulo">
                            {{-- a duração padrão é marcada pelo vendas.js --}}
                            @foreach ($duracoes as $minutos => $rotulo)
                                <label class="chip-opcao">
                                    <input type="radio" name="duracao" value="{{ $minutos }}" data-aluguel-duracao>
                                    <span>{{ $rotulo }}</span>
                                </label>
                            @endforeach
                            <label class="chip-opcao">
                                <input type="radio" name="duracao" value="outra" data-aluguel-duracao>
                                <span>Outra</span>
                            </label>
                        </div>
                    </div>

                    <div class="campo" data-fim-campo hidden>
                        <label for="aluguel-fim">Término</label>
                        <input type="time" id="aluguel-fim" step="900" data-aluguel-fim>
                    </div>
                    <p class="campo__dica aluguel__horario-resumo" data-aluguel-horario-resumo></p>
                    {{-- aviso de horário fora do funcionamento da loja (não impede salvar) --}}
                    <p class="aluguel__aviso" data-aluguel-horario-aviso role="status" hidden>
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <span data-aluguel-horario-aviso-texto></span>
                    </p>
                    <span class="campo__erro" data-erro="hora_fim" hidden></span>

                    <div class="campo" data-repetir-bloco>
                        <span class="campo__rotulo" id="aluguel-repetir-rotulo">Repetir</span>
                        <div class="segmentado" role="radiogroup" aria-labelledby="aluguel-repetir-rotulo">
                            <label class="segmentado__opcao">
                                <input type="radio" name="repetir" value="nao" checked data-aluguel-repetir>
                                <span>Só esta data</span>
                            </label>
                            <label class="segmentado__opcao">
                                <input type="radio" name="repetir" value="semanal" data-aluguel-repetir>
                                <span><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Toda semana</span>
                            </label>
                        </div>
                        <div class="campo aluguel__repetir-ate" data-repetir-ate-campo hidden>
                            <label for="aluguel-repetir-ate">Até</label>
                            <input type="date" id="aluguel-repetir-ate" name="repetir_ate" data-aluguel-repetir-ate>
                            <p class="campo__dica" data-repetir-dica></p>
                            <span class="campo__erro" data-erro="repetir_ate" hidden></span>
                        </div>
                    </div>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Quem vai jogar</h3>
                    @include('vendas.partials._seletor_cliente', [
                        'nome' => 'aluguel',
                        'permitirAvulso' => true,
                        'placeholder' => 'Buscar cliente ou digitar um nome',
                    ])
                    <span class="campo__erro" data-erro="responsavel" hidden></span>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Jogo</h3>
                    <div class="chips-opcoes chips-opcoes--jogo" role="radiogroup" aria-label="Tipo de jogo">
                        @foreach ($tiposJogo as $tipo)
                            <label class="chip-opcao chip-opcao--{{ $tipo['cor'] }}">
                                <input type="radio" name="tipo_jogo" value="{{ $tipo['chave'] }}" data-aluguel-tipo-jogo>
                                <span><i class="bi {{ $tipo['icone'] }}" aria-hidden="true"></i> {{ $tipo['nome'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <span class="campo__erro" data-erro="tipo_jogo" hidden></span>
                    <div class="campo aluguel__jogo">
                        <label for="aluguel-jogo">Qual jogo? <span class="campo__opcional">(opcional)</span></label>
                        <input type="text" id="aluguel-jogo" name="jogo" maxlength="120" placeholder="Ex.: D&D 5e, Magic Commander, Catan" data-aluguel-jogo>
                    </div>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Valor</h3>
                    <div class="creditos__campo-valor">
                        <span class="creditos__prefixo" aria-hidden="true">R$</span>
                        <input type="text" inputmode="decimal" autocomplete="off" placeholder="0,00" class="creditos__input-valor" aria-label="Valor do aluguel" data-aluguel-valor>
                    </div>
                    <p class="campo__dica aluguel__valor-dica">
                        <span data-aluguel-valor-dica></span>
                        <button type="button" class="perfil__acao-link" data-recalcular-valor hidden>Usar valor da mesa</button>
                    </p>
                    <span class="campo__erro" data-erro="valor" hidden></span>

                    <div class="campo campo--textarea aluguel__observacoes">
                        <label for="aluguel-observacoes">Observações <span class="campo__opcional">(opcional)</span></label>
                        <textarea id="aluguel-observacoes" rows="2" maxlength="255" placeholder="Ex.: grupo de 5 pessoas, trazem o próprio material" data-aluguel-observacoes></textarea>
                    </div>
                </section>
            </div>

            <footer class="perfil__acoes-form">
                <button type="submit" class="botao botao--principal botao--full" data-aluguel-salvar>Reservar mesa</button>
                <button type="button" class="perfil__cancelar" data-fechar-modal>Cancelar</button>
            </footer>
        </form>
    </div>
</div>
