/**
 * Lógica da tela de Vendas: lista de vendas (busca, filtros, paginação),
 * modal de nova venda (carrinho, cliente, créditos e pagamento), detalhes e
 * cancelamento de venda, agenda de aluguéis de mesas (novo/editar/cancelar,
 * pagamento) e o cadastro de mesas.
 *
 * Mesmo estilo de clientes.js: JS puro, inicializado só quando a página atual
 * é a de vendas. Toda mudança (venda, aluguel, mesa) é salva na hora via
 * fetch; depois de salvar, a página recarrega com o aviso de sucesso
 * guardado em sessionStorage, para resumo, lista e agenda ficarem certos.
 */

import { ativarFocusTrap, desativarFocusTrap, formatarMoeda } from './comum';

const ICONES_MENSAGEM = {
    sucesso: 'bi-check-circle-fill',
    erro: 'bi-exclamation-circle-fill',
};

const ICONES_TIPO = { produto: 'bi-box-seam', carta: 'bi-stack', aluguel: 'bi-dice-5' };
const PLACEHOLDER_CATALOGO = {
    produto: 'Buscar produto',
    carta: 'Buscar carta',
    aluguel: 'Buscar aluguel por cliente ou mesa',
};
const DIAS_SEMANA = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
const CHAVE_MENSAGEM = 'vendas:mensagem';

// ---- Utilitários ------------------------------------------------------------

/** "1.234,56", "12,5", "12.50" ou "R$ 10" → número (NaN se não der). */
function lerValor(texto) {
    let limpo = String(texto ?? '').replace(/[R$\s]/g, '');
    if (limpo.includes(',')) {
        limpo = limpo.replace(/\./g, '').replace(',', '.');
    }
    return limpo === '' ? NaN : Number(limpo);
}

/** 12.5 → "12,50" (para preencher campos de valor). */
function formatarDecimal(valor) {
    return Number(valor || 0).toFixed(2).replace('.', ',');
}

function paraMinutos(hora) {
    const [h, m] = String(hora || '').split(':').map(Number);
    return Number.isFinite(h) && Number.isFinite(m) ? h * 60 + m : NaN;
}

function paraHora(minutos) {
    const normalizado = ((minutos % 1440) + 1440) % 1440;
    return `${String(Math.floor(normalizado / 60)).padStart(2, '0')}:${String(normalizado % 60).padStart(2, '0')}`;
}

/** "3h", "1h30", "45min". */
function textoDuracao(minutos) {
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    if (h === 0) return `${m}min`;
    return m ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`;
}

/** 'AAAA-MM-DD' → Date local (sem fuso deslocar o dia). */
function lerData(iso) {
    const [a, m, d] = String(iso || '').split('-').map(Number);
    return a && m && d ? new Date(a, m - 1, d) : null;
}

function dataIso(data) {
    return `${data.getFullYear()}-${String(data.getMonth() + 1).padStart(2, '0')}-${String(data.getDate()).padStart(2, '0')}`;
}

function somarDias(iso, dias) {
    const data = lerData(iso);
    data.setDate(data.getDate() + dias);
    return dataIso(data);
}

function dataCurta(iso) {
    const data = lerData(iso);
    return data ? `${String(data.getDate()).padStart(2, '0')}/${String(data.getMonth() + 1).padStart(2, '0')}` : '';
}

function criar(tag, classe = '', texto = null) {
    const elemento = document.createElement(tag);
    if (classe) elemento.className = classe;
    if (texto !== null) elemento.textContent = texto;
    return elemento;
}

function icone(classe) {
    const i = criar('i', `bi ${classe}`);
    i.setAttribute('aria-hidden', 'true');
    return i;
}

/** Avatar do cliente: foto, iniciais na cor dele, ou ícone (sem cadastro). */
function preencherAvatar(span, cliente) {
    span.innerHTML = '';
    span.style.backgroundColor = '';
    if (cliente && cliente.foto_url) {
        const img = criar('img');
        img.src = cliente.foto_url;
        img.alt = '';
        span.appendChild(img);
    } else if (cliente) {
        span.textContent = cliente.iniciais;
        span.style.backgroundColor = cliente.cor_avatar;
    } else {
        span.appendChild(icone('bi-person-fill'));
    }
}

function lerConfig(pagina) {
    try {
        return JSON.parse(pagina.querySelector('[data-vendas-config]').textContent);
    } catch (erro) {
        return { mesas: [], tiposJogo: {}, formasPagamento: {}, hoje: dataIso(new Date()), dia: dataIso(new Date()), maxSemanas: 26 };
    }
}

// ---- Página -----------------------------------------------------------------

function iniciarPaginaVendas() {
    const pagina = document.querySelector('[data-vendas-page]');
    if (!pagina) return;

    const config = lerConfig(pagina);
    const urlBase = pagina.dataset.urlBase;
    const mensagemEl = pagina.querySelector('[data-mensagem]');
    const modalConfirmar = document.querySelector('[data-modal-confirmar]');
    const ehDesktop = () => window.matchMedia('(min-width: 768px)').matches;

    // ---- Comunicação com o servidor ------------------------------------------

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    /** fetch com JSON nos dois sentidos; nunca lança — devolve {ok, status, dados}. */
    async function requisicao(url, { metodo = 'GET', corpo = null } = {}) {
        const opcoes = {
            method: metodo,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        };
        if (metodo !== 'GET') opcoes.headers['X-CSRF-TOKEN'] = csrfToken();
        if (corpo) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(corpo);
        }

        let resposta;
        try {
            resposta = await fetch(url, opcoes);
        } catch (erro) {
            return { ok: false, status: 0, dados: { message: 'Sem conexão com o servidor. Tente de novo.' } };
        }

        let dados = {};
        try {
            dados = await resposta.json();
        } catch (erro) {
            dados = {};
        }
        return { ok: resposta.ok, status: resposta.status, dados };
    }

    function mensagemDeErro(dados) {
        if (dados && dados.errors) {
            const primeiro = Object.values(dados.errors)[0];
            if (primeiro && primeiro[0]) return primeiro[0];
        }
        return (dados && dados.message) || 'Algo deu errado. Tente de novo.';
    }

    // ---- Avisos ---------------------------------------------------------------

    function mostrarMensagem(texto, tipo = 'sucesso') {
        if (!mensagemEl || !texto) return;
        mensagemEl.innerHTML = '';
        mensagemEl.append(icone(ICONES_MENSAGEM[tipo] || ICONES_MENSAGEM.sucesso), criar('span', '', texto));
        mensagemEl.className = 'mensagem-flutuante mensagem-flutuante--' + tipo;
        mensagemEl.hidden = false;
        window.clearTimeout(mensagemEl._timeout);
        mensagemEl._timeout = window.setTimeout(() => { mensagemEl.hidden = true; }, tipo === 'erro' ? 6000 : 4000);
    }

    /** Recarrega a página (ou vai para outra url) e mostra o aviso depois. */
    function recarregarComMensagem(texto, url = null) {
        try {
            sessionStorage.setItem(CHAVE_MENSAGEM, texto);
        } catch (erro) { /* sem sessionStorage: só não mostra o aviso */ }
        if (url) {
            window.location.href = url;
        } else {
            window.location.reload();
        }
    }

    function urlAgenda(dia) {
        return `${urlBase}?aba=alugueis&dia=${encodeURIComponent(dia)}`;
    }

    try {
        const pendente = sessionStorage.getItem(CHAVE_MENSAGEM);
        if (pendente) {
            sessionStorage.removeItem(CHAVE_MENSAGEM);
            mostrarMensagem(pendente);
        }
    } catch (erro) { /* idem */ }

    // ---- Modais ---------------------------------------------------------------
    // Mais de um pode ficar aberto (ex.: "Mesas" por cima do formulário de
    // aluguel): Esc e clique fora fecham só o de cima.

    const pilha = [];

    function overlayDe(nome) {
        return pagina.querySelector(`[data-modal="${nome}"]`);
    }

    function abrirModal(nome, focar = null) {
        const overlay = overlayDe(nome);
        if (!overlay.hidden) return overlay;

        overlay._anterior = document.activeElement;
        overlay.hidden = false;
        pilha.push(overlay);
        document.body.style.overflow = 'hidden';
        ativarFocusTrap(overlay.firstElementChild);

        const alvo = focar || overlay.querySelector('.modal-cliente__fechar, [data-fechar-modal]');
        if (alvo) window.requestAnimationFrame(() => alvo.focus());
        return overlay;
    }

    function fecharModal(nomeOuOverlay) {
        const overlay = typeof nomeOuOverlay === 'string' ? overlayDe(nomeOuOverlay) : nomeOuOverlay;
        if (!overlay || overlay.hidden) return;

        overlay.hidden = true;
        pilha.splice(pilha.indexOf(overlay), 1);
        desativarFocusTrap(overlay.firstElementChild);
        if (pilha.length === 0) document.body.style.overflow = '';
        if (overlay._anterior && document.contains(overlay._anterior) && overlay._anterior.focus) {
            overlay._anterior.focus();
        }
        overlay.dispatchEvent(new CustomEvent('modal:fechado'));
    }

    /** Fecha pedindo confirmação antes, se o modal tiver uma guarda (venda com itens). */
    async function tentarFechar(overlay) {
        if (overlay._antesDeFechar && !(await overlay._antesDeFechar())) return;
        fecharModal(overlay);
    }

    pagina.querySelectorAll('[data-modal]').forEach((overlay) => {
        overlay.addEventListener('click', (evento) => {
            if (evento.target === overlay || evento.target.closest('[data-fechar-modal]')) {
                tentarFechar(overlay);
            }
        });
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape' || pilha.length === 0) return;
        if (modalConfirmar && !modalConfirmar.hidden) return; // o confirmar trata o próprio Esc
        tentarFechar(pilha[pilha.length - 1]);
    });

    /**
     * Confirmação com o modal genérico de Clientes (substitui window.confirm()).
     * O botão de recusar diz "Voltar" para não confundir com ações que também
     * se chamam "Cancelar …".
     */
    function confirmar({ titulo, texto, textoOk = 'Confirmar' }) {
        return new Promise((resolve) => {
            if (!modalConfirmar) {
                resolve(window.confirm(texto));
                return;
            }

            const botaoOk = modalConfirmar.querySelector('[data-confirmar-ok]');
            const botaoCancelar = modalConfirmar.querySelector('[data-confirmar-cancelar]');
            const anterior = document.activeElement;

            modalConfirmar.querySelector('[data-confirmar-titulo]').textContent = titulo;
            modalConfirmar.querySelector('[data-confirmar-texto]').textContent = texto;
            botaoOk.textContent = textoOk;
            botaoCancelar.textContent = 'Voltar';

            function finalizar(resultado) {
                modalConfirmar.hidden = true;
                if (pilha.length === 0) document.body.style.overflow = '';
                desativarFocusTrap(modalConfirmar.querySelector('.modal-confirmar'));
                botaoOk.removeEventListener('click', aoConfirmar);
                botaoCancelar.removeEventListener('click', aoCancelar);
                modalConfirmar.removeEventListener('click', aoClicarFora);
                document.removeEventListener('keydown', aoEscapar);
                if (anterior && anterior.focus) anterior.focus();
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

    /** Mostra erros de validação do servidor ao lado de cada campo (data-erro="campo"). */
    function exibirErrosDeCampos(raiz, erros = {}) {
        raiz.querySelectorAll('[data-erro]').forEach((span) => {
            const mensagens = erros[span.dataset.erro];
            span.hidden = !mensagens;
            span.textContent = mensagens ? mensagens[0] : '';
        });
    }

    // ---- Seletor de cliente (venda e aluguel) ----------------------------------

    function criarSeletorCliente(raiz, aoMudar) {
        const permitirAvulso = raiz.dataset.permitirAvulso === '1';
        const escolhidoEl = raiz.querySelector('[data-cliente-escolhido]');
        const avatarEl = raiz.querySelector('[data-cliente-avatar]');
        const nomeEl = raiz.querySelector('[data-cliente-nome]');
        const detalheEl = raiz.querySelector('[data-cliente-detalhe]');
        const removerBtn = raiz.querySelector('[data-cliente-remover]');
        const buscaBloco = raiz.querySelector('[data-cliente-busca-bloco]');
        const campo = raiz.querySelector('[data-cliente-busca]');
        const lista = raiz.querySelector('[data-cliente-resultados]');

        // null | { tipo: 'cliente', cliente } | { tipo: 'avulso', nome }
        let valor = null;
        let timeout = null;
        let ultimaBusca = 0;
        let opcoes = [];
        let ativa = -1;

        function marcarAtiva(indice) {
            ativa = indice;
            opcoes.forEach((opcao, i) => opcao.el.classList.toggle('sugestoes__item--ativo', i === ativa));
            if (opcoes[ativa]) opcoes[ativa].el.scrollIntoView({ block: 'nearest' });
        }

        function adicionarOpcao(el, aoEscolher) {
            el.setAttribute('role', 'option');
            // mousedown (e não click) para escolher antes do blur esconder a lista
            el.addEventListener('mousedown', (evento) => {
                evento.preventDefault();
                aoEscolher();
            });
            opcoes.push({ el, aoEscolher });
            lista.appendChild(el);
        }

        function desenharResultados(clientes, termo) {
            lista.innerHTML = '';
            opcoes = [];
            ativa = -1;

            clientes.forEach((cliente) => {
                const li = criar('li', 'sugestoes__item');
                const avatar = criar('span', 'avatar avatar--mini');
                preencherAvatar(avatar, cliente);
                const info = criar('span', 'sugestoes__info');
                info.append(criar('strong', '', cliente.nome));
                const detalhe = cliente.inativo
                    ? 'inativo'
                    : (cliente.creditos > 0 ? `saldo ${formatarMoeda(cliente.creditos)}` : 'sem créditos');
                info.append(criar('small', '', detalhe));
                li.append(avatar, info);
                adicionarOpcao(li, () => escolher({ tipo: 'cliente', cliente }));
            });

            const jaExiste = clientes.some((c) => c.nome.toLowerCase() === termo.toLowerCase());
            if (permitirAvulso && termo && !jaExiste) {
                const li = criar('li', 'sugestoes__item sugestoes__item--avulso');
                const avatar = criar('span', 'avatar avatar--mini avatar--vazio');
                avatar.append(icone('bi-person-plus'));
                const info = criar('span', 'sugestoes__info');
                info.append(criar('strong', '', `Usar “${termo}”`), criar('small', '', 'sem cadastro de cliente'));
                li.append(avatar, info);
                adicionarOpcao(li, () => escolher({ tipo: 'avulso', nome: termo }));
            }

            if (opcoes.length === 0) {
                lista.append(criar('li', 'sugestoes__vazio', termo ? `Nenhum cliente encontrado para “${termo}”.` : 'Nenhum cliente cadastrado.'));
            }

            lista.hidden = false;
            if (termo && opcoes.length) marcarAtiva(0);
        }

        async function buscar() {
            const termo = campo.value.trim();
            const id = ++ultimaBusca;
            const { ok, dados } = await requisicao(`${urlBase}/clientes?q=${encodeURIComponent(termo)}`);
            if (id !== ultimaBusca || document.activeElement !== campo) return;
            desenharResultados(ok && Array.isArray(dados) ? dados : [], termo);
        }

        function desenharEscolhido() {
            escolhidoEl.hidden = !valor;
            buscaBloco.hidden = Boolean(valor);
            if (!valor) return;

            if (valor.tipo === 'cliente') {
                const cliente = valor.cliente;
                preencherAvatar(avatarEl, cliente);
                avatarEl.classList.remove('avatar--vazio');
                nomeEl.textContent = cliente.nome;
                detalheEl.textContent = cliente.creditos > 0 ? `saldo de créditos: ${formatarMoeda(cliente.creditos)}` : 'sem saldo de créditos';
            } else {
                preencherAvatar(avatarEl, null);
                avatarEl.classList.add('avatar--vazio');
                nomeEl.textContent = valor.nome;
                detalheEl.textContent = 'sem cadastro de cliente';
            }
        }

        function escolher(novo, avisar = true) {
            valor = novo;
            campo.value = '';
            lista.hidden = true;
            desenharEscolhido();
            if (avisar) aoMudar(valor);
        }

        campo.addEventListener('input', () => {
            window.clearTimeout(timeout);
            timeout = window.setTimeout(buscar, 200);
        });
        campo.addEventListener('focus', buscar);
        campo.addEventListener('blur', () => { window.setTimeout(() => { lista.hidden = true; }, 120); });
        campo.addEventListener('keydown', (evento) => {
            if (evento.key === 'ArrowDown' && opcoes.length) {
                evento.preventDefault();
                marcarAtiva(Math.min(opcoes.length - 1, ativa + 1));
            } else if (evento.key === 'ArrowUp' && opcoes.length) {
                evento.preventDefault();
                marcarAtiva(Math.max(0, ativa - 1));
            } else if (evento.key === 'Enter') {
                evento.preventDefault(); // não envia o formulário do aluguel
                if (opcoes[ativa]) opcoes[ativa].aoEscolher();
            } else if (evento.key === 'Escape' && !lista.hidden) {
                evento.stopPropagation();
                lista.hidden = true;
            }
        });

        removerBtn.addEventListener('click', () => {
            escolher(null);
            campo.focus();
        });

        return {
            valor: () => valor,
            /** Define sem disparar aoMudar (quem chama já atualiza a tela). */
            definir: (novo) => escolher(novo, false),
        };
    }

    // =========================================================================
    // Aba Vendas: busca, filtros e paginação
    // =========================================================================

    function iniciarListaVendas() {
        const wrapper = pagina.querySelector('[data-lista-wrapper]');
        if (!wrapper) return;

        const campo = pagina.querySelector('[data-busca-campo]');
        const limparBusca = pagina.querySelector('[data-busca-limpar]');
        const spinner = pagina.querySelector('[data-busca-spinner]');
        const selects = pagina.querySelectorAll('[data-filtro]');
        const limparFiltros = pagina.querySelector('[data-limpar-filtros]');
        const padroes = { periodo: 'tudo', pagamento: 'todos', tipo: 'todos' };

        let paginaAtual = Number(new URLSearchParams(window.location.search).get('page')) || 1;
        let timeout = null;
        let ultimaCarga = 0;

        function parametros() {
            const params = new URLSearchParams();
            const termo = campo.value.trim();
            if (termo) params.set('busca', termo);
            selects.forEach((select) => {
                if (select.value !== padroes[select.dataset.filtro]) params.set(select.dataset.filtro, select.value);
            });
            if (paginaAtual > 1) params.set('page', paginaAtual);
            return params;
        }

        function atualizarControles() {
            limparBusca.hidden = campo.value.length === 0;
            limparFiltros.hidden = Array.from(selects).every((s) => s.value === padroes[s.dataset.filtro]);
        }

        async function carregar() {
            const params = parametros();
            const id = ++ultimaCarga;
            wrapper.classList.add('esta-carregando');
            spinner.hidden = false;

            try {
                const resposta = await fetch(`${urlBase}/listar?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (id !== ultimaCarga) return;
                if (!resposta.ok) {
                    mostrarMensagem('Não foi possível carregar as vendas.', 'erro');
                    return;
                }
                wrapper.innerHTML = await resposta.text();
                // a url acompanha os filtros: recarregar a página mantém a lista
                const query = params.toString();
                window.history.replaceState(null, '', window.location.pathname + (query ? `?${query}` : ''));
            } catch (erro) {
                mostrarMensagem('Sem conexão com o servidor.', 'erro');
            } finally {
                if (id === ultimaCarga) {
                    wrapper.classList.remove('esta-carregando');
                    spinner.hidden = true;
                }
            }
        }

        function recomecar() {
            paginaAtual = 1;
            atualizarControles();
            carregar();
        }

        pagina.querySelector('[data-busca-form]').addEventListener('submit', (evento) => evento.preventDefault());

        campo.addEventListener('input', () => {
            atualizarControles();
            window.clearTimeout(timeout);
            timeout = window.setTimeout(recomecar, 300);
        });

        limparBusca.addEventListener('click', () => {
            campo.value = '';
            campo.focus();
            recomecar();
        });

        selects.forEach((select) => select.addEventListener('change', recomecar));

        limparFiltros.addEventListener('click', () => {
            selects.forEach((select) => { select.value = padroes[select.dataset.filtro]; });
            recomecar();
        });

        wrapper.addEventListener('click', (evento) => {
            const link = evento.target.closest('[data-pagina-link]');
            if (!link) return;
            evento.preventDefault();
            if (link.getAttribute('aria-disabled') === 'true') return;
            paginaAtual = Number(link.dataset.pagina) || 1;
            carregar().then(() => wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        });

        atualizarControles();
    }

    // =========================================================================
    // Nova venda
    // =========================================================================

    const modalVenda = overlayDe('venda');
    const elVenda = {
        erros: modalVenda.querySelector('[data-venda-erros]'),
        tipos: modalVenda.querySelectorAll('[data-catalogo-tipo]'),
        busca: modalVenda.querySelector('[data-catalogo-busca]'),
        spinner: modalVenda.querySelector('[data-catalogo-spinner]'),
        resultados: modalVenda.querySelector('[data-catalogo-resultados]'),
        catalogoVazio: modalVenda.querySelector('[data-catalogo-vazio]'),
        carrinho: modalVenda.querySelector('[data-carrinho]'),
        carrinhoVazio: modalVenda.querySelector('[data-carrinho-vazio]'),
        contagem: modalVenda.querySelector('[data-carrinho-contagem]'),
        usarCreditosBloco: modalVenda.querySelector('[data-usar-creditos-bloco]'),
        usarCreditos: modalVenda.querySelector('[data-usar-creditos]'),
        usarCreditosSaldo: modalVenda.querySelector('[data-usar-creditos-saldo]'),
        formasContainer: modalVenda.querySelector('[data-formas-pagamento]'),
        formas: modalVenda.querySelectorAll('[data-forma-pagamento]'),
        pagoComCreditos: modalVenda.querySelector('[data-pago-com-creditos]'),
        mostrarObs: modalVenda.querySelector('[data-mostrar-observacoes]'),
        obsCampo: modalVenda.querySelector('[data-observacoes-campo]'),
        obs: modalVenda.querySelector('[data-venda-observacoes]'),
        subtotal: modalVenda.querySelector('[data-total-subtotal]'),
        creditosLinha: modalVenda.querySelector('[data-total-creditos-linha]'),
        creditos: modalVenda.querySelector('[data-total-creditos]'),
        pagar: modalVenda.querySelector('[data-total-pagar]'),
        finalizar: modalVenda.querySelector('[data-finalizar-venda]'),
        dica: modalVenda.querySelector('[data-venda-dica]'),
    };

    const venda = { itens: [], tipoCatalogo: 'produto', enviando: false };
    let ultimoCatalogo = 0;
    let timeoutCatalogo = null;

    const seletorClienteVenda = criarSeletorCliente(
        modalVenda.querySelector('[data-seletor-cliente="venda"]'),
        () => atualizarTotais(),
    );

    function chaveItem(item) {
        return `${item.tipo}:${item.id}`;
    }

    function clienteDaVenda() {
        const valor = seletorClienteVenda.valor();
        return valor && valor.tipo === 'cliente' ? valor.cliente : null;
    }

    function formaEscolhida() {
        const marcada = Array.from(elVenda.formas).find((r) => r.checked);
        return marcada ? marcada.value : null;
    }

    function mostrarErroVenda(texto) {
        elVenda.erros.textContent = texto || '';
        elVenda.erros.hidden = !texto;
    }

    function abrirNovaVenda({ itens = [], cliente = null } = {}) {
        venda.itens = itens.map((item) => ({ ...item, quantidade: 1 }));
        venda.enviando = false;
        seletorClienteVenda.definir(cliente ? { tipo: 'cliente', cliente } : null);
        elVenda.usarCreditos.checked = false;
        elVenda.formas.forEach((r) => { r.checked = false; });
        elVenda.obs.value = '';
        elVenda.obsCampo.hidden = true;
        elVenda.mostrarObs.hidden = false;
        elVenda.busca.value = '';
        mostrarErroVenda('');

        // pagando um aluguel, a busca já começa em "Mesas"
        definirTipoCatalogo(itens.some((i) => i.tipo === 'aluguel') ? 'aluguel' : 'produto');
        desenharCarrinho();
        atualizarTotais();
        abrirModal('venda', ehDesktop() ? elVenda.busca : null);
    }

    modalVenda._antesDeFechar = async () => {
        if (venda.itens.length === 0 || venda.enviando) return true;
        return confirmar({
            titulo: 'Descartar esta venda?',
            texto: 'Os itens adicionados não serão salvos.',
            textoOk: 'Descartar',
        });
    };

    function definirTipoCatalogo(tipo) {
        venda.tipoCatalogo = tipo;
        elVenda.tipos.forEach((r) => { r.checked = r.value === tipo; });
        elVenda.busca.placeholder = PLACEHOLDER_CATALOGO[tipo];
        carregarCatalogo();
    }

    async function carregarCatalogo() {
        const id = ++ultimoCatalogo;
        const termo = elVenda.busca.value.trim();
        elVenda.spinner.hidden = false;

        const { ok, dados } = await requisicao(`${urlBase}/catalogo?tipo=${venda.tipoCatalogo}&q=${encodeURIComponent(termo)}`);
        if (id !== ultimoCatalogo) return;
        elVenda.spinner.hidden = true;

        if (!ok) {
            elVenda.resultados.innerHTML = '';
            elVenda.catalogoVazio.textContent = 'Não foi possível carregar os itens.';
            elVenda.catalogoVazio.hidden = false;
            return;
        }

        desenharCatalogo(dados, termo);
    }

    function desenharCatalogo(itens, termo) {
        elVenda.resultados.innerHTML = '';
        elVenda.resultados._itens = itens;

        if (itens.length === 0) {
            const nomes = { produto: 'produto', carta: 'carta', aluguel: 'aluguel' };
            elVenda.catalogoVazio.textContent = termo
                ? `Nenhum ${nomes[venda.tipoCatalogo]} encontrado para “${termo}”.`
                : {
                    produto: 'Nenhum produto cadastrado no estoque.',
                    carta: 'Nenhuma carta cadastrada no estoque.',
                    aluguel: 'Nenhum aluguel de mesa aguardando pagamento.',
                }[venda.tipoCatalogo];
            elVenda.catalogoVazio.hidden = false;
            return;
        }
        elVenda.catalogoVazio.hidden = true;

        itens.forEach((item) => {
            const noCarrinho = venda.itens.find((i) => chaveItem(i) === chaveItem(item));
            const semEstoque = item.estoque !== null && item.estoque <= 0;
            const esgotou = noCarrinho && (item.tipo === 'aluguel' || noCarrinho.quantidade >= item.estoque);

            const li = criar('li');
            const botao = criar('button', 'catalogo__item');
            botao.type = 'button';
            botao.disabled = semEstoque;

            const miniatura = criar('span', `catalogo__miniatura catalogo__miniatura--${item.tipo}`);
            if (item.imagem) {
                const img = criar('img');
                img.src = item.imagem;
                img.alt = '';
                img.loading = 'lazy';
                miniatura.append(img);
            } else {
                miniatura.append(icone(ICONES_TIPO[item.tipo]));
            }

            const info = criar('span', 'catalogo__info');
            info.append(criar('strong', '', item.nome));
            if (item.detalhe) info.append(criar('small', '', item.detalhe));

            const lado = criar('span', 'catalogo__lado');
            lado.append(criar('span', 'catalogo__preco', formatarMoeda(item.preco)));
            if (noCarrinho) {
                lado.append(criar('small', 'catalogo__no-carrinho', item.tipo === 'aluguel' ? 'na venda' : `${noCarrinho.quantidade} na venda`));
            } else if (item.estoque !== null) {
                lado.append(criar('small', semEstoque ? 'catalogo__estoque catalogo__estoque--zero' : 'catalogo__estoque', semEstoque ? 'sem estoque' : `${item.estoque} em estoque`));
            }

            const acao = criar('span', 'catalogo__adicionar');
            acao.append(icone(esgotou ? 'bi-check-lg' : 'bi-plus-lg'));

            botao.append(miniatura, info, lado, acao);
            botao.setAttribute('aria-label', `Adicionar ${item.nome}, ${formatarMoeda(item.preco)}`);
            botao.addEventListener('click', () => adicionarItem(item));
            li.append(botao);
            elVenda.resultados.append(li);
        });
    }

    function adicionarItem(item) {
        const existente = venda.itens.find((i) => chaveItem(i) === chaveItem(item));

        if (existente) {
            if (item.tipo === 'aluguel') {
                mostrarMensagem('Esse aluguel já está na venda.', 'erro');
                return;
            }
            if (existente.quantidade >= item.estoque) {
                mostrarMensagem(`Só há ${item.estoque} de “${item.nome}” em estoque.`, 'erro');
                return;
            }
            existente.quantidade += 1;
        } else {
            venda.itens.push({ ...item, quantidade: 1 });
        }

        // pagando o aluguel de um cliente cadastrado, já vincula o cliente
        if (item.tipo === 'aluguel' && item.cliente && !seletorClienteVenda.valor()) {
            seletorClienteVenda.definir({ tipo: 'cliente', cliente: item.cliente });
        }

        mostrarErroVenda('');
        desenharCarrinho(chaveItem(item));
        atualizarTotais();
        desenharCatalogo(elVenda.resultados._itens || [], elVenda.busca.value.trim());
    }

    function alterarQuantidade(chave, diferenca) {
        const item = venda.itens.find((i) => chaveItem(i) === chave);
        if (!item) return;
        item.quantidade = Math.max(1, Math.min(item.estoque ?? 1, item.quantidade + diferenca));
        desenharCarrinho();
        atualizarTotais();
        desenharCatalogo(elVenda.resultados._itens || [], elVenda.busca.value.trim());
    }

    function removerItem(chave) {
        venda.itens = venda.itens.filter((i) => chaveItem(i) !== chave);
        desenharCarrinho();
        atualizarTotais();
        desenharCatalogo(elVenda.resultados._itens || [], elVenda.busca.value.trim());
    }

    function desenharCarrinho(destacar = null) {
        elVenda.carrinho.innerHTML = '';
        elVenda.carrinhoVazio.hidden = venda.itens.length > 0;
        const unidades = venda.itens.reduce((soma, i) => soma + i.quantidade, 0);
        elVenda.contagem.textContent = unidades ? `${unidades} ${unidades === 1 ? 'item' : 'itens'}` : '';

        venda.itens.forEach((item) => {
            const chave = chaveItem(item);
            const li = criar('li', 'carrinho__item');
            if (chave === destacar) li.classList.add('carrinho__item--novo');

            const tipo = criar('span', `carrinho__tipo carrinho__tipo--${item.tipo}`);
            tipo.append(icone(ICONES_TIPO[item.tipo]));

            const info = criar('span', 'carrinho__info');
            info.append(criar('strong', '', item.nome));
            info.append(criar('small', '', item.tipo === 'aluguel' ? (item.detalhe || 'aluguel de mesa') : `${formatarMoeda(item.preco)} cada`));

            const controles = criar('span', 'carrinho__controles');
            if (item.tipo !== 'aluguel') {
                const stepper = criar('span', 'stepper');
                const menos = criar('button', 'stepper__botao');
                menos.type = 'button';
                menos.disabled = item.quantidade <= 1;
                menos.setAttribute('aria-label', `Diminuir quantidade de ${item.nome}`);
                menos.append(icone('bi-dash'));
                menos.addEventListener('click', () => alterarQuantidade(chave, -1));

                const quantidade = criar('span', 'stepper__valor', String(item.quantidade));
                quantidade.setAttribute('aria-live', 'polite');

                const mais = criar('button', 'stepper__botao');
                mais.type = 'button';
                mais.disabled = item.quantidade >= item.estoque;
                mais.setAttribute('aria-label', `Aumentar quantidade de ${item.nome}`);
                mais.title = mais.disabled ? `Só há ${item.estoque} em estoque` : '';
                mais.append(icone('bi-plus'));
                mais.addEventListener('click', () => alterarQuantidade(chave, 1));

                stepper.append(menos, quantidade, mais);
                controles.append(stepper);
            }
            controles.append(criar('span', 'carrinho__subtotal', formatarMoeda(item.preco * item.quantidade)));

            const remover = criar('button', 'carrinho__remover');
            remover.type = 'button';
            remover.setAttribute('aria-label', `Remover ${item.nome}`);
            remover.title = 'Remover';
            remover.append(icone('bi-x-lg'));
            remover.addEventListener('click', () => removerItem(chave));

            li.append(tipo, info, controles, remover);
            elVenda.carrinho.append(li);
        });
    }

    function calcularTotais() {
        const subtotal = Math.round(venda.itens.reduce((soma, i) => soma + i.preco * i.quantidade, 0) * 100) / 100;
        const cliente = clienteDaVenda();
        const saldo = cliente ? cliente.creditos : 0;
        const creditos = elVenda.usarCreditos.checked && saldo > 0 ? Math.min(saldo, subtotal) : 0;
        return { subtotal, saldo, creditos, restante: Math.round((subtotal - creditos) * 100) / 100 };
    }

    function atualizarTotais() {
        const { subtotal, saldo, creditos, restante } = calcularTotais();
        const cliente = clienteDaVenda();

        // pagamento com créditos pode estar desligado em Configurações > Vendas
        elVenda.usarCreditosBloco.hidden = !(config.permitirCreditos && cliente && saldo > 0);
        if (elVenda.usarCreditosBloco.hidden) elVenda.usarCreditos.checked = false;
        elVenda.usarCreditosSaldo.textContent = `saldo de ${cliente ? cliente.nome.split(' ')[0] : ''}: ${formatarMoeda(saldo)}`;

        const creditosCobremTudo = creditos > 0 && restante <= 0;
        elVenda.formasContainer.hidden = creditosCobremTudo;
        elVenda.pagoComCreditos.hidden = !creditosCobremTudo;

        elVenda.subtotal.textContent = formatarMoeda(subtotal);
        elVenda.creditosLinha.hidden = creditos <= 0;
        elVenda.creditos.textContent = `− ${formatarMoeda(creditos)}`;
        elVenda.pagar.textContent = formatarMoeda(restante);

        const forma = formaEscolhida();
        let dica = '';
        if (venda.itens.length === 0) {
            dica = 'Adicione ao menos um item.';
        } else if (restante > 0 && !forma) {
            dica = creditos > 0 ? 'Escolha como o restante será pago.' : 'Escolha a forma de pagamento.';
        } else if (restante > 0) {
            dica = `${formatarMoeda(restante)} em ${config.formasPagamento[forma]}${creditos > 0 ? ` + ${formatarMoeda(creditos)} em créditos` : ''}.`;
        } else if (creditos > 0) {
            dica = `Pago com ${formatarMoeda(creditos)} dos créditos do cliente.`;
        }

        const pronta = venda.itens.length > 0 && (restante <= 0 || forma);
        elVenda.finalizar.disabled = !pronta || venda.enviando;
        elVenda.dica.textContent = dica;
        elVenda.dica.classList.toggle('modal-venda__dica--ok', Boolean(pronta));
    }

    async function finalizarVenda() {
        if (venda.enviando) return;
        const { creditos, restante } = calcularTotais();
        const cliente = clienteDaVenda();

        venda.enviando = true;
        atualizarTotais();
        const textoOriginal = elVenda.finalizar.innerHTML;
        elVenda.finalizar.textContent = 'Registrando…';
        mostrarErroVenda('');

        const { ok, dados } = await requisicao(urlBase, {
            metodo: 'POST',
            corpo: {
                cliente_id: cliente ? cliente.id : null,
                itens: venda.itens.map((i) => ({ tipo: i.tipo, id: i.id, quantidade: i.quantidade })),
                usar_creditos: creditos > 0,
                forma_pagamento: restante > 0 ? formaEscolhida() : null,
                observacoes: elVenda.obs.value.trim() || null,
            },
        });

        if (ok) {
            fecharModal(modalVenda);
            recarregarComMensagem(dados.message || 'Venda registrada.');
            return;
        }

        venda.enviando = false;
        elVenda.finalizar.innerHTML = textoOriginal;
        mostrarErroVenda(mensagemDeErro(dados));
        elVenda.erros.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        atualizarTotais();
        carregarCatalogo(); // estoque pode ter mudado
    }

    elVenda.tipos.forEach((radio) => radio.addEventListener('change', () => {
        elVenda.busca.value = '';
        definirTipoCatalogo(radio.value);
    }));
    elVenda.busca.addEventListener('input', () => {
        window.clearTimeout(timeoutCatalogo);
        timeoutCatalogo = window.setTimeout(carregarCatalogo, 200);
    });
    elVenda.busca.addEventListener('keydown', (evento) => {
        // Enter adiciona o primeiro resultado disponível (leitor de código de barras, digitação rápida)
        if (evento.key !== 'Enter') return;
        evento.preventDefault();
        const primeiro = elVenda.resultados.querySelector('.catalogo__item:not(:disabled)');
        if (primeiro) primeiro.click();
    });
    elVenda.usarCreditos.addEventListener('change', atualizarTotais);
    elVenda.formas.forEach((radio) => radio.addEventListener('change', atualizarTotais));
    elVenda.mostrarObs.addEventListener('click', () => {
        elVenda.obsCampo.hidden = false;
        elVenda.mostrarObs.hidden = true;
        elVenda.obs.focus();
    });
    elVenda.finalizar.addEventListener('click', finalizarVenda);

    // =========================================================================
    // Detalhes da venda
    // =========================================================================

    const modalRecibo = overlayDe('detalhes-venda');

    async function abrirDetalhesVenda(id) {
        const { ok, dados } = await requisicao(`${urlBase}/${id}`);
        if (!ok) {
            mostrarMensagem('Não foi possível abrir essa venda.', 'erro');
            return;
        }

        const cancelada = dados.status === 'cancelada';
        modalRecibo.querySelector('.modal-recibo').classList.toggle('modal-recibo--cancelada', cancelada);
        modalRecibo.querySelector('[data-recibo-titulo]').textContent = `Venda #${dados.id}`;
        modalRecibo.querySelector('[data-recibo-total]').textContent = formatarMoeda(dados.total);
        modalRecibo.querySelector('[data-recibo-data]').textContent = dados.data;

        const selo = modalRecibo.querySelector('[data-recibo-cancelada]');
        selo.hidden = !cancelada;
        selo.textContent = cancelada ? `Cancelada em ${dados.cancelada_em}` : '';
        const motivo = modalRecibo.querySelector('[data-recibo-motivo]');
        motivo.hidden = !(cancelada && dados.motivo_cancelamento);
        motivo.textContent = dados.motivo_cancelamento ? `Motivo: ${dados.motivo_cancelamento}` : '';

        const itens = modalRecibo.querySelector('[data-recibo-itens]');
        itens.innerHTML = '';
        dados.itens.forEach((item) => {
            const li = criar('li', 'recibo__item');
            const tipo = criar('span', `carrinho__tipo carrinho__tipo--${item.tipo}`);
            tipo.append(icone(ICONES_TIPO[item.tipo]));
            const info = criar('span', 'recibo__item-info');
            info.append(criar('strong', '', item.quantidade > 1 ? `${item.quantidade}× ${item.descricao}` : item.descricao));
            if (item.quantidade > 1) info.append(criar('small', '', `${formatarMoeda(item.preco_unitario)} cada`));
            li.append(tipo, info, criar('span', 'recibo__item-valor', formatarMoeda(item.subtotal)));
            itens.append(li);
        });

        const pagamento = modalRecibo.querySelector('[data-recibo-pagamento]');
        pagamento.innerHTML = '';
        const linhaPagamento = (rotulo, valor) => {
            const div = criar('div', 'recibo__pagamento-linha');
            div.append(criar('dt', '', rotulo), criar('dd', '', formatarMoeda(valor)));
            pagamento.append(div);
        };
        if (dados.valor_creditos > 0) linhaPagamento('Créditos do cliente', dados.valor_creditos);
        if (dados.valor_restante > 0) linhaPagamento(dados.forma_pagamento_rotulo || 'Pagamento', dados.valor_restante);
        if (dados.valor_creditos <= 0 && dados.valor_restante <= 0) linhaPagamento('Sem cobrança', 0);

        const clienteEl = modalRecibo.querySelector('[data-recibo-cliente]');
        clienteEl.innerHTML = '';
        if (dados.cliente) {
            const avatar = criar('span', 'avatar');
            preencherAvatar(avatar, dados.cliente);
            const info = criar('span', 'recibo__cliente-info');
            info.append(criar('strong', '', dados.cliente.nome));
            info.append(criar('small', '', `saldo atual de créditos: ${formatarMoeda(dados.cliente.creditos)}`));
            clienteEl.append(avatar, info);
        } else {
            clienteEl.append(criar('span', 'perfil__vazio', 'Venda sem cliente vinculado.'));
        }

        const obs = modalRecibo.querySelector('[data-recibo-observacoes]');
        obs.textContent = dados.observacoes || '';
        modalRecibo.querySelector('[data-recibo-observacoes-secao]').hidden = !dados.observacoes;

        modalRecibo.querySelector('[data-recibo-rodape]').hidden = cancelada;
        modalRecibo._venda = dados;

        abrirModal('detalhes-venda');
    }

    // Cancelar venda: confirmação com o motivo (obrigatório ou não, conforme
    // Configurações > Vendas).
    const modalCancelarVenda = overlayDe('cancelar-venda');
    const campoMotivo = modalCancelarVenda.querySelector('[data-cancelar-venda-motivo]');
    const erroMotivo = modalCancelarVenda.querySelector('[data-cancelar-venda-erro]');
    const botaoConfirmarCancelarVenda = modalCancelarVenda.querySelector('[data-cancelar-venda-confirmar]');

    function mostrarErroMotivo(texto) {
        erroMotivo.textContent = texto || '';
        erroMotivo.hidden = !texto;
        campoMotivo.closest('.campo').classList.toggle('campo--invalido', Boolean(texto));
    }

    modalRecibo.querySelector('[data-cancelar-venda]').addEventListener('click', () => {
        const dados = modalRecibo._venda;
        if (!dados) return;

        const partes = [];
        if (dados.itens.some((i) => i.tipo !== 'aluguel')) partes.push('o estoque dos itens volta para a loja');
        if (dados.itens.some((i) => i.tipo === 'aluguel')) partes.push('o aluguel volta a ficar em aberto');
        if (dados.valor_creditos > 0 && dados.cliente) partes.push(`${formatarMoeda(dados.valor_creditos)} voltam para os créditos de ${dados.cliente.nome}`);
        const resumo = partes.length ? partes.join(', ').replace(/^./, (c) => c.toUpperCase()) + '. ' : '';

        modalCancelarVenda.querySelector('[data-cancelar-venda-titulo]').textContent = `Cancelar a venda #${dados.id}?`;
        modalCancelarVenda.querySelector('[data-cancelar-venda-texto]').textContent =
            `${resumo}A venda continua no histórico, marcada como cancelada.`;
        campoMotivo.value = '';
        mostrarErroMotivo('');
        abrirModal('cancelar-venda', config.exigirMotivoCancelamento ? campoMotivo : modalCancelarVenda.querySelector('[data-fechar-modal]'));
    });

    campoMotivo.addEventListener('input', () => mostrarErroMotivo(''));

    botaoConfirmarCancelarVenda.addEventListener('click', async () => {
        const dados = modalRecibo._venda;
        const motivo = campoMotivo.value.trim();
        if (config.exigirMotivoCancelamento && !motivo) {
            mostrarErroMotivo('Informe o motivo do cancelamento.');
            campoMotivo.focus();
            return;
        }

        botaoConfirmarCancelarVenda.disabled = true;
        const { ok, dados: resposta } = await requisicao(`${urlBase}/${dados.id}/cancelar`, {
            metodo: 'POST',
            corpo: { motivo: motivo || null },
        });
        botaoConfirmarCancelarVenda.disabled = false;

        if (ok) {
            fecharModal(modalCancelarVenda);
            fecharModal(modalRecibo);
            recarregarComMensagem(resposta.message);
        } else if (resposta.errors && resposta.errors.motivo) {
            mostrarErroMotivo(resposta.errors.motivo[0]);
        } else {
            mostrarMensagem(mensagemDeErro(resposta), 'erro');
        }
    });

    // =========================================================================
    // Aluguéis: detalhes, cancelamento
    // =========================================================================

    const modalReserva = overlayDe('detalhes-aluguel');
    const modalCancelarAluguel = overlayDe('cancelar-aluguel');
    const rotulosStatus = { agendado: 'Agendado', pago: 'Pago', cancelado: 'Cancelado' };

    async function abrirDetalhesAluguel(id) {
        const { ok, dados } = await requisicao(`${urlBase}/alugueis/${id}`);
        if (!ok) {
            mostrarMensagem('Não foi possível abrir essa reserva.', 'erro');
            return;
        }
        modalReserva._aluguel = dados;

        const status = modalReserva.querySelector('[data-reserva-status]');
        status.className = `aluguel-status aluguel-status--${dados.status}`;
        status.textContent = rotulosStatus[dados.status] || dados.status;

        modalReserva.querySelector('[data-reserva-horario]').textContent = dados.horario;
        modalReserva.querySelector('[data-reserva-data]').textContent = dados.data_texto.replace(/^./, (c) => c.toUpperCase());
        modalReserva.querySelector('[data-reserva-mesa]').textContent = `${dados.mesa} · ${dados.duracao_texto}`;

        const quem = modalReserva.querySelector('[data-reserva-quem]');
        quem.innerHTML = '';
        const avatar = criar('span', 'avatar avatar--mini');
        preencherAvatar(avatar, dados.cliente);
        if (!dados.cliente) avatar.classList.add('avatar--vazio');
        quem.append(avatar, criar('span', '', dados.nome_exibicao));
        if (!dados.cliente) quem.append(criar('small', 'reserva__sem-cadastro', 'sem cadastro'));

        const jogo = modalReserva.querySelector('[data-reserva-jogo]');
        jogo.innerHTML = '';
        const chip = criar('span', `jogo-chip jogo-chip--${dados.tipo_jogo}`);
        chip.className = `jogo-chip jogo-chip--${dados.tipo_jogo_cor}`;
        chip.append(icone(dados.tipo_jogo_icone), document.createTextNode(` ${dados.tipo_jogo_rotulo}`));
        jogo.append(chip);
        if (dados.jogo) jogo.append(document.createTextNode(` ${dados.jogo}`));

        const serieItem = modalReserva.querySelector('[data-reserva-serie-item]');
        serieItem.hidden = !dados.serie;
        if (dados.serie) {
            modalReserva.querySelector('[data-reserva-serie]').textContent =
                `Toda ${dados.dia_semana} às ${dados.hora_inicio} · data ${dados.serie.posicao} de ${dados.serie.total} (até ${dados.serie.ultima})`;
        }

        modalReserva.querySelector('[data-reserva-valor]').textContent = formatarMoeda(dados.valor);
        const obs = modalReserva.querySelector('[data-reserva-observacoes]');
        obs.textContent = dados.observacoes || '';
        obs.hidden = !dados.observacoes;

        const agendado = dados.status === 'agendado';
        modalReserva.querySelector('[data-reserva-editar]').hidden = !agendado;
        modalReserva.querySelector('[data-reserva-pagar]').hidden = !agendado;
        const verVenda = modalReserva.querySelector('[data-reserva-ver-venda]');
        verVenda.hidden = !(dados.status === 'pago' && dados.venda_id);
        modalReserva.querySelector('[data-reserva-venda-rotulo]').textContent = `Pago na venda #${dados.venda_id}`;
        modalReserva.querySelector('[data-reserva-rodape]').hidden = !agendado;

        abrirModal('detalhes-aluguel', agendado ? modalReserva.querySelector('[data-reserva-pagar]') : null);
    }

    modalReserva.querySelector('[data-reserva-pagar]').addEventListener('click', () => {
        const dados = modalReserva._aluguel;
        fecharModal(modalReserva);
        abrirNovaVenda({
            itens: [{
                tipo: 'aluguel',
                id: dados.id,
                nome: dados.descricao_venda,
                detalhe: `${dados.nome_exibicao} · ${dados.tipo_jogo_rotulo}`,
                preco: dados.valor,
                estoque: null,
                imagem: null,
                cliente: dados.cliente,
            }],
            cliente: dados.cliente,
        });
    });

    modalReserva.querySelector('[data-reserva-ver-venda]').addEventListener('click', () => {
        const vendaId = modalReserva._aluguel.venda_id;
        fecharModal(modalReserva);
        abrirDetalhesVenda(vendaId);
    });

    modalReserva.querySelector('[data-reserva-editar]').addEventListener('click', () => {
        const dados = modalReserva._aluguel;
        fecharModal(modalReserva);
        abrirFormAluguel({ edicao: dados });
    });

    modalReserva.querySelector('[data-reserva-cancelar]').addEventListener('click', () => {
        const dados = modalReserva._aluguel;
        const opcoes = modalCancelarAluguel.querySelector('[data-cancelar-aluguel-opcoes]');
        const temProximas = dados.serie && dados.serie.proximas > 1;

        modalCancelarAluguel.querySelector('[data-cancelar-aluguel-texto]').textContent =
            `${dados.mesa} · ${dados.data_texto} às ${dados.hora_inicio} (${dados.nome_exibicao}).`;
        opcoes.hidden = !temProximas;
        opcoes.querySelector('input[value="este"]').checked = true;
        if (temProximas) {
            modalCancelarAluguel.querySelector('[data-cancelar-aluguel-este]').textContent = dataCurta(dados.data);
            modalCancelarAluguel.querySelector('[data-cancelar-aluguel-proximos]').textContent =
                `${dados.serie.proximas} datas, até ${dados.serie.ultima}`;
        }
        abrirModal('cancelar-aluguel', modalCancelarAluguel.querySelector('[data-fechar-modal]'));
    });

    const botaoConfirmarCancelamento = modalCancelarAluguel.querySelector('[data-cancelar-aluguel-confirmar]');
    botaoConfirmarCancelamento.addEventListener('click', async () => {
        const dados = modalReserva._aluguel;
        const escolhido = modalCancelarAluguel.querySelector('input[name="escopo_cancelamento"]:checked');
        botaoConfirmarCancelamento.disabled = true;

        const { ok, dados: resposta } = await requisicao(`${urlBase}/alugueis/${dados.id}/cancelar`, {
            metodo: 'POST',
            corpo: { escopo: escolhido ? escolhido.value : 'este' },
        });

        botaoConfirmarCancelamento.disabled = false;
        if (ok) {
            fecharModal(modalCancelarAluguel);
            fecharModal(modalReserva);
            recarregarComMensagem(resposta.message);
        } else {
            mostrarMensagem(mensagemDeErro(resposta), 'erro');
        }
    });

    // =========================================================================
    // Novo aluguel / editar reserva
    // =========================================================================

    const modalAluguel = overlayDe('aluguel');
    const form = modalAluguel.querySelector('[data-form-aluguel]');
    const elAluguel = {
        titulo: form.querySelector('[data-aluguel-titulo]'),
        erros: form.querySelector('[data-aluguel-erros]'),
        mesas: form.querySelector('[data-mesas-opcoes]'),
        semMesas: form.querySelector('[data-sem-mesas]'),
        data: form.querySelector('[data-aluguel-data]'),
        inicio: form.querySelector('[data-aluguel-inicio]'),
        duracoes: form.querySelectorAll('[data-aluguel-duracao]'),
        fimCampo: form.querySelector('[data-fim-campo]'),
        fim: form.querySelector('[data-aluguel-fim]'),
        horarioResumo: form.querySelector('[data-aluguel-horario-resumo]'),
        horarioAviso: form.querySelector('[data-aluguel-horario-aviso]'),
        repetirBloco: form.querySelector('[data-repetir-bloco]'),
        repetir: form.querySelectorAll('[data-aluguel-repetir]'),
        repetirAteCampo: form.querySelector('[data-repetir-ate-campo]'),
        repetirAte: form.querySelector('[data-aluguel-repetir-ate]'),
        repetirDica: form.querySelector('[data-repetir-dica]'),
        tipos: form.querySelectorAll('[data-aluguel-tipo-jogo]'),
        jogo: form.querySelector('[data-aluguel-jogo]'),
        valor: form.querySelector('[data-aluguel-valor]'),
        valorDica: form.querySelector('[data-aluguel-valor-dica]'),
        recalcular: form.querySelector('[data-recalcular-valor]'),
        observacoes: form.querySelector('[data-aluguel-observacoes]'),
        salvar: form.querySelector('[data-aluguel-salvar]'),
    };

    const aluguel = { editandoId: null, mesaAtualId: null, valorManual: false, enviando: false };

    const seletorClienteAluguel = criarSeletorCliente(
        form.querySelector('[data-seletor-cliente="aluguel"]'),
        () => exibirErrosDeCampos(form, {}),
    );

    function mesaEscolhida() {
        const marcada = elAluguel.mesas.querySelector('input:checked');
        return marcada ? config.mesas.find((m) => m.id === Number(marcada.value)) : null;
    }

    function desenharMesasOpcoes(selecionadaId = null) {
        elAluguel.mesas.innerHTML = '';
        // na edição a mesa atual aparece mesmo se tiver sido desativada
        const visiveis = config.mesas.filter((m) => m.ativa || m.id === aluguel.mesaAtualId);
        elAluguel.semMesas.hidden = visiveis.length > 0;

        visiveis.forEach((mesa) => {
            const label = criar('label', 'mesa-opcao');
            const input = criar('input');
            input.type = 'radio';
            input.name = 'mesa_id';
            input.value = mesa.id;
            input.checked = mesa.id === selecionadaId || (selecionadaId === null && visiveis.length === 1);
            input.addEventListener('change', atualizarValorAutomatico);
            const caixa = criar('span', 'mesa-opcao__caixa');
            caixa.append(criar('strong', '', mesa.nome));
            caixa.append(criar('small', '', `${mesa.capacidade} lugares · ${formatarMoeda(mesa.preco_hora)}/h`));
            label.append(input, caixa);
            elAluguel.mesas.append(label);
        });
    }

    function duracaoEscolhida() {
        const marcada = Array.from(elAluguel.duracoes).find((r) => r.checked);
        if (!marcada) return NaN;
        if (marcada.value !== 'outra') return Number(marcada.value);

        const inicio = paraMinutos(elAluguel.inicio.value);
        const fim = paraMinutos(elAluguel.fim.value);
        if (Number.isNaN(inicio) || Number.isNaN(fim)) return NaN;
        return fim > inicio ? fim - inicio : fim + 1440 - inicio;
    }

    function horaFim() {
        const inicio = paraMinutos(elAluguel.inicio.value);
        const duracao = duracaoEscolhida();
        return Number.isNaN(inicio) || Number.isNaN(duracao) ? '' : paraHora(inicio + duracao);
    }

    function repeticaoSemanal() {
        return Array.from(elAluguel.repetir).some((r) => r.checked && r.value === 'semanal');
    }

    function quantidadeDeSemanas() {
        const inicio = lerData(elAluguel.data.value);
        const fim = lerData(elAluguel.repetirAte.value);
        if (!inicio || !fim || fim < inicio) return 0;
        return Math.floor((fim - inicio) / (7 * 86400000)) + 1;
    }

    function atualizarHorario() {
        const duracao = duracaoEscolhida();
        const inicio = paraMinutos(elAluguel.inicio.value);
        elAluguel.fimCampo.hidden = !Array.from(elAluguel.duracoes).some((r) => r.checked && r.value === 'outra');

        let aviso = '';
        if (Number.isNaN(inicio) || Number.isNaN(duracao)) {
            elAluguel.horarioResumo.textContent = '';
        } else {
            const passaDaMeiaNoite = inicio + duracao >= 1440;
            elAluguel.horarioResumo.textContent =
                `Das ${elAluguel.inicio.value} às ${horaFim()} (${textoDuracao(duracao)})${passaDaMeiaNoite ? ' — termina no dia seguinte' : ''}.`;

            // Aviso (não bloqueia) com base em Configurações > Loja
            const faixa = faixaDoDia(elAluguel.data.value);
            if (elAluguel.data.value && !faixa) {
                aviso = 'A loja está fechada neste dia, pelo horário de funcionamento.';
            } else if (faixa && (inicio < faixa[0] || inicio + duracao > faixa[1])) {
                aviso = `Fora do horário de funcionamento (${paraHora(faixa[0])}–${paraHora(faixa[1])}).`;
            }
            if (duracao > config.duracaoMaxima) {
                aviso = `A duração máxima de um aluguel é ${textoDuracao(config.duracaoMaxima)}.`;
            }
        }
        elAluguel.horarioAviso.querySelector('[data-aluguel-horario-aviso-texto]').textContent = aviso;
        elAluguel.horarioAviso.hidden = !aviso;

        atualizarRepeticao();
        atualizarValorAutomatico();
    }

    function atualizarRepeticao() {
        const semanal = repeticaoSemanal();
        elAluguel.repetirAteCampo.hidden = !semanal;
        if (!semanal) return;

        const data = lerData(elAluguel.data.value);
        const semanas = quantidadeDeSemanas();
        elAluguel.repetirDica.classList.toggle('campo__dica--erro', semanas > config.maxSemanas || semanas === 0);

        if (!data || semanas === 0) {
            elAluguel.repetirDica.textContent = 'Escolha uma data final depois da primeira data.';
        } else if (semanas > config.maxSemanas) {
            elAluguel.repetirDica.textContent = `Máximo de ${config.maxSemanas} semanas — escolha uma data mais próxima.`;
        } else {
            elAluguel.repetirDica.textContent =
                `Toda ${DIAS_SEMANA[data.getDay()]} às ${elAluguel.inicio.value || '--:--'} · ${semanas} ${semanas === 1 ? 'data' : 'datas'}.`;
        }
    }

    function valorDaMesa() {
        const mesa = mesaEscolhida();
        const duracao = duracaoEscolhida();
        if (!mesa || Number.isNaN(duracao)) return null;
        return Math.round(mesa.preco_hora * (duracao / 60) * 100) / 100;
    }

    function atualizarValorAutomatico() {
        const mesa = mesaEscolhida();
        const duracao = duracaoEscolhida();
        const calculado = valorDaMesa();

        if (calculado === null) {
            elAluguel.valorDica.textContent = mesa ? '' : 'Escolha a mesa para calcular o valor.';
            elAluguel.recalcular.hidden = true;
            return;
        }

        if (!aluguel.valorManual) elAluguel.valor.value = formatarDecimal(calculado);
        const atual = lerValor(elAluguel.valor.value);
        const diferente = aluguel.valorManual && Math.abs(atual - calculado) > 0.004;

        elAluguel.valorDica.textContent = diferente
            ? `Valor combinado. Pela mesa seria ${formatarMoeda(calculado)} (${textoDuracao(duracao)} × ${formatarMoeda(mesa.preco_hora)}/h).`
            : `${textoDuracao(duracao)} × ${formatarMoeda(mesa.preco_hora)}/h${repeticaoSemanal() ? ', por data' : ''}.`;
        elAluguel.recalcular.hidden = !diferente;
    }

    /**
     * Abertura e fechamento (minutos) do dia da data, pelo horário de
     * funcionamento; null se a loja não abre. Fechar antes de abrir = dia seguinte.
     */
    function faixaDoDia(iso) {
        const data = lerData(iso);
        const horario = data && config.horario ? config.horario[data.getDay()] : null;
        if (!horario || !horario.aberto) return null;
        const abre = paraMinutos(horario.abre);
        const fecha = paraMinutos(horario.fecha);
        return [abre, fecha <= abre ? fecha + 1440 : fecha];
    }

    /**
     * Início sugerido: hoje, a próxima hora cheia (se a loja estiver aberta);
     * outro dia, 18:00 se der tempo antes de fechar — senão, a abertura.
     */
    function horaPadrao(data) {
        const faixa = faixaDoDia(data) || [10 * 60, 22 * 60];
        const cabe = (minutos) => minutos >= faixa[0] && minutos + 60 <= faixa[1];

        if (data === config.hoje) {
            const proxima = (new Date().getHours() + 1) * 60;
            if (cabe(proxima)) return paraHora(proxima);
        }
        return cabe(18 * 60) ? '18:00' : paraHora(faixa[0]);
    }

    /** Marca o chip da duração padrão (ou "Outra", se ela não for 1h–4h). */
    function aplicarDuracaoPadrao() {
        const chip = Array.from(elAluguel.duracoes).find((r) => Number(r.value) === config.duracaoPadrao);
        if (chip) {
            chip.checked = true;
            return;
        }
        elAluguel.duracoes.forEach((r) => { r.checked = r.value === 'outra'; });
        elAluguel.fim.value = paraHora(paraMinutos(elAluguel.inicio.value || '18:00') + config.duracaoPadrao);
    }

    function abrirFormAluguel({ edicao = null, mesaId = null, data = null, hora = null } = {}) {
        form.reset();
        exibirErrosDeCampos(form, {});
        elAluguel.erros.hidden = true;
        aluguel.editandoId = edicao ? edicao.id : null;
        aluguel.mesaAtualId = edicao ? edicao.mesa_id : null;
        aluguel.valorManual = false;
        aluguel.enviando = false;

        elAluguel.titulo.textContent = edicao ? 'Editar reserva' : 'Novo aluguel';
        elAluguel.salvar.textContent = edicao ? 'Salvar alterações' : 'Reservar mesa';
        elAluguel.repetirBloco.hidden = Boolean(edicao);

        if (edicao) {
            desenharMesasOpcoes(edicao.mesa_id);
            elAluguel.data.value = edicao.data;
            elAluguel.inicio.value = edicao.hora_inicio;
            const chip = Array.from(elAluguel.duracoes).find((r) => Number(r.value) === edicao.duracao_minutos);
            if (chip) {
                chip.checked = true;
            } else {
                elAluguel.duracoes.forEach((r) => { r.checked = r.value === 'outra'; });
                elAluguel.fim.value = edicao.hora_fim;
            }
            seletorClienteAluguel.definir(edicao.cliente
                ? { tipo: 'cliente', cliente: edicao.cliente }
                : { tipo: 'avulso', nome: edicao.responsavel || '' });
            elAluguel.tipos.forEach((r) => { r.checked = r.value === edicao.tipo_jogo; });
            elAluguel.jogo.value = edicao.jogo || '';
            elAluguel.observacoes.value = edicao.observacoes || '';
            elAluguel.valor.value = formatarDecimal(edicao.valor);
            aluguel.valorManual = true;
            atualizarHorario();
            // se o valor salvo é igual ao calculado pela mesa, volta a seguir o cálculo
            const calculado = valorDaMesa();
            if (calculado !== null && Math.abs(calculado - edicao.valor) < 0.005) aluguel.valorManual = false;
            atualizarValorAutomatico();
        } else {
            desenharMesasOpcoes(mesaId);
            // um dia passado na agenda não faz sentido como padrão: usa hoje
            const dia = data || (config.dia < config.hoje ? config.hoje : config.dia);
            elAluguel.data.value = dia;
            elAluguel.inicio.value = hora || horaPadrao(dia);
            aplicarDuracaoPadrao();
            // padrão: 8 datas, ou menos se o limite de semanas for menor
            elAluguel.repetirAte.value = somarDias(dia, 7 * (Math.min(8, config.maxSemanas) - 1));
            seletorClienteAluguel.definir(null);
            atualizarHorario();
        }

        abrirModal('aluguel');
    }

    function validarAluguelNoNavegador() {
        const erros = {};
        if (!mesaEscolhida()) erros.mesa_id = ['Escolha a mesa.'];
        if (!elAluguel.data.value) erros.data = ['Informe a data.'];
        if (!elAluguel.inicio.value) erros.hora_inicio = ['Informe o horário de início.'];
        if (Number.isNaN(duracaoEscolhida())) erros.hora_fim = ['Informe o horário de término.'];
        else if (duracaoEscolhida() > config.duracaoMaxima) erros.hora_fim = [`O aluguel pode durar no máximo ${textoDuracao(config.duracaoMaxima)}.`];
        if (!seletorClienteAluguel.valor()) erros.responsavel = ['Escolha um cliente ou digite o nome de quem está alugando.'];
        if (!Array.from(elAluguel.tipos).some((r) => r.checked)) erros.tipo_jogo = ['Escolha o tipo de jogo.'];
        const valor = lerValor(elAluguel.valor.value);
        if (Number.isNaN(valor) || valor < 0) erros.valor = ['Informe um valor válido.'];
        if (!aluguel.editandoId && repeticaoSemanal()) {
            const semanas = quantidadeDeSemanas();
            if (semanas === 0) erros.repetir_ate = ['Escolha uma data final depois da primeira data.'];
            if (semanas > config.maxSemanas) erros.repetir_ate = [`Repita por no máximo ${config.maxSemanas} semanas.`];
        }
        return erros;
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (aluguel.enviando) return;

        const erros = validarAluguelNoNavegador();
        exibirErrosDeCampos(form, erros);
        elAluguel.erros.hidden = true;
        if (Object.keys(erros).length) {
            const primeiro = form.querySelector('[data-erro]:not([hidden])');
            if (primeiro) primeiro.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const quem = seletorClienteAluguel.valor();
        const tipo = Array.from(elAluguel.tipos).find((r) => r.checked);
        const corpo = {
            mesa_id: mesaEscolhida().id,
            data: elAluguel.data.value,
            hora_inicio: elAluguel.inicio.value,
            hora_fim: horaFim(),
            cliente_id: quem.tipo === 'cliente' ? quem.cliente.id : null,
            responsavel: quem.tipo === 'avulso' ? quem.nome : null,
            valor: lerValor(elAluguel.valor.value),
            tipo_jogo: tipo.value,
            jogo: elAluguel.jogo.value.trim() || null,
            observacoes: elAluguel.observacoes.value.trim() || null,
        };
        if (!aluguel.editandoId) {
            corpo.repetir = repeticaoSemanal() ? 'semanal' : 'nao';
            corpo.repetir_ate = repeticaoSemanal() ? elAluguel.repetirAte.value : null;
        }

        aluguel.enviando = true;
        elAluguel.salvar.disabled = true;
        const textoSalvar = elAluguel.salvar.textContent;
        elAluguel.salvar.textContent = 'Salvando…';

        const { ok, dados } = await requisicao(
            aluguel.editandoId ? `${urlBase}/alugueis/${aluguel.editandoId}` : `${urlBase}/alugueis`,
            { metodo: aluguel.editandoId ? 'PUT' : 'POST', corpo },
        );

        if (ok) {
            fecharModal(modalAluguel);
            recarregarComMensagem(dados.message, urlAgenda(dados.dia));
            return;
        }

        aluguel.enviando = false;
        elAluguel.salvar.disabled = false;
        elAluguel.salvar.textContent = textoSalvar;
        exibirErrosDeCampos(form, dados.errors || {});
        elAluguel.erros.textContent = mensagemDeErro(dados);
        elAluguel.erros.hidden = false;
        elAluguel.erros.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    elAluguel.data.addEventListener('change', atualizarHorario);
    elAluguel.inicio.addEventListener('change', atualizarHorario);
    elAluguel.fim.addEventListener('change', atualizarHorario);
    elAluguel.duracoes.forEach((r) => r.addEventListener('change', () => {
        if (r.value === 'outra' && !elAluguel.fim.value) {
            elAluguel.fim.value = paraHora(paraMinutos(elAluguel.inicio.value || '18:00') + 150);
        }
        atualizarHorario();
    }));
    elAluguel.repetir.forEach((r) => r.addEventListener('change', () => {
        atualizarRepeticao();
        atualizarValorAutomatico();
    }));
    elAluguel.repetirAte.addEventListener('change', atualizarRepeticao);
    elAluguel.tipos.forEach((r) => r.addEventListener('change', () => exibirErrosDeCampos(form, {})));
    elAluguel.valor.addEventListener('input', () => {
        aluguel.valorManual = true;
        atualizarValorAutomatico();
    });
    elAluguel.valor.addEventListener('blur', () => {
        const valor = lerValor(elAluguel.valor.value);
        if (!Number.isNaN(valor)) elAluguel.valor.value = formatarDecimal(valor);
    });
    elAluguel.recalcular.addEventListener('click', () => {
        aluguel.valorManual = false;
        atualizarValorAutomatico();
    });

    // =========================================================================
    // Mesas
    // =========================================================================

    const modalMesas = overlayDe('mesas');
    const formMesa = modalMesas.querySelector('[data-form-mesa]');
    const elMesa = {
        titulo: formMesa.querySelector('[data-mesa-form-titulo]'),
        nome: formMesa.querySelector('[data-mesa-nome]'),
        capacidade: formMesa.querySelector('[data-mesa-capacidade]'),
        preco: formMesa.querySelector('[data-mesa-preco]'),
        salvar: formMesa.querySelector('[data-mesa-salvar]'),
        salvarTexto: formMesa.querySelector('[data-mesa-salvar-texto]'),
        cancelarEdicao: formMesa.querySelector('[data-mesa-cancelar-edicao]'),
        lista: modalMesas.querySelector('[data-mesas-lista]'),
        vazio: modalMesas.querySelector('[data-mesas-vazio]'),
    };
    const mesas = { editandoId: null, alteradas: false };

    function prepararFormMesa(mesa = null) {
        mesas.editandoId = mesa ? mesa.id : null;
        exibirErrosDeCampos(formMesa, {});
        elMesa.titulo.textContent = mesa ? `Editar ${mesa.nome}` : 'Nova mesa';
        elMesa.salvarTexto.textContent = mesa ? 'Salvar mesa' : 'Adicionar mesa';
        elMesa.salvar.querySelector('i').className = `bi ${mesa ? 'bi-check-lg' : 'bi-plus-lg'}`;
        elMesa.cancelarEdicao.hidden = !mesa;
        elMesa.nome.value = mesa ? mesa.nome : `Mesa ${config.mesas.length + 1}`;
        elMesa.capacidade.value = mesa ? mesa.capacidade : 4;
        elMesa.preco.value = mesa ? formatarDecimal(mesa.preco_hora) : '';
    }

    function desenharMesas() {
        elMesa.lista.innerHTML = '';
        elMesa.vazio.hidden = config.mesas.length > 0;

        config.mesas.forEach((mesa) => {
            const li = criar('li', `mesa-item ${mesa.ativa ? '' : 'mesa-item--inativa'}`);
            const info = criar('span', 'mesa-item__info');
            const nome = criar('strong', '', mesa.nome);
            if (!mesa.ativa) nome.append(criar('span', 'status-badge status-badge--inativo', 'Inativa'));
            info.append(nome, criar('small', '', `${mesa.capacidade} lugares · ${formatarMoeda(mesa.preco_hora)}/h`));

            const acoes = criar('span', 'mesa-item__acoes');
            const botao = (classe, rotulo, aoClicar) => {
                const b = criar('button', 'mesa-item__botao');
                b.type = 'button';
                b.title = rotulo;
                b.setAttribute('aria-label', `${rotulo} ${mesa.nome}`);
                b.append(icone(classe));
                b.addEventListener('click', aoClicar);
                acoes.append(b);
                return b;
            };
            botao('bi-pencil', 'Editar', () => {
                prepararFormMesa(mesa);
                elMesa.nome.focus();
            });
            botao(mesa.ativa ? 'bi-pause-circle' : 'bi-play-circle', mesa.ativa ? 'Desativar' : 'Ativar', () => salvarMesa(mesa.id, { ...mesa, ativa: !mesa.ativa }));
            botao('bi-trash3', 'Excluir', () => excluirMesa(mesa)).classList.add('mesa-item__botao--perigo');

            li.append(info, acoes);
            elMesa.lista.append(li);
        });
    }

    async function salvarMesa(id, corpo) {
        const { ok, dados } = await requisicao(id ? `${urlBase}/mesas/${id}` : `${urlBase}/mesas`, {
            metodo: id ? 'PUT' : 'POST',
            corpo,
        });

        if (!ok) {
            exibirErrosDeCampos(formMesa, dados.errors || {});
            if (!dados.errors) mostrarMensagem(mensagemDeErro(dados), 'erro');
            return false;
        }

        config.mesas = dados.mesas;
        mesas.alteradas = true;
        desenharMesas();
        mostrarMensagem(dados.message);
        return true;
    }

    async function excluirMesa(mesa) {
        const confirmou = await confirmar({
            titulo: `Excluir ${mesa.nome}?`,
            texto: 'Só mesas sem nenhuma reserva podem ser excluídas.',
            textoOk: 'Excluir',
        });
        if (!confirmou) return;

        const { ok, dados } = await requisicao(`${urlBase}/mesas/${mesa.id}`, { metodo: 'DELETE' });
        if (!ok) {
            mostrarMensagem(mensagemDeErro(dados), 'erro');
            return;
        }
        config.mesas = dados.mesas;
        mesas.alteradas = true;
        if (mesas.editandoId === mesa.id) prepararFormMesa();
        desenharMesas();
        mostrarMensagem(dados.message);
    }

    function abrirMesas() {
        mesas.alteradas = false;
        prepararFormMesa();
        desenharMesas();
        abrirModal('mesas', config.mesas.length === 0 ? elMesa.nome : null);
    }

    formMesa.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const preco = lerValor(elMesa.preco.value);
        const corpo = {
            nome: elMesa.nome.value.trim(),
            capacidade: Number(elMesa.capacidade.value),
            preco_hora: Number.isNaN(preco) ? null : preco,
        };
        elMesa.salvar.disabled = true;
        const salvou = await salvarMesa(mesas.editandoId, corpo);
        elMesa.salvar.disabled = false;
        if (salvou) {
            prepararFormMesa();
            elMesa.nome.focus();
        }
    });

    elMesa.cancelarEdicao.addEventListener('click', () => prepararFormMesa());
    elMesa.preco.addEventListener('blur', () => {
        const valor = lerValor(elMesa.preco.value);
        if (!Number.isNaN(valor)) elMesa.preco.value = formatarDecimal(valor);
    });

    modalMesas.addEventListener('modal:fechado', () => {
        if (!mesas.alteradas) return;
        if (!modalAluguel.hidden) {
            // aberto por cima do formulário de aluguel: atualiza as opções lá
            const atual = mesaEscolhida();
            desenharMesasOpcoes(atual ? atual.id : null);
            atualizarValorAutomatico();
        } else if (pagina.dataset.aba === 'alugueis') {
            window.location.reload(); // agenda e quadro de ocupação dependem das mesas
        }
    });

    // =========================================================================
    // Ligações gerais (delegação: a lista é trocada via AJAX)
    // =========================================================================

    pagina.addEventListener('click', (evento) => {
        const alvo = evento.target;

        if (alvo.closest('[data-abrir-nova-venda]')) {
            abrirNovaVenda();
            return;
        }

        const linhaVenda = alvo.closest('[data-abrir-venda]');
        if (linhaVenda) {
            abrirDetalhesVenda(linhaVenda.dataset.abrirVenda);
            return;
        }

        const reserva = alvo.closest('[data-abrir-aluguel]');
        if (reserva) {
            abrirDetalhesAluguel(reserva.dataset.abrirAluguel);
            return;
        }

        if (alvo.closest('[data-abrir-novo-aluguel]')) {
            abrirFormAluguel();
            return;
        }

        if (alvo.closest('[data-abrir-mesas]')) {
            abrirMesas();
            return;
        }

        // Quadro de ocupação: clique num horário livre abre "Novo aluguel"
        // com a mesa e o horário (arredondado para a meia hora anterior).
        const trilho = alvo.closest('[data-trilho]');
        if (trilho) {
            const quadro = trilho.closest('[data-ocupacao]');
            const horaInicial = Number(quadro.dataset.horaInicial);
            const horaFinal = Number(quadro.dataset.horaFinal);
            const caixa = trilho.getBoundingClientRect();
            const proporcao = Math.min(1, Math.max(0, (evento.clientX - caixa.left) / caixa.width));
            const minutos = Math.floor((horaInicial * 60 + proporcao * (horaFinal - horaInicial) * 60) / 30) * 30;
            abrirFormAluguel({
                mesaId: Number(trilho.dataset.mesa),
                data: config.dia,
                hora: paraHora(Math.min(minutos, 23 * 60 + 30)),
            });
        }
    });

    iniciarListaVendas();

    // ?nova=1 (atalho "adicionar venda" da página inicial)
    if (pagina.hasAttribute('data-iniciar-nova-venda')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('nova');
        window.history.replaceState(null, '', url);
        abrirNovaVenda();
    }

    // ?mesas=1 (atalho "Gerenciar mesas" de Configurações)
    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get('mesas') === '1') {
        const url = new URL(window.location.href);
        url.searchParams.delete('mesas');
        window.history.replaceState(null, '', url);
        abrirMesas();
    }
}

document.addEventListener('DOMContentLoaded', iniciarPaginaVendas);
