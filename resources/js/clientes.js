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

function formatarMoeda(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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

/**
 * Focus trap simples: mantém o Tab/Shift+Tab preso dentro do container
 * enquanto ele estiver ativo, para o teclado não escapar pro conteúdo atrás
 * do overlay do modal.
 */
function ativarFocusTrap(container) {
    if (!container) return;

    function focaveis() {
        return Array.from(
            container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')
        ).filter((el) => el.offsetParent !== null);
    }

    function aoTeclar(evento) {
        if (evento.key !== 'Tab') return;
        const itens = focaveis();
        if (itens.length === 0) return;

        const primeiro = itens[0];
        const ultimo = itens[itens.length - 1];

        if (evento.shiftKey && document.activeElement === primeiro) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && document.activeElement === ultimo) {
            evento.preventDefault();
            primeiro.focus();
        }
    }

    container.addEventListener('keydown', aoTeclar);
    container._focusTrapHandler = aoTeclar;
}

function desativarFocusTrap(container) {
    if (container && container._focusTrapHandler) {
        container.removeEventListener('keydown', container._focusTrapHandler);
        container._focusTrapHandler = null;
    }
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
    const filtroStatusSelect = pagina.querySelector('[data-filtro-status]');
    const filtroSaldoSelect = pagina.querySelector('[data-filtro-saldo]');
    const filtroAniversariantesCheckbox = pagina.querySelector('[data-filtro-aniversariantes]');
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

    function atualizarBarraSelecao() {
        const total = selecionados.size;
        selecaoContagem.textContent = total === 1 ? '1 selecionado' : total + ' selecionados';
        botaoConfirmarExclusao.disabled = total === 0;
        if (botaoExportarSelecionados) {
            botaoExportarSelecionados.disabled = total === 0;
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
            editar: modalDetalhes.querySelector('[data-detalhes-editar]'),
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

        dRefs.editar.addEventListener('click', () => {
            if (!clienteDetalhesAtual) return;
            fecharDetalhes();
            abrirModalEdicao(clienteDetalhesAtual.id);
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
                dRefs.criado.textContent = cliente.criado_em;

                if (cliente.observacoes) {
                    dRefs.observacoes.textContent = cliente.observacoes;
                    dRefs.observacoes.hidden = false;
                } else {
                    dRefs.observacoes.hidden = true;
                }

                dRefs.saldo.textContent = formatarMoeda(cliente.creditos);

                const rotulosTipo = { adicionar: 'Adicionado', descontar: 'Descontado', definir: 'Saldo definido' };
                dRefs.historicoLista.innerHTML = '';
                if (cliente.historico && cliente.historico.length > 0) {
                    dRefs.semHistorico.hidden = true;
                    cliente.historico.forEach((item) => {
                        const li = document.createElement('li');
                        li.className = 'creditos__historico-item';
                        const rotulo = rotulosTipo[item.tipo] || item.tipo;
                        li.innerHTML = `
                            <span class="creditos__historico-tipo">${rotulo} · ${formatarMoeda(item.valor)}</span>
                            <span class="creditos__historico-data">${item.data} — saldo: ${formatarMoeda(item.saldo_novo)}</span>
                        `;
                        dRefs.historicoLista.appendChild(li);
                    });
                } else {
                    dRefs.semHistorico.hidden = false;
                }

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

    function configurarModal(raiz) {
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
            if (elementoAnteriorFoco && elementoAnteriorFoco.focus) elementoAnteriorFoco.focus();
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
            form.querySelectorAll('[data-erro]').forEach((span) => {
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

        // Ajuste de créditos (adicionar / descontar / definir) — sem prompt()
        // nativo: o valor vem de um campo numérico próprio (min="0"), e o
        // resultado nunca pode ficar negativo (trava tanto aqui quanto no
        // servidor). "Definir" pula a soma/subtração mental: o valor digitado
        // vira o novo saldo direto.
        function atualizarEstadoBotaoDescontar() {
            const botaoDescontar = form.querySelector('[data-ajustar-creditos="descontar"]');
            if (!botaoDescontar) return;
            const atual = parseFloat(inputCreditos.value) || 0;
            botaoDescontar.disabled = atual <= 0;
        }

        function mostrarMensagemCreditos(texto) {
            if (!creditosMensagem) return;
            creditosMensagem.textContent = texto;
            creditosMensagem.hidden = false;
        }

        function ocultarMensagemCreditos() {
            if (!creditosMensagem) return;
            creditosMensagem.hidden = true;
            creditosMensagem.textContent = '';
        }

        if (inputAjusteValor) {
            // Bloqueia negativo já na digitação (min="0" no input já ajuda,
            // mas alguns navegadores/teclados ainda deixam colar "-").
            inputAjusteValor.addEventListener('input', () => {
                if (inputAjusteValor.value !== '' && parseFloat(inputAjusteValor.value) < 0) {
                    inputAjusteValor.value = '';
                }
            });
        }

        form.querySelectorAll('[data-ajustar-creditos]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const acao = botao.getAttribute('data-ajustar-creditos');
                const valor = parseFloat(inputAjusteValor ? inputAjusteValor.value : '');
                const valorInvalido = isNaN(valor) || valor < 0 || (acao !== 'definir' && valor <= 0);

                if (valorInvalido) {
                    mostrarMensagemCreditos(
                        acao === 'definir' ? 'Informe um valor válido.' : 'Informe um valor válido, maior que zero.'
                    );
                    if (inputAjusteValor) inputAjusteValor.focus();
                    return;
                }

                const atual = parseFloat(inputCreditos.value) || 0;
                let novoValor;
                if (acao === 'definir') {
                    novoValor = valor;
                } else {
                    novoValor = acao === 'adicionar' ? atual + valor : atual - valor;
                }

                if (novoValor < 0) {
                    novoValor = 0;
                    mostrarMensagemCreditos(
                        `Esse cliente só tinha ${formatarMoeda(atual)}; o saldo foi ajustado para R$ 0,00 (créditos nunca ficam negativos).`
                    );
                } else {
                    ocultarMensagemCreditos();
                }

                inputCreditos.value = novoValor.toFixed(2);
                creditosExibicao.textContent = formatarMoeda(novoValor);
                if (inputAjusteValor) inputAjusteValor.value = '';
                atualizarEstadoBotaoDescontar();
            });
        });

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
            atualizarEstadoBotaoDescontar,
        };
    }

    const modalCriar = configurarModal(document.querySelector('[data-modal="criar"]'));
    const modalEditar = configurarModal(document.querySelector('[data-modal="editar"]'));

    function abrirModalCriacao() {
        modalCriar.form.reset();
        modalCriar.inputCreditos.value = '0';
        modalCriar.creditosExibicao.textContent = formatarMoeda(0);
        modalCriar.atualizarEstadoBotaoDescontar();
        modalCriar.previewImagem.hidden = true;
        modalCriar.previewImagem.src = '';
        if (modalCriar.iconeVazio) modalCriar.iconeVazio.hidden = false;
        modalCriar.botaoSelecionarFoto.classList.remove('tem-foto');
        modalCriar.abrir();
        window.setTimeout(() => modalCriar.form.querySelector('[data-input-nome]').focus(), 50);
    }

    async function abrirModalEdicao(id) {
        try {
            const resposta = await fetch('/clientes/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!resposta.ok) {
                mostrarMensagem('Não foi possível carregar os dados do cliente.', 'erro');
                return;
            }

            const cliente = await resposta.json();
            const {
                form, previewAvatar, previewImagem, previewIniciais, inputCreditos,
                creditosExibicao, inputWhatsapp, inputStatus, botaoAbrirWhatsapp, atualizarEstadoBotaoDescontar,
            } = modalEditar;

            form.reset();
            form.action = form.dataset.urlBase + '/' + cliente.id;
            form.querySelector('[data-input-nome]').value = cliente.nome;
            form.querySelector('[data-input-nascimento]').value = cliente.data_nascimento;
            form.querySelector('[data-input-observacoes]').value = cliente.observacoes || '';
            if (inputStatus) inputStatus.value = cliente.status || 'ativo';
            inputCreditos.value = cliente.creditos;
            creditosExibicao.textContent = formatarMoeda(cliente.creditos);
            atualizarEstadoBotaoDescontar();
            modalEditar.botaoSelecionarFoto.classList.remove('tem-foto');

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

            modalEditar.abrir();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao carregar cliente.', 'erro');
        }
    }

    botaoAbrirCriar.addEventListener('click', abrirModalCriacao);
    pagina.addEventListener('cliente:novo', abrirModalCriacao);
}

document.addEventListener('DOMContentLoaded', iniciarPaginaClientes);
