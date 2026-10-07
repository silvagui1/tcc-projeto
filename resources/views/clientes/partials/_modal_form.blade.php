{{-- Modal de criação OU edição de cliente — um template só, parametrizado
     por $modo ('criar'|'editar'), incluído duas vezes em index.blade.php.

     Segue a mesma linguagem do perfil (_modal_detalhes): foto circular grande
     e centralizada no topo, seções com divisórias (sem cards) e os mesmos
     rótulos ("Informações", "Créditos"). Na edição, cada movimentação de
     saldo é salva na hora (ver clientes.js, registrarMovimento). Ao sair de
     uma edição aberta a partir do perfil, o perfil volta (ver clientes.js). --}}
@php
    $ehEdicao = $modo === 'editar';
    $tituloId = 'modal-'.$modo.'-titulo';
@endphp
<div class="modal-overlay" data-modal="{{ $modo }}" hidden>
    <div class="modal-cliente" role="dialog" aria-modal="true" aria-labelledby="{{ $tituloId }}">
        <form
            data-form-cliente
            @if ($ehEdicao) data-url-base="{{ url('/clientes') }}" @else action="{{ route('clientes.store') }}" @endif
            enctype="multipart/form-data"
            novalidate
        >
            @csrf
            @if ($ehEdicao)
                <input type="hidden" name="_method" value="PUT">
            @else
                {{-- Na criação o saldo inicial vai junto com o cadastro. Na
                     edição não existe este campo: o saldo só muda pelas
                     movimentações, que são salvas na hora. --}}
                <input type="hidden" name="creditos" value="0" data-form-creditos>
            @endif

            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h2 id="{{ $tituloId }}">{{ $ehEdicao ? 'Editar perfil' : 'Adicionar cliente' }}</h2>
                <span class="modal-cliente__espaco" aria-hidden="true"></span>
            </header>

            <div class="modal-cliente__corpo">
                {{-- Resumo genérico do erro — o detalhe de cada campo já
                     aparece junto do próprio input (campo__erro). --}}
                <div class="modal-cliente__erros" data-modal-erros hidden>
                    Corrija os campos destacados abaixo.
                </div>

                <section class="perfil__cabecalho">
                    <button
                        type="button"
                        class="perfil__avatar-botao"
                        data-selecionar-foto
                        aria-label="{{ $ehEdicao ? 'Trocar foto do cliente' : 'Adicionar foto do cliente' }}"
                    >
                        <span class="perfil__avatar" data-preview-avatar>
                            <img data-preview-imagem hidden alt="Pré-visualização da foto">
                            @if ($ehEdicao)
                                <span data-preview-iniciais>--</span>
                            @else
                                <i class="bi bi-person-fill" data-preview-icone-vazio aria-hidden="true"></i>
                            @endif
                        </span>
                        <span class="perfil__selo-camera" aria-hidden="true">
                            <i class="bi bi-camera-fill"></i>
                        </span>
                    </button>
                    <span class="perfil__legenda-foto">{{ $ehEdicao ? 'Trocar foto' : 'Adicionar foto' }}</span>
                    <input
                        type="file"
                        name="foto"
                        accept="image/png,image/jpeg,image/webp"
                        hidden
                        data-input-foto
                    >
                    <span class="campo__erro" data-erro-foto hidden></span>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Informações</h3>

                    <div class="campo">
                        <label for="cliente-{{ $modo }}-nome">Nome</label>
                        <input type="text" id="cliente-{{ $modo }}-nome" name="nome" placeholder="Nome do cliente" required data-input-nome>
                        <span class="campo__erro" data-erro-nome hidden></span>
                    </div>

                    <div class="campo">
                        <label for="cliente-{{ $modo }}-nascimento">Nascimento</label>
                        <input type="date" id="cliente-{{ $modo }}-nascimento" name="data_nascimento" required data-input-nascimento>
                        <p class="campo__dica" data-dica-idade hidden></p>
                        <span class="campo__erro" data-erro-data_nascimento hidden></span>
                    </div>

                    @if ($ehEdicao)
                        {{-- Só existe na edição: um cliente recém-criado
                             sempre começa ativo (default do banco). O selo ao
                             lado é o mesmo do perfil, para a mudança aparecer
                             antes de salvar. --}}
                        <div class="campo">
                            <label for="cliente-{{ $modo }}-status">Status</label>
                            <div class="campo__linha">
                                <select id="cliente-{{ $modo }}-status" name="status" data-input-status>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                                <span class="status-badge status-badge--inativo" data-selo-status hidden>Inativo</span>
                            </div>
                            <span class="campo__erro" data-erro-status hidden></span>
                        </div>
                    @endif

                    <div class="campo">
                        <label for="cliente-{{ $modo }}-whatsapp">WhatsApp</label>
                        <div class="campo__com-icone">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                            <input
                                type="tel"
                                id="cliente-{{ $modo }}-whatsapp"
                                name="whatsapp"
                                placeholder="(11) 91234-5678"
                                inputmode="numeric"
                                maxlength="16"
                                data-input-whatsapp
                            >
                        </div>
                        <span class="campo__erro" data-erro-whatsapp hidden></span>
                        @if ($ehEdicao)
                            <a
                                href="#"
                                target="_blank"
                                rel="noopener"
                                class="perfil__acao-link"
                                data-abrir-whatsapp
                                hidden
                            >
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                Chamar no WhatsApp
                            </a>
                        @endif
                    </div>

                    <div class="campo campo--textarea">
                        <label for="cliente-{{ $modo }}-observacoes">Observações</label>
                        <textarea id="cliente-{{ $modo }}-observacoes" name="observacoes" rows="3" placeholder="Observações" maxlength="1000" data-input-observacoes></textarea>
                        <span class="campo__erro" data-erro-observacoes hidden></span>
                        {{-- Mesmo limite de 1000 caracteres validado no back-end. --}}
                        <span class="campo__contador" data-contador-observacoes>0/1000</span>
                    </div>
                </section>

                <section class="perfil__secao">
                    <h3 class="perfil__titulo">Créditos</h3>
                    <p class="perfil__rotulo">saldo atual</p>
                    <p class="perfil__saldo" data-creditos-exibicao>R$ 0,00</p>

                    <div class="creditos__ajuste">
                        <div class="creditos__operacao" role="radiogroup" aria-label="Tipo de operação" data-operacao-grupo>
                            <label class="creditos__opcao creditos__opcao--adicionar">
                                <input type="radio" name="operacao_creditos_{{ $modo }}" value="adicionar" checked data-operacao-creditos>
                                <span><i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar</span>
                            </label>
                            <label class="creditos__opcao creditos__opcao--descontar">
                                <input type="radio" name="operacao_creditos_{{ $modo }}" value="descontar" data-operacao-creditos>
                                <span><i class="bi bi-dash-lg" aria-hidden="true"></i> Descontar</span>
                            </label>
                        </div>

                        <p class="creditos__aviso-definir" data-aviso-definir hidden>
                            Corrigindo o saldo: o valor digitado passa a ser o saldo do cliente.
                        </p>

                        <div class="campo">
                            <label for="cliente-{{ $modo }}-valor-credito" data-rotulo-valor>Quanto?</label>
                            <div class="creditos__campo-valor">
                                <span class="creditos__prefixo" aria-hidden="true">R$</span>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    placeholder="0,00"
                                    id="cliente-{{ $modo }}-valor-credito"
                                    class="creditos__input-valor"
                                    data-input-ajuste-valor
                                >
                            </div>
                            <div class="creditos__rapidos" data-valores-rapidos>
                                @foreach (\App\Services\Configuracoes::valor('clientes.valores_rapidos') as $valorRapido)
                                    <button type="button" class="creditos__rapido" data-valor-rapido="{{ $valorRapido }}">R$ {{ $valorRapido }}</button>
                                @endforeach
                            </div>
                        </div>

                        <p class="creditos__previa" data-credito-previa>Digite um valor para ver o novo saldo.</p>

                        @if ($ehEdicao)
                            <div class="campo">
                                <label for="cliente-{{ $modo }}-motivo">Motivo <span class="campo__opcional">(opcional)</span></label>
                                <input
                                    type="text"
                                    id="cliente-{{ $modo }}-motivo"
                                    maxlength="120"
                                    list="motivos-credito-{{ $modo }}"
                                    autocomplete="off"
                                    placeholder="Ex.: compra no balcão"
                                    data-input-motivo
                                >
                                <datalist id="motivos-credito-{{ $modo }}">
                                    {{-- sugestões de Configurações > Clientes e créditos --}}
                                    @foreach (\App\Services\Configuracoes::valor('clientes.motivos_credito') as $motivoSugerido)
                                        <option value="{{ $motivoSugerido }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        @endif

                        <button type="button" class="botao botao--principal botao--full" data-aplicar-creditos disabled>
                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                            <span data-texto-aplicar>Adicionar</span>
                        </button>
                        <p class="creditos__dica" data-credito-dica hidden></p>
                        <p class="creditos__mensagem" data-creditos-mensagem role="status" hidden></p>

                        <button type="button" class="perfil__acao-link" data-alternar-definir>
                            Corrigir saldo manualmente
                        </button>
                    </div>

                    @if ($ehEdicao)
                        {{-- Últimas movimentações: atualizam logo após cada
                             ajuste, sem precisar salvar o formulário. --}}
                        <h4 class="perfil__subtitulo">Últimas movimentações</h4>
                        <ul class="creditos__historico-lista" data-edicao-historico></ul>
                        <p class="perfil__vazio" data-edicao-historico-vazio hidden>
                            Nenhuma movimentação registrada ainda.
                        </p>
                    @endif
                </section>
            </div>

            <footer class="perfil__acoes-form">
                <button type="submit" class="botao botao--principal botao--full" data-botao-salvar>
                    {{ $ehEdicao ? 'Salvar alterações' : 'Adicionar cliente' }}
                </button>
                <button type="button" class="perfil__cancelar" data-cancelar-edicao>Cancelar</button>
            </footer>
        </form>
    </div>
</div>
