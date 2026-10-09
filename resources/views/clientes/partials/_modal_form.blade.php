{{-- Modal de criação OU edição de cliente — um template só, parametrizado
     por $modo ('criar'|'editar'), incluído duas vezes em index.blade.php.
     Antes eram dois arquivos praticamente idênticos (risco de um campo ser
     atualizado num e esquecido no outro); a única diferença real de verdade
     entre os dois é o componente de foto (círculo vazio clicável na criação,
     vs. avatar + botão "Editar foto" na edição, já que só na edição existe
     uma foto/iniciais prévias pra mostrar) e alguns campos que só fazem
     sentido pra quem já existe (status, link de WhatsApp, histórico).

     Os campos de nome/nascimento/status, contato e observações dividem um
     único card ("Dados do cliente"), com divisórias sutis entre os grupos
     em vez de cada um ter sua própria caixa branca — mesma linguagem de
     "menos cardização" já aplicada na tela principal (ver .cartao__grupo
     no CSS). Só Foto e Créditos continuam em cards próprios, por serem
     blocos com um comportamento visual genuinamente diferente (upload de
     imagem; número + botões de ajuste). --}}
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
            @endif
            <input type="hidden" name="creditos" value="0" data-form-creditos>

            <header class="modal-cliente__topo">
                <button type="button" class="modal-cliente__fechar" data-fechar-modal aria-label="Fechar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h2 id="{{ $tituloId }}">{{ $ehEdicao ? 'Editar cliente' : 'Adicionar cliente' }}</h2>
                <span class="modal-cliente__espaco" aria-hidden="true"></span>
            </header>

            <div class="modal-cliente__corpo">
                {{-- Resumo genérico do erro — o detalhe de cada campo já
                     aparece junto do próprio input (campo__erro), então essa
                     mensagem só avisa "tem algo pra corrigir" sem repetir a
                     lista inteira de novo aqui em cima. --}}
                <div class="modal-cliente__erros" data-modal-erros hidden>
                    Corrija os campos destacados abaixo.
                </div>

                @if ($ehEdicao)
                    <section class="cartao">
                        <h3 class="cartao__titulo">Foto cliente</h3>
                        <div class="cartao__linha-foto">
                            <span class="avatar avatar--grande" data-preview-avatar>
                                <img data-preview-imagem hidden alt="Pré-visualização da foto">
                                <span data-preview-iniciais>--</span>
                            </span>
                            <button type="button" class="botao botao--principal" data-selecionar-foto>
                                Editar foto
                            </button>
                            <input
                                type="file"
                                name="foto"
                                accept="image/png,image/jpeg,image/webp"
                                hidden
                                data-input-foto
                            >
                        </div>
                        <span class="campo__erro" data-erro-foto hidden></span>
                    </section>
                @else
                    <section class="cartao cartao--foto-upload">
                        <button type="button" class="avatar-upload" data-selecionar-foto aria-label="Adicionar foto do cliente">
                            <span class="avatar-upload__preview" data-preview-avatar>
                                <img data-preview-imagem hidden alt="Pré-visualização da foto">
                                <i class="bi bi-camera-fill" data-preview-icone-vazio></i>
                            </span>
                            <span class="avatar-upload__badge" aria-hidden="true">
                                <i class="bi bi-camera-fill"></i>
                            </span>
                        </button>
                        <input
                            type="file"
                            name="foto"
                            accept="image/png,image/jpeg,image/webp"
                            hidden
                            data-input-foto
                        >
                        <p class="avatar-upload__legenda">toque para adicionar uma foto</p>
                        <span class="campo__erro" data-erro-foto hidden></span>
                    </section>
                @endif

                <section class="cartao">
                    <h3 class="cartao__titulo">Dados do cliente</h3>
                    <div class="campo">
                        <label for="cliente-{{ $modo }}-nome">Nome</label>
                        <input type="text" id="cliente-{{ $modo }}-nome" name="nome" placeholder="Nome do cliente" required data-input-nome>
                        <span class="campo__erro" data-erro-nome hidden></span>
                    </div>
                    <div class="campo">
                        <label for="cliente-{{ $modo }}-nascimento">Data de nascimento</label>
                        <input type="date" id="cliente-{{ $modo }}-nascimento" name="data_nascimento" required data-input-nascimento>
                        <span class="campo__erro" data-erro-data_nascimento hidden></span>
                    </div>
                    @if ($ehEdicao)
                        {{-- Só existe na edição: um cliente recém-criado
                             sempre começa ativo (default do banco), não faz
                             sentido pedir isso já na criação. --}}
                        <div class="campo">
                            <label for="cliente-{{ $modo }}-status">Status</label>
                            <select id="cliente-{{ $modo }}-status" name="status" data-input-status>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                            <span class="campo__erro" data-erro-status hidden></span>
                        </div>
                    @endif

                    <div class="cartao__grupo">
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
                        </div>
                        @if ($ehEdicao)
                            <a
                                href="#"
                                target="_blank"
                                rel="noopener"
                                class="botao botao--whatsapp botao--full"
                                data-abrir-whatsapp
                                hidden
                            >
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                Chamar no WhatsApp
                            </a>
                        @endif
                    </div>

                    <div class="cartao__grupo">
                        <div class="campo campo--textarea">
                            <label for="cliente-{{ $modo }}-observacoes">Observações</label>
                            <textarea id="cliente-{{ $modo }}-observacoes" name="observacoes" rows="3" placeholder="Observações" maxlength="1000" data-input-observacoes></textarea>
                            <span class="campo__erro" data-erro-observacoes hidden></span>
                            {{-- Mesmo limite de 1000 caracteres validado no
                                 back-end (UpdateClienteRequest/StoreClienteRequest)
                                 — antes só se descobria ao tentar salvar. --}}
                            <span class="campo__contador" data-contador-observacoes>0/1000</span>
                        </div>
                    </div>
                </section>

                <section class="cartao cartao--creditos">
                    <h3 class="cartao__titulo">Créditos cliente</h3>
                    <p class="creditos__rotulo">créditos atuais</p>
                    <p class="creditos__valor" data-creditos-exibicao>R$ 0,00</p>

                    <div class="creditos__ajuste">
                        <div class="creditos__campo-valor">
                            <span class="creditos__prefixo" aria-hidden="true">R$</span>
                            <input
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.01"
                                placeholder="0,00"
                                class="creditos__input-valor"
                                data-input-ajuste-valor
                                aria-label="Valor em reais para adicionar, descontar ou definir como novo saldo"
                            >
                        </div>
                        <div class="creditos__acoes">
                            <button type="button" class="botao botao--principal" data-ajustar-creditos="adicionar">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                Adicionar
                            </button>
                            <button type="button" class="botao botao--perigo" data-ajustar-creditos="descontar">
                                <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                Descontar
                            </button>
                        </div>
                        {{-- Alternativa a somar/subtrair mentalmente: digita
                             o saldo final desejado e define direto. --}}
                        <button type="button" class="botao botao--neutro botao--full" data-ajustar-creditos="definir">
                            <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                            Definir como novo saldo
                        </button>
                    </div>
                    <p class="creditos__mensagem" data-creditos-mensagem hidden></p>
                </section>
            </div>

            <footer class="modal-cliente__rodape">
                <button type="submit" class="botao botao--principal botao--full" data-botao-salvar>
                    {{ $ehEdicao ? 'Salvar alterações' : 'Adicionar cliente' }}
                </button>
            </footer>
        </form>
    </div>
</div>
