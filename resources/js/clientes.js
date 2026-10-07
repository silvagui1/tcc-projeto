/**
 * Lógica da tela de Clientes: cards de resumo, busca, filtros, ordenação,
 * seleção/exclusão em lote (com desfazer), exportação, e os modais de
 * detalhes (somente leitura), criar/editar e confirmação.
 *
 * Criar e editar usam o mesmo template Blade (_modal_form.blade.php,
 * parametrizado por "modo"), mas continuam sendo dois <form> independentes
 * no DOM — cada um configurado aqui por configurarModal(), sem misturar
 * estado entre eles.
 *
 * Escrito em JS puro (sem framework), no mesmo estilo do restante do
 * projeto, e só é inicializado quando a página atual é a de clientes.
 */

import { ativarFocusTrap, desativarFocusTrap, formatarMoeda } from './comum';

const ICONES_MENSAGEM = {
    sucesso: 'bi-check-circle-fill',
    erro: 'bi-exclamation-circle-fill',
};

// Mesmo limite de app/Http/Requests/Store|UpdateClienteRequest.php (foto.max).
const TAMANHO_MAX_FOTO = 2 * 1024 * 1024;
const TIPOS_ACEITOS_FOTO = ['image/jpeg', 'image/png', 'image/webp'];
const LADO_CROP_FOTO = 600;

// Janela de "desfazer" antes de uma exclusão virar definitiva de verdade no
// servidor (ver agendarExclusao) — o projeto decidiu não reintroduzir soft
// delete no banco, então o "desfazer" é só client-side: atrasa o DELETE.
const JANELA_DESFAZER_MS = 6000;

/**
 * Lê a config compartilhada com o back-end (ver <script id="app-config"> em
 * resources/views/layouts/app.blade.php) — evita manter a paleta de cores
 * do avatar e o DDI do WhatsApp duplicados "de cabeça" em PHP e em JS.
 */
function lerConfigApp() {
    const script = document.getElementById('app-config');
    if (!script) return { avatarCores: [], whatsappDdi: '55' };
    try {
        return JSON.parse(script.textContent);
    } catch (erro) {
        return { avatarCores: [], whatsappDdi: '55' };
    }
}

const CONFIG_APP = lerConfigApp();
const AVATAR_CORES = CONFIG_APP.avatarCores && CONFIG_APP.avatarCores.length
    ? CONFIG_APP.avatarCores
    : ['#8bbaed', '#b47194', '#53577d', '#6c6588', '#2e3045'];

const ROTULOS_TIPO_CREDITO = { adicionar: 'Adicionado', descontar: 'Descontado', definir: 'Saldo definido' };

/**
 * Preenche a lista de movimentações de crédito (somente leitura) — usada no
 * perfil e, em modo leitura, na edição. Monta os nós com textContent: os
 * dados vêm do banco e não passam por innerHTML.
 */
function renderizarHistorico(lista, vazioEl, historico) {
    if (!lista || !vazioEl) return;
    lista.innerHTML = '';

    if (!historico || historico.length === 0) {
        vazioEl.hidden = false;
        return;
    }

    vazioEl.hidden = true;
    historico.forEach((item) => {
        const li = document.createElement('li');
        li.className = 'creditos__historico-item';

        const tipo = document.createElement('span');
        tipo.className = 'creditos__historico-tipo';
        tipo.textContent = `${ROTULOS_TIPO_CREDITO[item.tipo] || item.tipo} · ${formatarMoeda(item.valor)}`;

        const data = document.createElement('span');
        data.className = 'creditos__historico-data';
        data.textContent = `${item.data} — saldo: ${formatarMoeda(item.saldo_novo)}`;

        li.append(tipo);
        if (item.motivo) {
            const motivo = document.createElement('span');
            motivo.className = 'creditos__historico-motivo';
            motivo.textContent = item.motivo;
            li.appendChild(motivo);
        }
        li.appendChild(data);
        lista.appendChild(li);
    });
}

/** Idade em anos completos a partir de 'AAAA-MM-DD', ou null se não der. */
function calcularIdade(dataIso) {
    if (!dataIso) return null;
    const nascimento = new Date(dataIso + 'T00:00:00');
    if (isNaN(nascimento)) return null;

    const hoje = new Date();
    let idade = hoje.getFullYear() - nascimento.getFullYear();
    const aindaNaoFezAniversario = hoje.getMonth() < nascimento.getMonth()
        || (hoje.getMonth() === nascimento.getMonth() && hoje.getDate() < nascimento.getDate());
    if (aindaNaoFezAniversario) idade--;

    return idade >= 0 ? idade : null;
}

/**
 * Formata um WhatsApp enquanto o usuário digita: mantém só dígitos (até 11 —
 * DDD + número) e aplica a máscara "(11) 91234-5678" / "(11) 1234-5678"
 * conforme a quantidade já digitada.
 */
function formatarWhatsapp(valor) {
    const digitos = String(valor || '').replace(/\D/g, '').slice(0, 11);

    if (digitos.length === 0) return '';
    if (digitos.length <= 2) return '(' + digitos;

    const ddd = digitos.slice(0, 2);
    const resto = digitos.slice(2);

    if (resto.length <= 4) {
        return `(${ddd}) ${resto}`;
    }

    // 10 dígitos (fixo/antigo): 4+4. 11 dígitos (celular com 9º dígito): 5+4.
    const tamanhoPrimeiraParte = digitos.length > 10 ? 5 : 4;
    const primeiraParte = resto.slice(0, tamanhoPrimeiraParte);
    const segundaParte = resto.slice(tamanhoPrimeiraParte);

    return `(${ddd}) ${primeiraParte}${segundaParte ? '-' + segundaParte : ''}`;
}

/**
 * Recorta uma imagem no quadrado central (mesma área que o avatar circular
 * acaba mostrando via object-fit: cover) e reduz pro lado padrão — o que o
 * usuário vê no preview passa a ser exatamente o que é enviado, em vez do
 * arquivo bruto (que podia ficar descentralizado se fosse retangular).
 */
function cortarQuadradoCentral(arquivo) {
    return new Promise((resolve, reject) => {
        const imagem = new Image();
        const url = URL.createObjectURL(arquivo);

        imagem.onload = () => {
            const lado = Math.min(imagem.width, imagem.height);
            const origemX = (imagem.width - lado) / 2;
            const origemY = (imagem.height - lado) / 2;

            const canvas = document.createElement('canvas');
            canvas.width = LADO_CROP_FOTO;
            canvas.height = LADO_CROP_FOTO;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(imagem, origemX, origemY, lado, lado, 0, 0, LADO_CROP_FOTO, LADO_CROP_FOTO);
            URL.revokeObjectURL(url);

            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error('Falha ao processar imagem.'));
                    return;
                }
                resolve(new File([blob], arquivo.name.replace(/\.\w+$/, '.jpg'), { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.9);
        };

        imagem.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Não foi possível ler a imagem.'));
        };

        imagem.src = url;
    });
}

function iniciarPaginaClientes() {
    const pagina = document.querySelector('[data-clientes-page]');
    if (!pagina) {
        return;
    }

    const listaWrapper = pagina.querySelector('[data-lista-wrapper]');
    const contador = pagina.querySelector('[data-contador]');
    const buscaForm = pagina.querySelector('[data-busca-form]');
    const buscaCampo = pagina.querySelector('[data-busca-campo]');
    const buscaLimpar = pagina.querySelector('[data-busca-limpar]');
    const buscaSpinner = pagina.querySelector('[data-busca-spinner]');
    const botaoAbrirCriar = pagina.querySelector('[data-abrir-criar]');
    const botaoAlternarSelecao = pagina.querySelector('[data-alternar-selecao]');
    const iconeSelecao = pagina.querySelector('[data-icone-selecao]');
    const labelSelecao = pagina.querySelector('[data-label-selecao]');
    const barraSelecao = pagina.querySelector('[data-barra-selecao]');
    const selecaoContagem = pagina.querySelector('[data-selecao-contagem]');
    const botaoCancelarSelecao = pagina.querySelector('[data-cancelar-selecao]');
    const botaoConfirmarExclusao = pagina.querySelector('[data-confirmar-exclusao]');
    const botaoExportarSelecionados = pagina.querySelector('[data-exportar-selecionados]');
    const botaoSelecionarTodos = pagina.querySelector('[data-selecionar-todos]');
    const filtroStatusSelect = pagina.querySelector('[data-filtro-status]');
    const filtroSaldoSelect = pagina.querySelector('[data-filtro-saldo]');
    const filtroAniversariantesCheckbox = pagina.querySelector('[data-filtro-aniversariantes]');
    const botaoLimparFiltros = pagina.querySelector('[data-limpar-filtros]');
    const mensagemEl = document.querySelector('[data-mensagem]');
    const modalConfirmar = document.querySelector('[data-modal-confirmar]');

    let termoAtual = '';
    let paginaAtual = 1;
    let modoSelecao = false;
    const selecionados = new Set();
    let timeoutBusca = null;

    let filtroStatus = 'todos';
    let filtroSaldo = 'todos';
    let filtroAniversariantes = false;
    let sortCampo = 'nome';
    let sortDir = 'asc';

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    function mostrarMensagem(texto, tipo = 'sucesso', onDesfazer = null) {
        if (!mensagemEl) return;
        const icone = ICONES_MENSAGEM[tipo] || ICONES_MENSAGEM.sucesso;

        mensagemEl.innerHTML = `<i class="bi ${icone}" aria-hidden="true"></i><span></span>`;
        mensagemEl.querySelector('span').textContent = texto;

        if (onDesfazer) {
            const botaoDesfazer = document.createElement('button');
            botaoDesfazer.type = 'button';
            botaoDesfazer.className = 'mensagem-flutuante__desfazer';
            botaoDesfazer.textContent = 'Desfazer';
            botaoDesfazer.addEventListener('click', () => {
                onDesfazer();
                mensagemEl.hidden = true;
                window.clearTimeout(mensagemEl._timeout);
            });
            mensagemEl.appendChild(botaoDesfazer);
        }

        mensagemEl.hidden = false;
        mensagemEl.className = 'mensagem-flutuante mensagem-flutuante--' + tipo;
        window.clearTimeout(mensagemEl._timeout);
        mensagemEl._timeout = window.setTimeout(() => {
            mensagemEl.hidden = true;
        }, onDesfazer ? JANELA_DESFAZER_MS : 4000);
    }

    /**
     * Modal de confirmação genérico (substitui window.confirm()) — retorna
     * uma Promise<boolean> resolvida com true/false conforme a escolha.
     */
    function confirmar({ titulo, texto, textoOk = 'Confirmar' }) {
        return new Promise((resolve) => {
            if (!modalConfirmar) {
                resolve(window.confirm(texto));
                return;
            }

            const elTitulo = modalConfirmar.querySelector('[data-confirmar-titulo]');
            const elTexto = modalConfirmar.querySelector('[data-confirmar-texto]');
            const botaoOk = modalConfirmar.querySelector('[data-confirmar-ok]');
            const botaoCancelar = modalConfirmar.querySelector('[data-confirmar-cancelar]');
            const elementoAnterior = document.activeElement;

            elTitulo.textContent = titulo;
            elTexto.textContent = texto;
            botaoOk.textContent = textoOk;

            function finalizar(resultado) {
                modalConfirmar.hidden = true;
                document.body.style.overflow = '';
                desativarFocusTrap(modalConfirmar.querySelector('.modal-confirmar'));
                botaoOk.removeEventListener('click', aoConfirmar);
                botaoCancelar.removeEventListener('click', aoCancelar);
                modalConfirmar.removeEventListener('click', aoClicarFora);
                document.removeEventListener('keydown', aoEscapar);
                if (elementoAnterior && elementoAnterior.focus) elementoAnterior.focus();
                resolve(resultado);
            }

            function aoConfirmar() { finalizar(true); }
            function aoCancelar() { finalizar(false); }
            function aoClicarFora(evento) { if (evento.target === modalConfirmar) finalizar(false); }
            function aoEscapar(evento) { if (evento.key === 'Escape') finalizar(false); }

            botaoOk.addEventListener('click', aoConfirmar);
            botaoCancelar.addEventListener('click', aoCancelar);
            modalConfirmar.addEventListener('click', aoClicarFora);
            document.addEventListener('keydown', aoEscapar);

            modalConfirmar.hidden = false;
            document.body.style.overflow = 'hidden';
            ativarFocusTrap(modalConfirmar.querySelector('.modal-confirmar'));
            botaoCancelar.focus();
        });
    }

    // ---- Busca, filtros e ordenação -----------------------------------------

    function atualizarBotaoLimparBusca() {
        if (buscaLimpar) {
            buscaLimpar.hidden = buscaCampo.value.length === 0;
        }
    }

    function sincronizarCabecalhoOrdenacao() {
        const lista = listaWrapper.querySelector('[data-lista]');
        const cabecalho = listaWrapper.querySelector('.clientes-lista-cabecalho');
        if (!lista || !cabecalho) return;

        sortCampo = lista.dataset.sort || 'nome';
        sortDir = lista.dataset.dir || 'asc';

        cabecalho.querySelectorAll('[data-sort-campo]').forEach((botao) => {
            const ativo = botao.getAttribute('data-sort-campo') === sortCampo;
            botao.classList.toggle('cabecalho-ordenar--ativo', ativo);
            const icone = botao.querySelector('[data-sort-icone]');
            if (!icone) return;
            icone.className = 'bi cabecalho-ordenar__icone';
            if (ativo) {
                icone.classList.add(sortDir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
            }
        });
    }

    async function carregarLista() {
        listaWrapper.classList.add('esta-carregando');
        if (buscaSpinner) buscaSpinner.hidden = false;

        const url = new URL(window.location.origin + '/clientes/buscar');
        if (termoAtual) url.searchParams.set('nome', termoAtual);
        if (paginaAtual > 1) url.searchParams.set('page', paginaAtual);
        if (filtroStatus !== 'todos') url.searchParams.set('status', filtroStatus);
        if (filtroSaldo !== 'todos') url.searchParams.set('saldo', filtroSaldo);
        if (filtroAniversariantes) url.searchParams.set('aniversariantes', '1');
        if (sortCampo !== 'nome') url.searchParams.set('sort', sortCampo);
        if (sortDir !== 'asc') url.searchParams.set('dir', sortDir);

        try {
            const resposta = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!resposta.ok) {
                mostrarMensagem('Não foi possível carregar a lista de clientes.', 'erro');
                return;
            }

            listaWrapper.innerHTML = await resposta.text();
            sairDoModoSelecao();
            atualizarContador();
            sincronizarCabecalhoOrdenacao();
            atualizarBotaoLimparFiltros();
        } finally {
            listaWrapper.classList.remove('esta-carregando');
            if (buscaSpinner) buscaSpinner.hidden = true;
        }
    }

    function atualizarContador() {
        // Os totais vêm do servidor (data-* na <ul>), não da contagem de
        // linhas na tela — com paginação, cada página só mostra 20 por vez.
        const lista = listaWrapper.querySelector('[data-lista]');
        const total = lista ? parseInt(lista.dataset.total || '0', 10) : 0;
        const primeiro = lista ? parseInt(lista.dataset.primeiro || '0', 10) : 0;
        const ultimo = lista ? parseInt(lista.dataset.ultimo || '0', 10) : 0;

        if (!contador) return;

        if (total === 0) {
            contador.textContent = 'Nenhum cliente';
        } else if (ultimo < total || primeiro > 1) {
            contador.textContent = `Mostrando ${primeiro}–${ultimo} de ${total}`;
        } else {
            contador.textContent = total === 1 ? '1 cadastrado' : total + ' cadastrados';
        }
    }

    buscaForm.addEventListener('submit', (evento) => {
        evento.preventDefault();
        termoAtual = buscaCampo.value.trim();
        paginaAtual = 1;
        carregarLista();
    });

    buscaCampo.addEventListener('input', () => {
        atualizarBotaoLimparBusca();
        window.clearTimeout(timeoutBusca);
        timeoutBusca = window.setTimeout(() => {
            termoAtual = buscaCampo.value.trim();
            paginaAtual = 1;
            carregarLista();
        }, 300);
    });

    if (buscaLimpar) {
        buscaLimpar.addEventListener('click', () => {
            buscaCampo.value = '';
            buscaCampo.focus();
            atualizarBotaoLimparBusca();
            window.clearTimeout(timeoutBusca);
            termoAtual = '';
            paginaAtual = 1;
            carregarLista();
        });
    }

    if (filtroStatusSelect) {
        filtroStatusSelect.addEventListener('change', () => {
            filtroStatus = filtroStatusSelect.value;
            paginaAtual = 1;
            carregarLista();
        });
    }

    if (filtroSaldoSelect) {
        filtroSaldoSelect.addEventListener('change', () => {
            filtroSaldo = filtroSaldoSelect.value;
            paginaAtual = 1;
            carregarLista();
        });
    }

    if (filtroAniversariantesCheckbox) {
        filtroAniversariantesCheckbox.addEventListener('change', () => {
            filtroAniversariantes = filtroAniversariantesCheckbox.checked;
            paginaAtual = 1;
            carregarLista();
        });
    }

    // "Limpar filtros" só aparece quando algum filtro não está no padrão —
    // chamado depois de toda mudança de filtro (ver carregarLista()).
    function atualizarBotaoLimparFiltros() {
        if (!botaoLimparFiltros) return;
        const algumAtivo = filtroStatus !== 'todos' || filtroSaldo !== 'todos' || filtroAniversariantes;
        botaoLimparFiltros.hidden = !algumAtivo;
    }

    // Compartilhada entre o botão "Limpar filtros" e o item "clientes
    // cadastrados" do resumo (data-resumo-filtro="limpar") — mesma ação,
    // dois pontos de entrada.
    function limparFiltros() {
        filtroStatus = 'todos';
        filtroSaldo = 'todos';
        filtroAniversariantes = false;
        if (filtroStatusSelect) filtroStatusSelect.value = 'todos';
        if (filtroSaldoSelect) filtroSaldoSelect.value = 'todos';
        if (filtroAniversariantesCheckbox) filtroAniversariantesCheckbox.checked = false;
    }

    if (botaoLimparFiltros) {
        botaoLimparFiltros.addEventListener('click', () => {
            limparFiltros();
            paginaAtual = 1;
            carregarLista();
        });
    }

    // Resumo clicável: só os itens com um filtro de verdade pra aplicar
    // viraram <button data-resumo-filtro> (ver index.blade.php) — "novos na
    // semana" continua só leitura, não existe filtro/ordenação por data de
    // cadastro hoje.
    pagina.querySelectorAll('[data-resumo-filtro]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const acao = botao.getAttribute('data-resumo-filtro');

            if (acao === 'limpar') {
                limparFiltros();
            } else if (acao === 'saldo-com') {
                filtroSaldo = 'com';
                if (filtroSaldoSelect) filtroSaldoSelect.value = 'com';
            } else if (acao === 'aniversariantes') {
                filtroAniversariantes = true;
                if (filtroAniversariantesCheckbox) filtroAniversariantesCheckbox.checked = true;
            }

            buscaCampo.value = '';
            termoAtual = '';
            paginaAtual = 1;
            carregarLista();
        });
    });

    // ---- Paginação e ordenação (delegados no wrapper, já que o conteúdo é
    // trocado inteiro a cada carregarLista()) ---------------------------------

    listaWrapper.addEventListener('click', (evento) => {
        const linkPagina = evento.target.closest('[data-pagina-link]');
        if (!linkPagina) return;

        evento.preventDefault();

        if (
            linkPagina.classList.contains('paginacao__seta--desabilitado')
            || linkPagina.classList.contains('paginacao__numero--atual')
        ) {
            return;
        }

        const novaPagina = parseInt(linkPagina.getAttribute('data-pagina'), 10);
        if (isNaN(novaPagina) || novaPagina < 1) return;

        paginaAtual = novaPagina;
        carregarLista();
        listaWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    listaWrapper.addEventListener('click', (evento) => {
        const botaoSort = evento.target.closest('[data-sort-campo]');
        if (!botaoSort) return;

        const campo = botaoSort.getAttribute('data-sort-campo');
        if (sortCampo === campo) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortCampo = campo;
            sortDir = 'asc';
        }
        paginaAtual = 1;
        carregarLista();
    });

    // ---- Estado vazio: atalho para adicionar o primeiro cliente ------------

    listaWrapper.addEventListener('click', (evento) => {
        if (evento.target.closest('[data-abrir-criar-vazio]')) {
            pagina.dispatchEvent(new CustomEvent('cliente:novo'));
        }
    });

    // ---- Modo de seleção (selecionar/excluir/exportar clientes) ------------

    // Ids de cliente realmente na tela agora (exclui a <li> de estado vazio,
    // que não tem data-cliente-linha) — usado por "Selecionar todos".
    function idsVisiveis() {
        return Array.from(listaWrapper.querySelectorAll('[data-cliente-linha]'))
            .map((linha) => linha.getAttribute('data-id'));
    }

    function atualizarBarraSelecao() {
        const total = selecionados.size;
        selecaoContagem.textContent = total === 1 ? '1 selecionado' : total + ' selecionados';
        botaoConfirmarExclusao.disabled = total === 0;
        if (botaoExportarSelecionados) {
            botaoExportarSelecionados.disabled = total === 0;
        }
        if (botaoSelecionarTodos) {
            const visiveis = idsVisiveis();
            const todosSelecionados = visiveis.length > 0 && visiveis.every((id) => selecionados.has(id));
            botaoSelecionarTodos.textContent = todosSelecionados ? 'Desmarcar todos' : 'Selecionar todos';
        }
    }

    function entrarNoModoSelecao() {
        modoSelecao = true;
        selecionados.clear();
        pagina.classList.add('modo-selecao');
        botaoAlternarSelecao.classList.add('acao-rapida--ativa');
        barraSelecao.hidden = false;
        labelSelecao.textContent = 'Toque para selecionar';
        if (iconeSelecao) {
            iconeSelecao.classList.remove('bi-check2-square');
            iconeSelecao.classList.add('bi-x-lg');
        }
        atualizarBarraSelecao();
    }

    function sairDoModoSelecao() {
        modoSelecao = false;
        selecionados.clear();
        pagina.classList.remove('modo-selecao');
        botaoAlternarSelecao.classList.remove('acao-rapida--ativa');
        barraSelecao.hidden = true;
        labelSelecao.textContent = 'Selecionar';
        if (iconeSelecao) {
            iconeSelecao.classList.remove('bi-x-lg');
            iconeSelecao.classList.add('bi-check2-square');
        }
        listaWrapper.querySelectorAll('[data-cliente-linha]').forEach((linha) => {
            linha.classList.remove('cliente-linha--selecionado');
            const checkbox = linha.querySelector('[data-linha-checkbox-input]');
            if (checkbox) checkbox.checked = false;
        });
    }

    botaoAlternarSelecao.addEventListener('click', () => {
        if (modoSelecao) {
            sairDoModoSelecao();
        } else {
            entrarNoModoSelecao();
        }
    });

    botaoCancelarSelecao.addEventListener('click', sairDoModoSelecao);

    // Marca/desmarca todos os clientes CARREGADOS na página atual (não o
    // cadastro inteiro — paginação continua em 20 por vez).
    if (botaoSelecionarTodos) {
        botaoSelecionarTodos.addEventListener('click', () => {
            const visiveis = idsVisiveis();
            const todosSelecionados = visiveis.length > 0 && visiveis.every((id) => selecionados.has(id));

            listaWrapper.querySelectorAll('[data-cliente-linha]').forEach((linha) => {
                const id = linha.getAttribute('data-id');
                const checkbox = linha.querySelector('[data-linha-checkbox-input]');

                if (todosSelecionados) {
                    selecionados.delete(id);
                    linha.classList.remove('cliente-linha--selecionado');
                    if (checkbox) checkbox.checked = false;
                } else {
                    selecionados.add(id);
                    linha.classList.add('cliente-linha--selecionado');
                    if (checkbox) checkbox.checked = true;
                }
            });

            atualizarBarraSelecao();
        });
    }

    listaWrapper.addEventListener('click', (evento) => {
        const linha = evento.target.closest('[data-cliente-linha]');
        if (!linha) return;

        if (modoSelecao) {
            evento.preventDefault();
            const id = linha.getAttribute('data-id');
            const checkbox = linha.querySelector('[data-linha-checkbox-input]');
            const selecionado = selecionados.has(id);

            if (selecionado) {
                selecionados.delete(id);
            } else {
                selecionados.add(id);
            }

            linha.classList.toggle('cliente-linha--selecionado', !selecionado);
            if (checkbox) checkbox.checked = !selecionado;
            atualizarBarraSelecao();
            return;
        }

        const botaoDetalhes = evento.target.closest('[data-abrir-detalhes]');
        if (botaoDetalhes) {
            abrirModalDetalhes(botaoDetalhes.getAttribute('data-id'));
        }
    });

    // ---- Exclusão com janela de "desfazer" ----------------------------------
    // Sem soft delete no banco (decisão já tomada neste projeto): a linha some
    // visualmente na hora, mas o DELETE de verdade só é disparado alguns
    // segundos depois — "Desfazer" no toast cancela o timer antes disso.

    let exclusaoPendente = null;

    async function dispararExclusao(ids) {
        try {
            const resposta = ids.length === 1
                ? await fetch('/clientes/' + ids[0], {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                : await fetch('/clientes', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids }),
                });

            if (!resposta.ok) {
                const dados = await resposta.json().catch(() => ({}));
                mostrarMensagem(dados.message || 'Não foi possível excluir.', 'erro');
            }
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao excluir. A lista pode estar desatualizada.', 'erro');
        } finally {
            termoAtual = buscaCampo.value.trim();
            paginaAtual = 1;
            await carregarLista();
        }
    }

    function agendarExclusao(ids, mensagemBase) {
        // Já havia uma exclusão pendente: efetiva ela na hora, sem empilhar
        // timers nem arriscar perdê-la.
        if (exclusaoPendente) {
            window.clearTimeout(exclusaoPendente.timer);
            const idsAnteriores = exclusaoPendente.ids;
            exclusaoPendente = null;
            dispararExclusao(idsAnteriores);
        }

        ids.forEach((id) => {
            const linha = listaWrapper.querySelector(`[data-cliente-linha][data-id="${id}"]`);
            if (linha) linha.classList.add('cliente-linha--removendo');
        });

        const timer = window.setTimeout(() => {
            exclusaoPendente = null;
            dispararExclusao(ids);
        }, JANELA_DESFAZER_MS);

        exclusaoPendente = { ids, timer };

        mostrarMensagem(mensagemBase, 'sucesso', () => {
            window.clearTimeout(timer);
            exclusaoPendente = null;
            ids.forEach((id) => {
                const linha = listaWrapper.querySelector(`[data-cliente-linha][data-id="${id}"]`);
                if (linha) linha.classList.remove('cliente-linha--removendo');
            });
        });
    }

    botaoConfirmarExclusao.addEventListener('click', async () => {
        if (selecionados.size === 0) return;

        const ids = Array.from(selecionados);
        const ok = await confirmar({
            titulo: 'Excluir clientes?',
            texto: `Tem certeza que deseja excluir ${ids.length} cliente(s)? Essa ação não pode ser desfeita.`,
            textoOk: 'Excluir',
        });
        if (!ok) return;

        sairDoModoSelecao();
        agendarExclusao(ids, ids.length === 1 ? '1 cliente será removido' : `${ids.length} clientes serão removidos`);
    });

    // ---- Exportação (CSV) ----------------------------------------------------

    if (botaoExportarSelecionados) {
        botaoExportarSelecionados.addEventListener('click', () => {
            if (selecionados.size === 0) return;

            const url = new URL(window.location.origin + '/clientes/exportar');
            url.searchParams.set('ids', Array.from(selecionados).join(','));
            window.location.href = url.toString();
        });
    }

    // ---- Modal de DETALHES (somente leitura) --------------------------------

    const modalDetalhes = document.querySelector('[data-modal="detalhes"]');
    let clienteDetalhesAtual = null;
    let executarAberturaDetalhes = null;

    if (modalDetalhes) {
        const dRefs = {
            avatar: modalDetalhes.querySelector('[data-detalhes-avatar]'),
            imagem: modalDetalhes.querySelector('[data-detalhes-imagem]'),
            iniciais: modalDetalhes.querySelector('[data-detalhes-iniciais]'),
            nome: modalDetalhes.querySelector('[data-detalhes-nome]'),
            status: modalDetalhes.querySelector('[data-detalhes-status]'),
            nascimento: modalDetalhes.querySelector('[data-detalhes-nascimento]'),
            whatsapp: modalDetalhes.querySelector('[data-detalhes-whatsapp]'),
            criado: modalDetalhes.querySelector('[data-detalhes-criado]'),
            observacoes: modalDetalhes.querySelector('[data-detalhes-observacoes]'),
            saldo: modalDetalhes.querySelector('[data-detalhes-saldo]'),
            historicoLista: modalDetalhes.querySelector('[data-detalhes-historico]'),
            semHistorico: modalDetalhes.querySelector('[data-detalhes-sem-historico]'),
            whatsappBotao: modalDetalhes.querySelector('[data-detalhes-whatsapp-botao]'),
            whatsappAdicionar: modalDetalhes.querySelector('[data-detalhes-whatsapp-adicionar]'),
            editares: modalDetalhes.querySelectorAll('[data-detalhes-editar]'),
            excluir: modalDetalhes.querySelector('[data-detalhes-excluir]'),
        };

        function fecharDetalhes() {
            modalDetalhes.hidden = true;
            document.body.style.overflow = '';
            desativarFocusTrap(modalDetalhes.querySelector('.modal-cliente'));
        }

        modalDetalhes.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
            botao.addEventListener('click', fecharDetalhes);
        });
        modalDetalhes.addEventListener('click', (evento) => {
            if (evento.target === modalDetalhes) fecharDetalhes();
        });
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !modalDetalhes.hidden) fecharDetalhes();
        });

        dRefs.editares.forEach((botao) => {
            botao.addEventListener('click', () => {
                if (!clienteDetalhesAtual) return;
                fecharDetalhes();
                abrirModalEdicao(clienteDetalhesAtual.id, 'perfil');
            });
        });

        dRefs.excluir.addEventListener('click', async () => {
            if (!clienteDetalhesAtual) return;
            const nome = clienteDetalhesAtual.nome;
            const id = String(clienteDetalhesAtual.id);

            const ok = await confirmar({
                titulo: 'Excluir cliente?',
                texto: `Tem certeza que deseja excluir ${nome}? Essa ação não pode ser desfeita.`,
                textoOk: 'Excluir',
            });
            if (!ok) return;

            fecharDetalhes();
            agendarExclusao([id], `${nome} será removido`);
        });

        executarAberturaDetalhes = async function (id) {
            try {
                const resposta = await fetch('/clientes/' + id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!resposta.ok) {
                    mostrarMensagem('Não foi possível carregar os dados do cliente.', 'erro');
                    return;
                }

                const cliente = await resposta.json();
                clienteDetalhesAtual = cliente;

                if (cliente.foto_url) {
                    dRefs.imagem.src = cliente.foto_url;
                    dRefs.imagem.hidden = false;
                    dRefs.iniciais.hidden = true;
                    dRefs.avatar.style.backgroundColor = '';
                } else {
                    dRefs.imagem.hidden = true;
                    dRefs.imagem.src = '';
                    dRefs.iniciais.hidden = false;
                    dRefs.iniciais.textContent = cliente.iniciais || '--';
                    dRefs.avatar.style.backgroundColor = cliente.cor_avatar || AVATAR_CORES[0];
                }

                dRefs.nome.textContent = cliente.nome;
                dRefs.status.hidden = cliente.status !== 'inativo';

                const nascimento = new Date(cliente.data_nascimento + 'T00:00:00');
                dRefs.nascimento.textContent = `${nascimento.toLocaleDateString('pt-BR')} (${cliente.idade} anos)`;
                dRefs.whatsapp.textContent = cliente.whatsapp ? formatarWhatsapp(cliente.whatsapp) : 'Não informado';
                dRefs.whatsapp.classList.toggle('perfil__vazio', !cliente.whatsapp);
                dRefs.whatsappAdicionar.hidden = Boolean(cliente.whatsapp);
                dRefs.criado.textContent = cliente.criado_em;

                if (cliente.observacoes) {
                    dRefs.observacoes.textContent = cliente.observacoes;
                    dRefs.observacoes.hidden = false;
                } else {
                    dRefs.observacoes.hidden = true;
                }

                dRefs.saldo.textContent = formatarMoeda(cliente.creditos);

                renderizarHistorico(dRefs.historicoLista, dRefs.semHistorico, cliente.historico);

                if (cliente.whatsapp_url) {
                    dRefs.whatsappBotao.href = cliente.whatsapp_url;
                    dRefs.whatsappBotao.hidden = false;
                } else {
                    dRefs.whatsappBotao.hidden = true;
                    dRefs.whatsappBotao.removeAttribute('href');
                }

                modalDetalhes.hidden = false;
                document.body.style.overflow = 'hidden';
                ativarFocusTrap(modalDetalhes.querySelector('.modal-cliente'));
                // Foco vai para o fechar: quando o perfil reabre depois de uma
                // edição, o foco não fica perdido no corpo da página.
                modalDetalhes.querySelector('[data-fechar-modal]').focus();
            } catch (erro) {
                mostrarMensagem('Erro de conexão ao carregar cliente.', 'erro');
            }
        };
    }

    async function abrirModalDetalhes(id) {
        if (executarAberturaDetalhes) {
            await executarAberturaDetalhes(id);
        }
    }

    // ---- Modais de criar/editar --------------------------------------------
    // Wiring compartilhado entre os dois modais (fechar, foto, WhatsApp,
    // créditos, envio) — cada modal tem seu próprio formulário/overlay, então
    // isso é configurado uma vez para cada um, sem misturar estado entre eles.

    // aoFechar (opcional) roda ao sair do modal por qualquer caminho — salvar,
    // cancelar, X, Esc ou clique fora. Se devolver true, outra tela já assumiu
    // o foco (o perfil), e o foco não volta para quem abriu o modal.
    function configurarModal(raiz, { aoFechar = null } = {}) {
        const form = raiz.querySelector('[data-form-cliente]');
        const modalErros = raiz.querySelector('[data-modal-erros]');
        const inputCreditos = form.querySelector('[data-form-creditos]');
        const inputAjusteValor = form.querySelector('[data-input-ajuste-valor]');
        const creditosMensagem = form.querySelector('[data-creditos-mensagem]');
        const inputWhatsapp = form.querySelector('[data-input-whatsapp]');
        const inputStatus = form.querySelector('[data-input-status]');
        const botaoAbrirWhatsapp = raiz.querySelector('[data-abrir-whatsapp]');
        const inputFoto = form.querySelector('[data-input-foto]');
        const erroFoto = form.querySelector('[data-erro-foto]');
        const botaoSelecionarFoto = form.querySelector('[data-selecionar-foto]');
        const previewAvatar = form.querySelector('[data-preview-avatar]');
        const previewImagem = form.querySelector('[data-preview-imagem]');
        const previewIniciais = form.querySelector('[data-preview-iniciais]');
        const iconeVazio = form.querySelector('[data-preview-icone-vazio]');
        const creditosExibicao = form.querySelector('[data-creditos-exibicao]');
        const inputObservacoes = form.querySelector('[data-input-observacoes]');
        const contadorObservacoes = form.querySelector('[data-contador-observacoes]');
        const inputNascimento = form.querySelector('[data-input-nascimento]');
        const dicaIdade = form.querySelector('[data-dica-idade]');
        const seloStatus = form.querySelector('[data-selo-status]');
        const botoesCancelar = raiz.querySelectorAll('[data-cancelar-edicao]');
        const historicoLista = form.querySelector('[data-edicao-historico]');
        const historicoVazio = form.querySelector('[data-edicao-historico-vazio]');
        const operacaoGrupo = form.querySelector('[data-operacao-grupo]');
        const operacaoRadios = form.querySelectorAll('[data-operacao-creditos]');
        const avisoDefinir = form.querySelector('[data-aviso-definir]');
        const rotuloValor = form.querySelector('[data-rotulo-valor]');
        const previa = form.querySelector('[data-credito-previa]');
        const inputMotivo = form.querySelector('[data-input-motivo]');
        const botaoAplicar = form.querySelector('[data-aplicar-creditos]');
        const textoAplicar = form.querySelector('[data-texto-aplicar]');
        const dicaCreditos = form.querySelector('[data-credito-dica]');
        const botaoAlternarDefinir = form.querySelector('[data-alternar-definir]');

        let elementoAnteriorFoco = null;

        function abrir() {
            elementoAnteriorFoco = document.activeElement;
            raiz.hidden = false;
            document.body.style.overflow = 'hidden';
            ativarFocusTrap(raiz.querySelector('.modal-cliente'));
        }

        function fechar() {
            raiz.hidden = true;
            document.body.style.overflow = '';
            desativarFocusTrap(raiz.querySelector('.modal-cliente'));
            form.reset();
            modalErros.hidden = true;
            limparErrosDeCampos();
            inputFoto.value = '';
            if (erroFoto) {
                erroFoto.hidden = true;
                erroFoto.textContent = '';
            }
            if (creditosMensagem) {
                creditosMensagem.hidden = true;
                creditosMensagem.textContent = '';
            }
            atualizarContadorObservacoes();
            atualizarDicaIdade();
            atualizarSeloStatus();
            definirModoCreditos('adicionar');
            // Movimentações já foram salvas na hora; a lista só precisa refletir
            // o novo saldo quando o modal fecha.
            if (houveMovimento) {
                houveMovimento = false;
                carregarLista();
            }
            const retomouOutraTela = aoFechar ? aoFechar() : false;
            if (!retomouOutraTela && elementoAnteriorFoco && elementoAnteriorFoco.focus) elementoAnteriorFoco.focus();
        }

        raiz.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
            botao.addEventListener('click', fechar);
        });

        raiz.addEventListener('click', (evento) => {
            if (evento.target === raiz) fechar();
        });

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !raiz.hidden) fechar();
        });

        // ---- Erros por campo -------------------------------------------
        // O resumo no topo (modalErros) só avisa "tem campo pra corrigir" —
        // o texto específico de cada erro fica só junto do campo em si, sem
        // repetir a mesma mensagem duas vezes na tela.

        function limparErrosDeCampos() {
            form.querySelectorAll('.campo__erro').forEach((span) => {
                span.hidden = true;
                span.textContent = '';
            });
            form.querySelectorAll('.campo--invalido').forEach((campo) => {
                campo.classList.remove('campo--invalido');
            });
            form.querySelectorAll('[aria-invalid="true"]').forEach((input) => {
                input.removeAttribute('aria-invalid');
            });
        }

        function exibirErrosDeCampos(errosPorCampo) {
            Object.entries(errosPorCampo).forEach(([campoNome, mensagens]) => {
                const mensagem = Array.isArray(mensagens) ? mensagens[0] : mensagens;
                const spanErro = form.querySelector(`[data-erro-${campoNome}]`);
                const input = form.querySelector(`[name="${campoNome}"]`);

                if (spanErro) {
                    spanErro.textContent = mensagem;
                    spanErro.hidden = false;
                }
                if (input) {
                    input.setAttribute('aria-invalid', 'true');
                    const campoWrapper = input.closest('.campo');
                    if (campoWrapper) campoWrapper.classList.add('campo--invalido');
                }
            });
        }

        // Foto: seleção com validação client-side (tamanho/tipo) antes de
        // qualquer preview, e recorte automático no quadrado central — sem
        // isso o usuário só descobria um arquivo grande demais depois de
        // preencher o formulário inteiro e tentar salvar.
        botaoSelecionarFoto.addEventListener('click', () => inputFoto.click());

        inputFoto.addEventListener('change', async () => {
            const arquivo = inputFoto.files[0];
            if (!arquivo) return;

            if (erroFoto) {
                erroFoto.hidden = true;
                erroFoto.textContent = '';
            }

            if (!TIPOS_ACEITOS_FOTO.includes(arquivo.type)) {
                if (erroFoto) {
                    erroFoto.textContent = 'A foto deve estar em formato JPG, PNG ou WEBP.';
                    erroFoto.hidden = false;
                }
                inputFoto.value = '';
                return;
            }

            if (arquivo.size > TAMANHO_MAX_FOTO) {
                if (erroFoto) {
                    erroFoto.textContent = 'A foto deve ter no máximo 2MB.';
                    erroFoto.hidden = false;
                }
                inputFoto.value = '';
                return;
            }

            try {
                const arquivoCortado = await cortarQuadradoCentral(arquivo);
                const transferencia = new DataTransfer();
                transferencia.items.add(arquivoCortado);
                inputFoto.files = transferencia.files;

                previewImagem.src = URL.createObjectURL(arquivoCortado);
                previewImagem.hidden = false;
                if (previewIniciais) previewIniciais.hidden = true;
                if (iconeVazio) iconeVazio.hidden = true;
                botaoSelecionarFoto.classList.add('tem-foto');
            } catch (erro) {
                if (erroFoto) {
                    erroFoto.textContent = 'Não foi possível processar essa imagem. Tente outra.';
                    erroFoto.hidden = false;
                }
                inputFoto.value = '';
            }
        });

        // WhatsApp: máscara "(11) 91234-5678" enquanto o usuário digita.
        if (inputWhatsapp) {
            inputWhatsapp.addEventListener('input', () => {
                inputWhatsapp.value = formatarWhatsapp(inputWhatsapp.value);
            });
        }

        // Observações: contador de caracteres (limite de 1000, igual ao
        // back-end) — antes só se descobria que passou do limite ao tentar
        // salvar e receber o erro de volta.
        function atualizarContadorObservacoes() {
            if (!contadorObservacoes || !inputObservacoes) return;
            contadorObservacoes.textContent = `${inputObservacoes.value.length}/1000`;
        }

        if (inputObservacoes) {
            inputObservacoes.addEventListener('input', atualizarContadorObservacoes);
        }

        // Idade ao vivo ao lado da data de nascimento — o cadastro guarda só a
        // data; mostrar a idade aqui ajuda a conferir antes de salvar.
        function atualizarDicaIdade() {
            if (!dicaIdade || !inputNascimento) return;
            const idade = calcularIdade(inputNascimento.value);
            dicaIdade.hidden = idade === null;
            dicaIdade.textContent = idade === null ? '' : `${idade} ${idade === 1 ? 'ano' : 'anos'}`;
        }

        if (inputNascimento) {
            inputNascimento.addEventListener('input', atualizarDicaIdade);
        }

        // Selo de status ao lado do select — mesmo visual do perfil; só aparece
        // quando o cliente está inativo.
        function atualizarSeloStatus() {
            if (!seloStatus || !inputStatus) return;
            seloStatus.hidden = inputStatus.value !== 'inativo';
        }

        if (inputStatus) {
            inputStatus.addEventListener('change', atualizarSeloStatus);
        }

        // "Cancelar" do rodapé faz o mesmo que fechar pelo X.
        botoesCancelar.forEach((botao) => botao.addEventListener('click', fechar));

        // ---- Créditos ------------------------------------------------------
        // Edição: cada movimentação vai para o servidor na hora (POST
        // /clientes/{id}/creditos), vira um lançamento no histórico, e o saldo
        // e as movimentações da tela atualizam logo em seguida. Criação: o saldo
        // inicial é só local e entra no cadastro quando o cliente é salvo.
        // Antes de confirmar, a prévia mostra "saldo atual → novo saldo", e o
        // botão só habilita quando a operação é válida. Nada é recortado em
        // silêncio: descontar mais do que o saldo é recusado com o motivo.

        const ehEdicao = Boolean(form.dataset.urlBase);
        let saldoBase = 0;
        let modoCreditos = 'adicionar'; // 'adicionar' | 'descontar' | 'definir'
        let houveMovimento = false;

        const ROTULOS_OPERACAO = { adicionar: 'Adicionar', descontar: 'Descontar', definir: 'Definir saldo' };

        function mostrarMensagemCreditos(texto, tipo = 'erro') {
            if (!creditosMensagem) return;
            creditosMensagem.textContent = texto;
            creditosMensagem.classList.toggle('creditos__mensagem--sucesso', tipo === 'sucesso');
            creditosMensagem.classList.toggle('creditos__mensagem--erro', tipo === 'erro');
            creditosMensagem.hidden = false;
        }

        function ocultarMensagemCreditos() {
            if (!creditosMensagem) return;
            creditosMensagem.hidden = true;
            creditosMensagem.textContent = '';
        }

        // Máscara de dinheiro enquanto digita: só dígitos, lidos como centavos
        // ("3050" vira "30,50"). Evita o problema de vírgula decimal em
        // type="number", que muda de um navegador para outro.
        function formatarDigitacaoValor(texto) {
            const digitos = String(texto || '').replace(/\D/g, '').slice(0, 11);
            if (digitos === '') return '';
            const centavos = parseInt(digitos, 10);
            return (centavos / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function valorDigitado() {
            if (!inputAjusteValor) return null;
            const digitos = inputAjusteValor.value.replace(/\D/g, '');
            return digitos === '' ? null : parseInt(digitos, 10) / 100;
        }

        // Decide, sem efeito colateral, o novo saldo e se a operação é válida.
        // "erro" é o motivo de não poder confirmar (mostrado como dica).
        function calcularOperacao() {
            const valor = valorDigitado();
            const vazio = valor === null;
            const resultado = { valor, novo: saldoBase, erro: null, vazio };

            if (modoCreditos === 'definir') {
                if (!vazio && valor === saldoBase) {
                    resultado.erro = 'O saldo já é esse valor.';
                }
                resultado.novo = vazio ? saldoBase : valor;
                return resultado;
            }

            if (modoCreditos === 'descontar' && saldoBase <= 0) {
                resultado.erro = 'Este cliente não tem saldo para descontar.';
                return resultado;
            }

            if (vazio) return resultado;

            if (valor <= 0) {
                resultado.erro = 'Digite um valor maior que zero.';
                return resultado;
            }

            if (modoCreditos === 'adicionar') {
                resultado.novo = Math.round((saldoBase + valor) * 100) / 100;
            } else if (valor > saldoBase) {
                resultado.erro = `Saldo insuficiente: o máximo a descontar é ${formatarMoeda(saldoBase)}.`;
            } else {
                resultado.novo = Math.round((saldoBase - valor) * 100) / 100;
            }

            return resultado;
        }

        // Atualiza prévia, texto e estado do botão e a dica, a partir do que
        // está digitado agora. Chamada depois de cada mudança na tela.
        function atualizarCreditos() {
            const { valor, novo, erro, vazio } = calcularOperacao();

            if (previa) {
                if (modoCreditos === 'definir') {
                    previa.textContent = vazio
                        ? 'Digite o novo saldo para ver a prévia.'
                        : `Saldo: ${formatarMoeda(saldoBase)} → ${formatarMoeda(novo)}`;
                } else if (vazio) {
                    previa.textContent = 'Digite um valor para ver o novo saldo.';
                } else {
                    const mostrado = erro ? saldoBase : novo;
                    previa.textContent = `Saldo: ${formatarMoeda(saldoBase)} → ${formatarMoeda(mostrado)}`;
                }
            }

            if (textoAplicar) {
                const rotulo = ROTULOS_OPERACAO[modoCreditos];
                textoAplicar.textContent = vazio || erro
                    ? rotulo
                    : `${rotulo} ${formatarMoeda(valor)}`;
            }

            if (botaoAplicar) {
                botaoAplicar.disabled = vazio || Boolean(erro);
            }

            if (dicaCreditos) {
                let texto = erro;
                if (!texto && vazio) {
                    texto = modoCreditos === 'definir'
                        ? 'Digite o novo saldo para continuar.'
                        : 'Digite um valor para continuar.';
                }
                dicaCreditos.textContent = texto || '';
                dicaCreditos.hidden = !texto;
                dicaCreditos.classList.toggle('creditos__dica--erro', Boolean(erro));
            }
        }

        // Troca entre somar/subtrair e corrigir o saldo direto. No modo
        // "definir" o seletor some e a explicação aparece no lugar.
        function definirModoCreditos(novoModo) {
            modoCreditos = novoModo;
            if (operacaoGrupo) operacaoGrupo.hidden = novoModo === 'definir';
            if (avisoDefinir) avisoDefinir.hidden = novoModo !== 'definir';
            if (rotuloValor) rotuloValor.textContent = novoModo === 'definir' ? 'Novo saldo' : 'Quanto?';
            if (botaoAlternarDefinir) {
                botaoAlternarDefinir.textContent = novoModo === 'definir'
                    ? 'Voltar a adicionar ou descontar'
                    : 'Corrigir saldo manualmente';
            }
            if (novoModo !== 'definir') {
                operacaoRadios.forEach((radio) => { radio.checked = radio.value === novoModo; });
            }
            ocultarMensagemCreditos();
            atualizarCreditos();
        }

        // Define o saldo de partida (vindo do servidor na edição, zero na
        // criação) e zera a parte digitada.
        function definirSaldoBase(valor) {
            saldoBase = Number(valor) || 0;
            houveMovimento = false;
            if (inputCreditos) inputCreditos.value = saldoBase.toFixed(2);
            if (creditosExibicao) creditosExibicao.textContent = formatarMoeda(saldoBase);
            definirModoCreditos('adicionar');
        }

        function textoResumoMovimento(tipo, valor, novoSaldo) {
            const acao = {
                adicionar: `${formatarMoeda(valor)} adicionado.`,
                descontar: `${formatarMoeda(valor)} descontado.`,
                definir: `Saldo definido em ${formatarMoeda(valor)}.`,
            }[tipo];
            return `${acao} Saldo atual: ${formatarMoeda(novoSaldo)}.`;
        }

        function limparCamposCreditos() {
            if (inputAjusteValor) inputAjusteValor.value = '';
            if (inputMotivo) inputMotivo.value = '';
        }

        // Edição: grava a movimentação no servidor e atualiza saldo e histórico.
        async function registrarMovimento(op) {
            const tipo = modoCreditos;
            const motivo = inputMotivo ? inputMotivo.value.trim() : '';

            botaoAplicar.disabled = true;
            textoAplicar.textContent = 'Salvando...';

            try {
                const resposta = await fetch(form.action + '/creditos', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ tipo, valor: op.valor, motivo: motivo || null }),
                });

                const dados = await resposta.json().catch(() => ({}));

                if (!resposta.ok) {
                    const erroValor = dados.errors && dados.errors.valor ? dados.errors.valor[0] : null;
                    mostrarMensagemCreditos(erroValor || dados.message || 'Não foi possível registrar a movimentação.', 'erro');
                    return;
                }

                saldoBase = Number(dados.creditos) || 0;
                if (creditosExibicao) creditosExibicao.textContent = formatarMoeda(saldoBase);
                renderizarHistorico(historicoLista, historicoVazio, dados.historico);
                houveMovimento = true;
                limparCamposCreditos();
                mostrarMensagemCreditos(textoResumoMovimento(tipo, op.valor, saldoBase), 'sucesso');
            } catch (erro) {
                mostrarMensagemCreditos('Erro de conexão ao registrar a movimentação.', 'erro');
            } finally {
                atualizarCreditos();
            }
        }

        // Criação: o saldo inicial só vai para o campo oculto do formulário;
        // quem grava é o cadastro (ver ClienteController::store).
        function aplicarSaldoLocal(op) {
            saldoBase = op.novo;
            if (inputCreditos) inputCreditos.value = saldoBase.toFixed(2);
            if (creditosExibicao) creditosExibicao.textContent = formatarMoeda(saldoBase);
            limparCamposCreditos();
            mostrarMensagemCreditos(`Saldo inicial: ${formatarMoeda(saldoBase)}. Será salvo ao cadastrar o cliente.`, 'sucesso');
            atualizarCreditos();
        }

        if (inputAjusteValor) {
            inputAjusteValor.addEventListener('input', () => {
                inputAjusteValor.value = formatarDigitacaoValor(inputAjusteValor.value);
                ocultarMensagemCreditos();
                atualizarCreditos();
            });
        }

        operacaoRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                modoCreditos = radio.value;
                ocultarMensagemCreditos();
                atualizarCreditos();
            });
        });

        form.querySelectorAll('[data-valor-rapido]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const reais = parseInt(botao.getAttribute('data-valor-rapido'), 10);
                if (inputAjusteValor) inputAjusteValor.value = formatarDigitacaoValor(String(reais * 100));
                ocultarMensagemCreditos();
                atualizarCreditos();
                if (inputAjusteValor) inputAjusteValor.focus();
            });
        });

        if (botaoAlternarDefinir) {
            botaoAlternarDefinir.addEventListener('click', () => {
                definirModoCreditos(modoCreditos === 'definir' ? 'adicionar' : 'definir');
            });
        }

        if (botaoAplicar) {
            botaoAplicar.addEventListener('click', () => {
                const op = calcularOperacao();
                if (op.vazio || op.erro) return;

                if (ehEdicao) {
                    registrarMovimento(op);
                } else {
                    aplicarSaldoLocal(op);
                }
            });
        }

        // Envio do formulário
        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            limparErrosDeCampos();
            modalErros.hidden = true;

            const formData = new FormData(form);
            const botaoSalvar = form.querySelector('[data-botao-salvar]');
            const textoOriginal = botaoSalvar.textContent;
            botaoSalvar.disabled = true;
            botaoSalvar.textContent = 'Salvando...';

            try {
                const resposta = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const dados = await resposta.json();

                if (resposta.status === 422) {
                    const errosPorCampo = dados.errors || {};
                    exibirErrosDeCampos(errosPorCampo);
                    modalErros.hidden = false;
                    return;
                }

                if (!resposta.ok) {
                    mostrarMensagem(dados.message || 'Não foi possível salvar o cliente.', 'erro');
                    return;
                }

                mostrarMensagem(dados.message, 'sucesso');
                // O submit já recarrega a lista logo abaixo, então não precisa
                // do recarregamento extra que fechar() faria por movimentação.
                houveMovimento = false;
                fechar();
                termoAtual = buscaCampo.value.trim();
                await carregarLista();
            } catch (erro) {
                mostrarMensagem('Erro de conexão ao salvar cliente.', 'erro');
            } finally {
                botaoSalvar.disabled = false;
                botaoSalvar.textContent = textoOriginal;
            }
        });

        return {
            form,
            abrir,
            fechar,
            inputCreditos,
            creditosExibicao,
            inputWhatsapp,
            inputStatus,
            botaoAbrirWhatsapp,
            previewAvatar,
            previewImagem,
            previewIniciais,
            iconeVazio,
            botaoSelecionarFoto,
            definirSaldoBase,
            atualizarContadorObservacoes,
            atualizarDicaIdade,
            atualizarSeloStatus,
            historicoLista,
            historicoVazio,
        };
    }

    const modalCriar = configurarModal(document.querySelector('[data-modal="criar"]'));

    // Edição aberta a partir do perfil volta para ele ao sair (salvar, cancelar
    // ou fechar), com os dados já atualizados — o perfil é buscado de novo.
    // Edição aberta pela lista só fecha, como antes.
    let retornarAoPerfilId = null;
    const modalEditar = configurarModal(document.querySelector('[data-modal="editar"]'), {
        aoFechar() {
            const id = retornarAoPerfilId;
            retornarAoPerfilId = null;
            if (!id || !executarAberturaDetalhes) return false;
            executarAberturaDetalhes(id);
            return true;
        },
    });

    function abrirModalCriacao() {
        modalCriar.form.reset();
        modalCriar.definirSaldoBase(0);
        modalCriar.previewImagem.hidden = true;
        modalCriar.previewImagem.src = '';
        if (modalCriar.iconeVazio) modalCriar.iconeVazio.hidden = false;
        modalCriar.botaoSelecionarFoto.classList.remove('tem-foto');
        modalCriar.abrir();
        window.setTimeout(() => modalCriar.form.querySelector('[data-input-nome]').focus(), 50);
    }

    async function abrirModalEdicao(id, origem = null) {
        // Se a edição veio do perfil e não deu para abrir, o perfil volta — o
        // usuário não fica sem tela nenhuma depois de ter clicado em Editar.
        const voltarAoPerfil = () => {
            if (origem === 'perfil' && executarAberturaDetalhes) executarAberturaDetalhes(id);
        };

        try {
            const resposta = await fetch('/clientes/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!resposta.ok) {
                mostrarMensagem('Não foi possível carregar os dados do cliente.', 'erro');
                voltarAoPerfil();
                return;
            }

            const cliente = await resposta.json();
            const {
                form, previewAvatar, previewImagem, previewIniciais,
                inputWhatsapp, inputStatus, botaoAbrirWhatsapp, definirSaldoBase,
                atualizarDicaIdade, atualizarSeloStatus, historicoLista, historicoVazio,
            } = modalEditar;

            form.reset();
            form.action = form.dataset.urlBase + '/' + cliente.id;
            form.querySelector('[data-input-nome]').value = cliente.nome;
            form.querySelector('[data-input-nascimento]').value = cliente.data_nascimento;
            form.querySelector('[data-input-observacoes]').value = cliente.observacoes || '';
            modalEditar.atualizarContadorObservacoes();
            atualizarDicaIdade();
            if (inputStatus) inputStatus.value = cliente.status || 'ativo';
            atualizarSeloStatus();
            definirSaldoBase(cliente.creditos);
            modalEditar.botaoSelecionarFoto.classList.remove('tem-foto');
            renderizarHistorico(historicoLista, historicoVazio, cliente.historico);

            if (inputWhatsapp) {
                inputWhatsapp.value = formatarWhatsapp(cliente.whatsapp || '');
            }
            if (botaoAbrirWhatsapp) {
                if (cliente.whatsapp_url) {
                    botaoAbrirWhatsapp.href = cliente.whatsapp_url;
                    botaoAbrirWhatsapp.hidden = false;
                } else {
                    botaoAbrirWhatsapp.hidden = true;
                    botaoAbrirWhatsapp.removeAttribute('href');
                }
            }

            if (cliente.foto_url) {
                previewImagem.hidden = false;
                previewImagem.src = cliente.foto_url;
                previewIniciais.hidden = true;
                previewAvatar.style.backgroundColor = '';
            } else {
                previewImagem.hidden = true;
                previewImagem.src = '';
                previewIniciais.hidden = false;
                previewIniciais.textContent = cliente.iniciais || '--';
                previewAvatar.style.backgroundColor = cliente.cor_avatar || AVATAR_CORES[0];
            }

            retornarAoPerfilId = origem === 'perfil' ? String(id) : null;
            modalEditar.abrir();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao carregar cliente.', 'erro');
            voltarAoPerfil();
        }
    }

    botaoAbrirCriar.addEventListener('click', abrirModalCriacao);
    pagina.addEventListener('cliente:novo', abrirModalCriacao);
}

document.addEventListener('DOMContentLoaded', iniciarPaginaClientes);
