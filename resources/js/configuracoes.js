/**
 * Tela de Configurações: formulários por seção (com aviso de alterações não
 * salvas), editores de listas (chips), tipos de jogo, horário de
 * funcionamento, cadastros salvos na hora (categorias e jogos de carta),
 * logo da loja, exportações e o registro de atividades.
 *
 * Mesmo estilo de clientes.js e vendas.js: JS puro, só roda na página de
 * configurações. O tema (Aparência) continua no app.js.
 */

import { ativarFocusTrap, desativarFocusTrap } from './comum';

const ICONES_MENSAGEM = { sucesso: 'bi-check-circle-fill', erro: 'bi-exclamation-circle-fill' };

// Mesmos limites validados em ConfiguracaoController.
const LIMITES_CHIPS = { 'valores_rapidos[]': 6, 'motivos[]': 12, 'estados[]': 20, 'idiomas[]': 20 };

function criar(tag, classe = '', texto = null) {
    const el = document.createElement(tag);
    if (classe) el.className = classe;
    if (texto !== null) el.textContent = texto;
    return el;
}

function icone(classe) {
    const i = criar('i', `bi ${classe}`);
    i.setAttribute('aria-hidden', 'true');
    return i;
}

/** "11987654321" → "(11) 98765-4321" enquanto digita. */
function mascaraTelefone(valor) {
    const d = valor.replace(/\D/g, '').slice(0, 11);
    if (d.length <= 2) return d ? `(${d}` : '';
    const meio = d.length > 10 ? 5 : 4;
    const resto = d.slice(2);
    return `(${d.slice(0, 2)}) ${resto.slice(0, meio)}${resto.length > meio ? '-' + resto.slice(meio) : ''}`;
}

/** "12345678000190" → "12.345.678/0001-90" enquanto digita. */
function mascaraCnpj(valor) {
    const d = valor.replace(/\D/g, '').slice(0, 14);
    return d
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

function iniciarPaginaConfiguracoes() {
    const pagina = document.querySelector('[data-config-page]');
    if (!pagina) return;

    const urlBase = pagina.dataset.urlBase;
    const mensagemEl = pagina.querySelector('[data-mensagem]');
    const modalConfirmar = pagina.querySelector('[data-modal-confirmar]');

    // ---- Comunicação ----------------------------------------------------------

    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    /** fetch que nunca lança: devolve {ok, status, dados}. corpo: FormData ou objeto. */
    async function requisicao(url, { metodo = 'GET', corpo = null } = {}) {
        const opcoes = { method: metodo, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
        if (metodo !== 'GET') opcoes.headers['X-CSRF-TOKEN'] = csrf();
        if (corpo instanceof FormData) {
            opcoes.body = corpo;
        } else if (corpo) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(corpo);
        }

        try {
            const resposta = await fetch(url, opcoes);
            let dados = {};
            try { dados = await resposta.json(); } catch (e) { dados = {}; }
            return { ok: resposta.ok, status: resposta.status, dados };
        } catch (erro) {
            return { ok: false, status: 0, dados: { message: 'Sem conexão com o servidor. Tente de novo.' } };
        }
    }

    function mensagemDeErro(dados) {
        if (dados && dados.errors) {
            const primeiro = Object.values(dados.errors)[0];
            if (primeiro && primeiro[0]) return primeiro[0];
        }
        return (dados && dados.message) || 'Algo deu errado. Tente de novo.';
    }

    function mostrarMensagem(texto, tipo = 'sucesso') {
        if (!mensagemEl || !texto) return;
        mensagemEl.innerHTML = '';
        mensagemEl.append(icone(ICONES_MENSAGEM[tipo]), criar('span', '', texto));
        mensagemEl.className = `mensagem-flutuante mensagem-flutuante--${tipo}`;
        mensagemEl.hidden = false;
        window.clearTimeout(mensagemEl._timeout);
        mensagemEl._timeout = window.setTimeout(() => { mensagemEl.hidden = true; }, tipo === 'erro' ? 6000 : 3500);
    }

    /** Confirmação com o modal genérico de Clientes. */
    function confirmar({ titulo, texto, textoOk }) {
        return new Promise((resolve) => {
            if (!modalConfirmar) {
                resolve(window.confirm(texto));
                return;
            }
            const ok = modalConfirmar.querySelector('[data-confirmar-ok]');
            const cancelar = modalConfirmar.querySelector('[data-confirmar-cancelar]');
            const anterior = document.activeElement;
            modalConfirmar.querySelector('[data-confirmar-titulo]').textContent = titulo;
            modalConfirmar.querySelector('[data-confirmar-texto]').textContent = texto;
            ok.textContent = textoOk;
            cancelar.textContent = 'Voltar';

            const fim = (resultado) => {
                modalConfirmar.hidden = true;
                document.body.style.overflow = '';
                desativarFocusTrap(modalConfirmar.firstElementChild);
                ok.removeEventListener('click', sim);
                cancelar.removeEventListener('click', nao);
                modalConfirmar.removeEventListener('click', fora);
                document.removeEventListener('keydown', esc);
                if (anterior && anterior.focus) anterior.focus();
                resolve(resultado);
            };
            const sim = () => fim(true);
            const nao = () => fim(false);
            const fora = (e) => { if (e.target === modalConfirmar) fim(false); };
            const esc = (e) => { if (e.key === 'Escape') fim(false); };

            ok.addEventListener('click', sim);
            cancelar.addEventListener('click', nao);
            modalConfirmar.addEventListener('click', fora);
            document.addEventListener('keydown', esc);
            modalConfirmar.hidden = false;
            document.body.style.overflow = 'hidden';
            ativarFocusTrap(modalConfirmar.firstElementChild);
            cancelar.focus();
        });
    }

    /**
     * Mostra erros do servidor ao lado de cada campo. "estados.2" cai no
     * erro de "estados" se não houver um lugar só para o item.
     */
    function exibirErros(raiz, erros = {}) {
        raiz.querySelectorAll('[data-erro]').forEach((span) => {
            span.hidden = true;
            span.textContent = '';
        });
        Object.entries(erros).forEach(([chave, mensagens]) => {
            let alvo = null;
            for (let k = chave; k && !alvo; k = k.includes('.') ? k.slice(0, k.lastIndexOf('.')) : '') {
                alvo = raiz.querySelector(`[data-erro="${CSS.escape(k)}"]`);
            }
            if (alvo && alvo.hidden) {
                alvo.textContent = mensagens[0];
                alvo.hidden = false;
            }
        });
    }

    // =========================================================================
    // Formulários de seção: alterações não salvas, salvar, descartar
    // =========================================================================

    const formularios = Array.from(pagina.querySelectorAll('[data-config-form]'));

    const serializar = (form) => new URLSearchParams(new FormData(form)).toString();

    /** Grava os valores atuais como atributos — o innerHTML vira o novo "original". */
    function fixarValores(form) {
        form.querySelectorAll('input, select, textarea').forEach((campo) => {
            if (campo.type === 'checkbox' || campo.type === 'radio') {
                campo.toggleAttribute('checked', campo.checked);
            } else if (campo.tagName === 'SELECT') {
                Array.from(campo.options).forEach((o) => o.toggleAttribute('selected', o.selected));
            } else if (campo.type !== 'file') {
                campo.setAttribute('value', campo.value);
            }
        });
        form._original = form.innerHTML;
        form._salvo = serializar(form);
    }

    function atualizarEstado(form) {
        const sujo = serializar(form) !== form._salvo;
        form.classList.toggle('config-card--alterado', sujo);
        form.querySelector('[data-salvar]').disabled = !sujo || form._enviando;
        form.querySelector('[data-descartar]').hidden = !sujo;
        form.querySelector('[data-status]').textContent = form._enviando ? 'Salvando…' : (sujo ? 'Alterações não salvas' : 'Tudo salvo');
    }

    /** Ajustes visuais que dependem dos valores (rodam ao carregar e após descartar). */
    function sincronizar(form) {
        form.querySelectorAll('[data-horario-dia]').forEach((dia) => {
            dia.classList.toggle('horario__dia--fechado', !dia.querySelector('[data-horario-aberto]').checked);
        });
        form.querySelectorAll('[data-liga]').forEach((chave) => {
            const alvo = pagina.querySelector(chave.dataset.liga);
            if (alvo) alvo.classList.toggle('config-linha--desligada', !chave.checked);
        });
        form.querySelectorAll('[data-tipo-jogo]').forEach(atualizarPreviaTipo);
        form.querySelectorAll('[data-lista-chips]').forEach(atualizarLimiteChips);
    }

    formularios.forEach((form) => {
        sincronizar(form);
        fixarValores(form);
        atualizarEstado(form);

        form.addEventListener('input', () => atualizarEstado(form));
        form.addEventListener('change', () => {
            sincronizar(form);
            atualizarEstado(form);
        });

        form.querySelector('[data-descartar]').addEventListener('click', () => {
            form.innerHTML = form._original;
            sincronizar(form);
            exibirErros(form, {});
            atualizarEstado(form);
        });

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            if (form._enviando) return;

            reindexarTipos(form);
            form._enviando = true;
            atualizarEstado(form);

            const { ok, dados } = await requisicao(form.getAttribute('action'), { metodo: 'POST', corpo: new FormData(form) });

            form._enviando = false;
            if (ok) {
                exibirErros(form, {});
                aplicarRespostaSalva(form, dados);
                fixarValores(form);
                mostrarMensagem(dados.message);
            } else {
                exibirErros(form, dados.errors || {});
                mostrarMensagem(mensagemDeErro(dados), 'erro');
                const primeiro = form.querySelector('[data-erro]:not([hidden])');
                if (primeiro) primeiro.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            atualizarEstado(form);
        });
    });

    /** Depois de salvar: tipos novos ganham a chave criada pelo servidor. */
    function aplicarRespostaSalva(form, dados) {
        if (!dados.tipos) return;
        form.querySelectorAll('[data-tipo-jogo]').forEach((linha, i) => {
            const chave = linha.querySelector('[data-tipo-campo="chave"]');
            if (dados.tipos[i]) chave.value = dados.tipos[i].chave;
        });
    }

    window.addEventListener('beforeunload', (evento) => {
        if (formularios.some((form) => serializar(form) !== form._salvo)) {
            evento.preventDefault();
            evento.returnValue = '';
        }
    });

    // Máscaras de CNPJ e telefone
    pagina.addEventListener('input', (evento) => {
        const mascara = evento.target.dataset && evento.target.dataset.mascara;
        if (mascara === 'telefone') evento.target.value = mascaraTelefone(evento.target.value);
        if (mascara === 'cnpj') evento.target.value = mascaraCnpj(evento.target.value);
    });

    // =========================================================================
    // Horário de funcionamento
    // =========================================================================

    pagina.querySelectorAll('[data-copiar-horario]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const form = botao.closest('form');
            const segunda = form.querySelector('[data-horario-dia="1"]');
            const abre = segunda.querySelector('[data-horario-abre]').value;
            const fecha = segunda.querySelector('[data-horario-fecha]').value;
            form.querySelectorAll('[data-horario-dia]').forEach((dia) => {
                if (!dia.querySelector('[data-horario-aberto]').checked) return;
                dia.querySelector('[data-horario-abre]').value = abre;
                dia.querySelector('[data-horario-fecha]').value = fecha;
            });
            atualizarEstado(form);
            mostrarMensagem(`Horário ${abre}–${fecha} aplicado aos dias abertos. Lembre de salvar.`);
        });
    });

    // =========================================================================
    // Tipos de jogo (lista editável dentro do formulário de aluguéis)
    // =========================================================================

    function atualizarPreviaTipo(linha) {
        const nome = linha.querySelector('[data-tipo-campo="nome"]').value.trim();
        const cor = linha.querySelector('[data-tipo-campo="cor"]').value;
        const iconeTipo = linha.querySelector('[data-tipo-campo="icone"]').value;
        const previa = linha.querySelector('[data-tipo-previa]');
        previa.className = `jogo-chip jogo-chip--${cor} tipo-jogo__previa`;
        previa.querySelector('[data-tipo-previa-icone]').className = `bi ${iconeTipo}`;
        previa.querySelector('[data-tipo-previa-nome]').textContent = nome || 'Novo tipo';
    }

    /** Índices sequenciais (tipos[0], tipos[1]...) para os erros do servidor baterem com as linhas. */
    function reindexarTipos(form) {
        form.querySelectorAll('[data-tipos-jogo] [data-tipo-jogo]').forEach((linha, i) => {
            linha.querySelectorAll('[name^="tipos["]').forEach((campo) => {
                campo.name = campo.name.replace(/^tipos\[[^\]]*\]/, `tipos[${i}]`);
            });
            linha.querySelector('[data-erro]').dataset.erro = `tipos.${i}.nome`;
        });
    }

    pagina.addEventListener('input', (evento) => {
        const linha = evento.target.closest('[data-tipo-jogo]');
        if (linha) atualizarPreviaTipo(linha);
    });

    pagina.addEventListener('click', (evento) => {
        const adicionar = evento.target.closest('[data-adicionar-tipo]');
        if (adicionar) {
            const form = adicionar.closest('form');
            const lista = form.querySelector('[data-tipos-jogo]');
            const modelo = form.querySelector('[data-tipo-modelo]');
            const html = modelo.innerHTML.replaceAll('__I__', String(lista.children.length));
            lista.insertAdjacentHTML('beforeend', html);
            const nova = lista.lastElementChild;
            atualizarPreviaTipo(nova);
            nova.querySelector('[data-tipo-campo="nome"]').focus();
            atualizarEstado(form);
            return;
        }

        const remover = evento.target.closest('[data-remover-tipo]');
        if (remover && !remover.disabled) {
            const form = remover.closest('form');
            remover.closest('[data-tipo-jogo]').remove();
            atualizarEstado(form);
        }
    });

    // =========================================================================
    // Listas em chips (estados, idiomas, valores rápidos, motivos)
    // =========================================================================

    function atualizarLimiteChips(lista) {
        const limite = LIMITES_CHIPS[lista.dataset.nome];
        const total = lista.querySelectorAll('.lista-chips__item').length;
        const cheio = limite && total >= limite;
        lista.querySelector('[data-lista-chips-entrada]').disabled = cheio;
        lista.querySelector('[data-lista-chips-adicionar]').disabled = cheio;
        lista.querySelector('[data-lista-chips-entrada]').placeholder = cheio
            ? `Limite de ${limite} itens`
            : lista.querySelector('[data-lista-chips-entrada]').getAttribute('aria-label');
    }

    function adicionarChip(lista) {
        const entrada = lista.querySelector('[data-lista-chips-entrada]');
        let valor = entrada.value.trim();
        if (!valor) return;

        if (lista.hasAttribute('data-numerico')) {
            const numero = Number(valor.replace(',', '.'));
            if (!Number.isInteger(numero) || numero < 1) {
                mostrarMensagem('Use um número inteiro maior que zero.', 'erro');
                return;
            }
            valor = String(numero);
        }

        const existentes = Array.from(lista.querySelectorAll('input[type="hidden"]')).map((i) => i.value.toLowerCase());
        if (existentes.includes(valor.toLowerCase())) {
            mostrarMensagem(`“${valor}” já está na lista.`, 'erro');
            entrada.select();
            return;
        }

        const prefixo = lista.dataset.prefixo || '';
        const item = criar('li', 'lista-chips__item');
        const oculto = criar('input');
        oculto.type = 'hidden';
        oculto.name = lista.dataset.nome;
        oculto.value = valor;
        const remover = criar('button', 'lista-chips__remover');
        remover.type = 'button';
        remover.dataset.removerChip = '';
        remover.setAttribute('aria-label', `Remover ${prefixo}${valor}`);
        remover.append(icone('bi-x'));
        item.append(criar('span', '', `${prefixo}${valor}`), oculto, remover);
        lista.querySelector('[data-lista-chips-itens]').append(item);

        entrada.value = '';
        entrada.focus();
        atualizarLimiteChips(lista);
        atualizarEstado(lista.closest('form'));
    }

    pagina.addEventListener('click', (evento) => {
        const adicionar = evento.target.closest('[data-lista-chips-adicionar]');
        if (adicionar) {
            adicionarChip(adicionar.closest('[data-lista-chips]'));
            return;
        }
        const remover = evento.target.closest('[data-remover-chip]');
        if (remover) {
            const lista = remover.closest('[data-lista-chips]');
            remover.closest('.lista-chips__item').remove();
            atualizarLimiteChips(lista);
            atualizarEstado(lista.closest('form'));
            lista.querySelector('[data-lista-chips-entrada]').focus();
        }
    });

    pagina.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Enter' || !evento.target.matches('[data-lista-chips-entrada]')) return;
        evento.preventDefault(); // Enter adiciona o item, não envia o formulário
        adicionarChip(evento.target.closest('[data-lista-chips]'));
    });

    // =========================================================================
    // Cadastros salvos na hora: categorias e jogos de carta
    // =========================================================================

    pagina.querySelectorAll('[data-cadastro]').forEach((cadastro) => {
        const url = cadastro.dataset.url;
        const singular = cadastro.dataset.singular;
        const plural = cadastro.dataset.plural;
        let itens = JSON.parse(cadastro.dataset.itens || '[]');
        let editando = null;

        const lista = criar('ul', 'config-cadastro__lista');
        const vazio = criar('p', 'perfil__vazio', 'Nada cadastrado ainda.');
        const novo = criar('form', 'config-cadastro__novo');
        novo.noValidate = true;
        const campoNovo = criar('input');
        campoNovo.type = 'text';
        campoNovo.maxLength = 100;
        campoNovo.placeholder = cadastro.dataset.placeholder;
        campoNovo.setAttribute('aria-label', cadastro.dataset.placeholder);
        const botaoNovo = criar('button', 'botao botao--neutro');
        botaoNovo.type = 'submit';
        botaoNovo.append(icone('bi-plus-lg'), document.createTextNode(' Adicionar'));
        novo.append(campoNovo, botaoNovo);
        cadastro.append(lista, vazio, novo);

        async function enviar(metodo, endereco, corpo) {
            const { ok, dados } = await requisicao(endereco, { metodo, corpo });
            if (!ok) {
                mostrarMensagem(mensagemDeErro(dados), 'erro');
                return false;
            }
            itens = dados.itens;
            editando = null;
            desenhar();
            mostrarMensagem(dados.message);
            return true;
        }

        function desenhar() {
            lista.innerHTML = '';
            vazio.hidden = itens.length > 0;

            itens.forEach((item) => {
                const li = criar('li', 'config-cadastro__item');

                if (editando === item.id) {
                    const formEdicao = criar('form', 'config-cadastro__edicao');
                    const campo = criar('input');
                    campo.type = 'text';
                    campo.value = item.nome;
                    campo.maxLength = 100;
                    campo.setAttribute('aria-label', `Novo nome para ${item.nome}`);
                    const salvar = criar('button', 'botao botao--principal');
                    salvar.type = 'submit';
                    salvar.textContent = 'Salvar';
                    const cancelar = criar('button', 'botao-texto');
                    cancelar.type = 'button';
                    cancelar.textContent = 'Cancelar';
                    cancelar.addEventListener('click', () => { editando = null; desenhar(); });
                    campo.addEventListener('keydown', (e) => { if (e.key === 'Escape') { editando = null; desenhar(); } });
                    formEdicao.addEventListener('submit', (e) => {
                        e.preventDefault();
                        enviar('PUT', `${url}/${item.id}`, { nome: campo.value });
                    });
                    formEdicao.append(campo, salvar, cancelar);
                    li.append(formEdicao);
                    lista.append(li);
                    window.requestAnimationFrame(() => campo.select());
                    return;
                }

                const info = criar('span', 'config-cadastro__info');
                info.append(criar('strong', '', item.nome));
                info.append(criar('small', '', item.quantidade
                    ? `${item.quantidade} ${item.quantidade === 1 ? singular : plural}`
                    : `nenhum${singular === 'carta' ? 'a' : ''} ${singular}`));

                const acoes = criar('span', 'config-cadastro__acoes');
                const editar = criar('button', 'config-icone-botao');
                editar.type = 'button';
                editar.title = 'Renomear';
                editar.setAttribute('aria-label', `Renomear ${item.nome}`);
                editar.append(icone('bi-pencil'));
                editar.addEventListener('click', () => { editando = item.id; desenhar(); });

                const excluir = criar('button', 'config-icone-botao config-icone-botao--perigo');
                excluir.type = 'button';
                excluir.disabled = item.quantidade > 0;
                excluir.title = item.quantidade > 0 ? `Tem ${plural} — não pode ser excluído` : 'Excluir';
                excluir.setAttribute('aria-label', `Excluir ${item.nome}`);
                excluir.append(icone('bi-trash3'));
                excluir.addEventListener('click', async () => {
                    const certo = await confirmar({ titulo: `Excluir “${item.nome}”?`, texto: 'Essa ação não pode ser desfeita.', textoOk: 'Excluir' });
                    if (certo) enviar('DELETE', `${url}/${item.id}`);
                });

                acoes.append(editar, excluir);
                li.append(info, acoes);
                lista.append(li);
            });
        }

        novo.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            if (!campoNovo.value.trim()) {
                campoNovo.focus();
                return;
            }
            botaoNovo.disabled = true;
            if (await enviar('POST', url, { nome: campoNovo.value })) campoNovo.value = '';
            botaoNovo.disabled = false;
            campoNovo.focus();
        });

        desenhar();
    });

    // =========================================================================
    // Logo da loja (salva na hora)
    // =========================================================================

    const blocoLogo = pagina.querySelector('[data-config-logo]');
    if (blocoLogo) {
        const arquivo = blocoLogo.querySelector('[data-logo-arquivo]');
        const imagem = blocoLogo.querySelector('[data-logo-imagem]');
        const legenda = blocoLogo.querySelector('[data-logo-legenda]');
        const remover = blocoLogo.querySelector('[data-logo-remover]');

        const aplicar = (url) => {
            const topo = document.querySelector('.topbar__logo');
            const src = url || blocoLogo.dataset.logoPadrao;
            imagem.src = src;
            legenda.textContent = url ? 'Logo enviada pela loja.' : 'Usando a logo padrão do sistema.';
            remover.hidden = !url;
            if (topo) {
                topo.querySelector('img').src = src;
                topo.classList.toggle('topbar__logo--personalizada', Boolean(url));
            }
        };

        arquivo.addEventListener('change', async () => {
            if (!arquivo.files[0]) return;
            const corpo = new FormData();
            corpo.append('logo', arquivo.files[0]);
            const { ok, dados } = await requisicao(`${urlBase}/loja/logo`, { metodo: 'POST', corpo });
            arquivo.value = '';
            if (!ok) {
                mostrarMensagem(mensagemDeErro(dados), 'erro');
                return;
            }
            aplicar(dados.logo_url);
            mostrarMensagem(dados.message);
        });

        remover.addEventListener('click', async () => {
            const { ok, dados } = await requisicao(`${urlBase}/loja/logo`, { metodo: 'DELETE' });
            if (!ok) {
                mostrarMensagem(mensagemDeErro(dados), 'erro');
                return;
            }
            aplicar(null);
            mostrarMensagem(dados.message);
        });
    }

    // =========================================================================
    // Exportar vendas: período "Escolher datas…" mostra as datas
    // =========================================================================

    const exportarVendas = pagina.querySelector('[data-exportar-vendas]');
    if (exportarVendas) {
        const periodo = exportarVendas.querySelector('[data-exportar-periodo]');
        const datas = exportarVendas.querySelector('[data-exportar-datas]');
        periodo.addEventListener('change', () => {
            const intervalo = periodo.value === 'intervalo';
            datas.hidden = !intervalo;
            datas.querySelectorAll('input').forEach((i) => { i.disabled = !intervalo; });
        });
        exportarVendas.addEventListener('submit', (evento) => {
            if (periodo.value !== 'intervalo') return;
            const [de, ate] = datas.querySelectorAll('input');
            if (!de.value || !ate.value || ate.value < de.value) {
                evento.preventDefault();
                mostrarMensagem('Escolha uma data inicial e uma final depois dela.', 'erro');
            }
        });
    }

    // =========================================================================
    // Registro de atividades
    // =========================================================================

    const listaAtividades = pagina.querySelector('[data-atividades]');
    if (listaAtividades) {
        const vazio = pagina.querySelector('[data-atividades-vazio]');
        const mais = pagina.querySelector('[data-atividades-mais]');
        const filtro = pagina.querySelector('[data-atividades-area]');
        let ultimoId = null;

        const desenhar = (resultado, limpar) => {
            if (limpar) listaAtividades.innerHTML = '';
            let diaAnterior = limpar ? '' : (listaAtividades.dataset.ultimoDia || '');

            resultado.itens.forEach((item) => {
                if (item.data !== diaAnterior) {
                    listaAtividades.append(criar('li', 'atividades__dia', item.data));
                    diaAnterior = item.data;
                }
                const li = criar('li', `atividades__item atividades__item--${item.area}`);
                const marca = criar('span', 'atividades__icone');
                marca.title = item.area_rotulo;
                marca.append(icone(item.icone));
                const texto = criar('span', 'atividades__texto');
                texto.append(criar('span', '', item.descricao), criar('small', '', `${item.hora} · ${item.area_rotulo}`));
                li.append(marca, texto);
                listaAtividades.append(li);
                ultimoId = item.id;
            });

            listaAtividades.dataset.ultimoDia = diaAnterior;
            vazio.hidden = listaAtividades.children.length > 0;
            mais.hidden = !resultado.tem_mais;
        };

        async function carregar(limpar) {
            const params = new URLSearchParams();
            if (filtro.value) params.set('area', filtro.value);
            if (!limpar && ultimoId) params.set('antes', ultimoId);
            mais.disabled = true;
            const { ok, dados } = await requisicao(`${listaAtividades.dataset.url}?${params}`);
            mais.disabled = false;
            if (!ok) {
                mostrarMensagem('Não foi possível carregar as atividades.', 'erro');
                return;
            }
            if (limpar) ultimoId = null;
            desenhar(dados, limpar);
        }

        desenhar(JSON.parse(listaAtividades.dataset.inicial || '{"itens":[],"tem_mais":false}'), true);
        mais.addEventListener('click', () => carregar(false));
        filtro.addEventListener('change', () => carregar(true));
    }

    // =========================================================================
    // Índice das seções: destaca a seção visível
    // =========================================================================

    const itensNav = Array.from(pagina.querySelectorAll('[data-config-nav-item]'));
    if (itensNav.length && 'IntersectionObserver' in window) {
        const visiveis = new Map();
        const observador = new IntersectionObserver((entradas) => {
            entradas.forEach((e) => visiveis.set(e.target.id, e.isIntersecting ? e.intersectionRatio : 0));
            const atual = itensNav.find((item) => (visiveis.get(item.dataset.configNavItem) || 0) > 0);
            itensNav.forEach((item) => item.classList.toggle('is-active', item === atual));
        }, { rootMargin: '-90px 0px -55% 0px', threshold: [0, 0.01] });

        pagina.querySelectorAll('.config-secao').forEach((secao) => observador.observe(secao));
    }
}

document.addEventListener('DOMContentLoaded', iniciarPaginaConfiguracoes);
