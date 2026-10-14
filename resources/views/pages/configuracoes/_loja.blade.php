@php
    // segunda primeiro, domingo por último (como a semana da agenda)
    $diasSemana = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 0 => 'Domingo'];
@endphp
<section class="config-secao" id="loja" aria-labelledby="titulo-loja">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-shop"></i></span>
        <div>
            <h2 id="titulo-loja">Loja</h2>
            <p>Aparecem no título do sistema e no recibo das vendas. O horário guia a agenda das mesas.</p>
        </div>
    </header>

    {{-- Logo: salva na hora (upload separado do formulário) --}}
    <div class="config-card">
        <div class="config-logo" data-config-logo data-logo-padrao="{{ asset('images/logo-navbar.svg') }}">
            <span class="config-logo__previa" data-logo-previa>
                <img src="{{ $logoUrl ?? asset('images/logo-navbar.svg') }}" alt="Logo atual" data-logo-imagem>
            </span>
            <div class="config-logo__texto">
                <strong>Logo</strong>
                <span data-logo-legenda>{{ $logoUrl ? 'Logo enviada pela loja.' : 'Usando a logo padrão do sistema.' }}</span>
                <small>PNG, JPG ou WEBP de até 1 MB. Aparece na barra superior — prefira fundo transparente.</small>
            </div>
            <div class="config-logo__acoes">
                <label class="botao botao--neutro">
                    <i class="bi bi-upload" aria-hidden="true"></i>
                    Enviar logo
                    <input type="file" accept="image/png,image/jpeg,image/webp" hidden data-logo-arquivo>
                </label>
                <button type="button" class="botao-texto" data-logo-remover @unless ($logoUrl) hidden @endunless>Usar a padrão</button>
            </div>
        </div>
    </div>

    <form class="config-card" data-config-form action="{{ route('config.loja') }}" novalidate>
        <input type="hidden" name="_method" value="PUT">

        <div class="config-grade">
            <div class="campo config-grade__largo">
                <label for="loja-nome">Nome da loja</label>
                <input type="text" id="loja-nome" name="nome" maxlength="60" required value="{{ $config['loja.nome'] }}">
                <span class="campo__erro" data-erro="nome" hidden></span>
            </div>
            <div class="campo">
                <label for="loja-cnpj">CNPJ <span class="campo__opcional">(opcional)</span></label>
                <input type="text" id="loja-cnpj" name="cnpj" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" data-mascara="cnpj"
                       value="{{ $config['loja.cnpj'] ? \App\Support\Formatar::cnpj($config['loja.cnpj']) : '' }}">
                <span class="campo__erro" data-erro="cnpj" hidden></span>
            </div>
            <div class="campo">
                <label for="loja-telefone">Telefone <span class="campo__opcional">(opcional)</span></label>
                <input type="tel" id="loja-telefone" name="telefone" inputmode="numeric" maxlength="15" placeholder="(11) 3333-4444" data-mascara="telefone"
                       value="{{ $config['loja.telefone'] ? \App\Support\Formatar::telefone($config['loja.telefone']) : '' }}">
                <span class="campo__erro" data-erro="telefone" hidden></span>
            </div>
            <div class="campo">
                <label for="loja-whatsapp">WhatsApp <span class="campo__opcional">(opcional)</span></label>
                <input type="tel" id="loja-whatsapp" name="whatsapp" inputmode="numeric" maxlength="15" placeholder="(11) 91234-5678" data-mascara="telefone"
                       value="{{ $config['loja.whatsapp'] ? \App\Support\Formatar::telefone($config['loja.whatsapp']) : '' }}">
                <span class="campo__erro" data-erro="whatsapp" hidden></span>
            </div>
            <div class="campo config-grade__largo">
                <label for="loja-endereco">Endereço <span class="campo__opcional">(opcional)</span></label>
                <input type="text" id="loja-endereco" name="endereco" maxlength="200" placeholder="Rua, número — bairro, cidade" value="{{ $config['loja.endereco'] }}">
                <span class="campo__erro" data-erro="endereco" hidden></span>
            </div>
        </div>

        <div class="config-bloco">
            <div class="config-bloco__cabecalho">
                <h3>Horário de funcionamento</h3>
                <button type="button" class="botao-texto" data-copiar-horario title="Usa o horário de segunda em todos os dias abertos">
                    <i class="bi bi-copy" aria-hidden="true"></i> Repetir o de segunda
                </button>
            </div>
            <p class="config-bloco__ajuda">Fechar antes de abrir (ex.: 18:00 às 02:00) conta como o dia seguinte.</p>

            <div class="horario" data-horario>
                @foreach ($diasSemana as $dia => $nomeDia)
                    @php $h = $config['loja.horario'][$dia]; @endphp
                    <div class="horario__dia {{ $h['aberto'] ? '' : 'horario__dia--fechado' }}" data-horario-dia="{{ $dia }}">
                        <label class="interruptor" title="Abre neste dia?">
                            <input type="hidden" name="horario[{{ $dia }}][aberto]" value="0">
                            <input type="checkbox" name="horario[{{ $dia }}][aberto]" value="1" @checked($h['aberto']) data-horario-aberto>
                            <span class="interruptor__trilho" aria-hidden="true"></span>
                            <span class="horario__nome">{{ $nomeDia }}</span>
                        </label>
                        <div class="horario__horas">
                            <input type="time" name="horario[{{ $dia }}][abre]" value="{{ $h['abre'] }}" step="900" aria-label="{{ $nomeDia }}: abre às" data-horario-abre>
                            <span aria-hidden="true">às</span>
                            <input type="time" name="horario[{{ $dia }}][fecha]" value="{{ $h['fecha'] }}" step="900" aria-label="{{ $nomeDia }}: fecha às" data-horario-fecha>
                        </div>
                        <span class="horario__fechado">Fechado</span>
                        <span class="campo__erro horario__erro" data-erro="horario.{{ $dia }}.abre" hidden></span>
                    </div>
                @endforeach
            </div>
        </div>

        @include('pages.configuracoes._rodape_salvar')
    </form>
</section>
