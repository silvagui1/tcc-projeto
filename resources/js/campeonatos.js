/**
 * Campeonatos — tudo acontece na lista, como em Estoque e Clientes: criar,
 * ver, editar, premiar e o check-in abrem no mesmo pop-up
 * (<dialog data-champ-modal>).
 *
 *  - Qualquer elemento com data-champ-ir="<url>" abre/troca o conteúdo do
 *    pop-up (o servidor devolve só o fragmento quando o pedido vem do fetch).
 *    data-champ-estado ("abrir=5", "editar=5", "criar=1"...) vai para a url
 *    da página, então recarregar mantém o pop-up aberto.
 *  - <form data-champ-form> dentro do pop-up é enviado por fetch. Se o
 *    servidor pede (criou, editou, finalizou), a página recarrega; senão
 *    (inscrever, pagar, checklist...) só o conteúdo do pop-up é atualizado,
 *    e a lista atrás é atualizada quando o pop-up fecha.
 *  - data-confirmar="texto" pede confirmação antes de enviar.
 */
import { iniciarCampoDeImagem } from './comum';

// estado na url → url do conteúdo (data-url-* do <dialog>)
const ESTADOS = {
    criar: 'urlCriar',
    copiar: 'urlCopiar',
    abrir: 'urlVer',
    checkin: 'urlCheckin',
    editar: 'urlEditar',
    premiar: 'urlPremiar',
};

const CHAVE_PAGAR_JUNTO = 'campeonatos.pagarJunto';

const lerPreferencia = (chave) => {
    try {
        return localStorage.getItem(chave) ?? '';
    } catch (e) {
        return '';
    }
};

const salvarPreferencia = (chave, valor) => {
    try {
        localStorage.setItem(chave, valor);
    } catch (e) {
        // sem armazenamento: vale só até recarregar
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const pagina = document.querySelector('[data-campeonatos-page]');
    const modal = pagina?.querySelector('[data-champ-modal]');
    if (!modal) {
        return;
    }

    const conteudo = modal.querySelector('[data-champ-modal-conteudo]');
    const carregando = modal.querySelector('[data-champ-modal-carregando]');
    const confirmar = document.querySelector('[data-champ-confirmar]');
    const apagar = document.querySelector('[data-champ-apagar-dialog]');

    let urlAtual = null;
    // algo mudou (inscrição, pagamento...): ao fechar, a lista é atualizada
    let listaDesatualizada = false;

    // --- url da página ---------------------------------------------------

    const definirEstado = (estado) => {
        const url = new URL(window.location.href);
        Object.keys(ESTADOS).forEach((chave) => url.searchParams.delete(chave));
        if (estado) {
            const [chave, valor] = estado.split('=');
            url.searchParams.set(chave, valor || '1');
        }
        window.history.replaceState(null, '', url);
    };

    // --- avisos ----------------------------------------------------------

    const toast = (texto, erro = false) => {
        modal.querySelector('.champ-toast')?.remove();
        const aviso = document.createElement('div');
        aviso.className = `champ-toast ${erro ? 'champ-toast--erro' : ''}`;
        aviso.setAttribute('role', erro ? 'alert' : 'status');
        aviso.innerHTML = `<i class="bi ${erro ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill'}" aria-hidden="true"></i>`;
        aviso.append(document.createTextNode(texto));
        modal.append(aviso);
        window.setTimeout(() => aviso.remove(), erro ? 6000 : 3500);
    };

    const limparErros = (form) => {
        form.querySelectorAll('[data-erro-js]').forEach((el) => el.remove());
        form.querySelectorAll('.campo--invalido').forEach((el) => el.classList.remove('campo--invalido'));
    };

    // "premiacoes.0.valor" → name="premiacoes[0][valor]"
    const nomeDoCampo = (chave) => {
        const [primeiro, ...resto] = chave.split('.');
        return primeiro + resto.map((parte) => `[${parte}]`).join('');
    };

    const mostrarErros = (form, erros, mensagem) => {
        limparErros(form);
        const gerais = [];
        let primeiro = null;

        Object.entries(erros || {}).forEach(([chave, mensagens]) => {
            const campo = form.querySelector(`[name="${nomeDoCampo(chave)}"]`)
                ?? form.querySelector(`[data-campo-nome="${chave}"]`);
            const lugar = campo?.closest('.campo') ?? campo?.closest('[data-campo-nome]') ?? null;

            if (!lugar) {
                gerais.push(...mensagens);
                return;
            }
            // campo dentro de "Mais opções" fechado: abre para mostrar o erro
            const recolhido = lugar.closest('details:not([open])');
            if (recolhido) recolhido.open = true;

            lugar.classList.add('campo--invalido');
            const erro = document.createElement('span');
            erro.className = 'campo__erro';
            erro.dataset.erroJs = '';
            erro.textContent = mensagens[0];
            lugar.append(erro);
            primeiro ??= lugar;
        });

        if (!erros && mensagem) {
            gerais.push(mensagem);
        }

        // formulário grande: lista no topo; formulários pequenos: aviso flutuante
        const corpo = form.querySelector('[data-champ-corpo]');
        if (gerais.length && corpo) {
            const caixa = document.createElement('div');
            caixa.className = 'champ-aviso champ-aviso--erro';
            caixa.dataset.erroJs = '';
            caixa.setAttribute('role', 'alert');
            caixa.innerHTML = '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>';
            const texto = document.createElement('span');
            texto.textContent = gerais.join(' ');
            caixa.append(texto);
            corpo.prepend(caixa);
            primeiro ??= caixa;
        } else if (gerais.length) {
            toast(gerais.join(' '), true);
        }

        primeiro?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    };

    // --- conteúdo do pop-up ----------------------------------------------

    const ir = async (url, estado, { manterRolagem = false, focar = null } = {}) => {
        const rolagem = conteudo.querySelector('[data-champ-corpo]')?.scrollTop ?? 0;

        if (!modal.open) {
            conteudo.innerHTML = '';
            modal.showModal();
        }
        carregando.hidden = false;

        try {
            const resposta = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
            // o servidor mandou para a lista (ex.: campeonato já finalizado)
            if (resposta.redirected) {
                window.location.href = resposta.url;
                return;
            }
            if (!resposta.ok) {
                throw new Error(String(resposta.status));
            }

            conteudo.innerHTML = await resposta.text();
            urlAtual = url;
            const fragmento = conteudo.querySelector('[data-champ-fragmento]');
            definirEstado(estado ?? fragmento?.dataset.estado);
            // check-in ocupa a tela toda
            modal.classList.toggle('champ-modal--cheio', Boolean(fragmento?.classList.contains('champ-modal__painel--checkin')));
            iniciar(conteudo);

            const corpo = conteudo.querySelector('[data-champ-corpo]');
            if (corpo) {
                corpo.scrollTop = manterRolagem ? rolagem : 0;
            }
            if (focar) {
                conteudo.querySelector(focar)?.focus();
            }
        } catch (erro) {
            conteudo.innerHTML = `
                <div class="champ-modal__painel">
                    <header class="champ-modal__topo">
                        <h2 class="champ-modal__titulo">Campeonato</h2>
                        <button type="button" class="champ-modal__fechar" data-champ-fechar aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
                    </header>
                    <div class="champ-modal__corpo">
                        <p class="champ-vazio"><i class="bi bi-wifi-off" aria-hidden="true"></i>
                        ${erro.message === '404' ? 'Este campeonato não existe mais.' : 'Não foi possível carregar. Tente de novo.'}</p>
                    </div>
                </div>`;
        } finally {
            carregando.hidden = true;
        }
    };

    /**
     * Atualiza resumo e lista atrás do pop-up sem recarregar a página (os
     * filtros e a rolagem continuam onde estavam).
     */
    const atualizarLista = async () => {
        try {
            const resposta = await fetch(window.location.href, { headers: { Accept: 'text/html' } });
            const nova = new DOMParser().parseFromString(await resposta.text(), 'text/html');
            nova.querySelectorAll('[data-champ-atualizavel]').forEach((parte) => {
                const atual = pagina.querySelector(`[data-champ-atualizavel="${parte.dataset.champAtualizavel}"]`);
                if (atual) {
                    atual.innerHTML = parte.innerHTML;
                    usarBannerReserva(atual);
                }
            });
        } catch (e) {
            window.location.reload();
        }
    };

    const fechar = () => modal.close();

    modal.addEventListener('close', () => {
        definirEstado(null);
        conteudo.innerHTML = '';
        urlAtual = null;
        modal.classList.remove('champ-modal--cheio');
        if (listaDesatualizada) {
            listaDesatualizada = false;
            atualizarLista();
        }
    });

    // clicar no fundo escuro fecha
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            fechar();
        }
    });

    // --- envio dos formulários -------------------------------------------

    const pedirConfirmacao = (form) => new Promise((resolve) => {
        if (!confirmar) {
            resolve(window.confirm(form.dataset.confirmar));
            return;
        }
        confirmar.querySelector('[data-confirmar-titulo]').textContent = form.dataset.confirmarTitulo || 'Confirmar?';
        confirmar.querySelector('[data-confirmar-texto]').textContent = form.dataset.confirmar;
        const sim = confirmar.querySelector('[data-confirmar-sim]');
        sim.textContent = form.dataset.confirmarSim || 'Confirmar';

        let resposta = false;
        const aoSim = () => {
            resposta = true;
            confirmar.close();
        };
        sim.addEventListener('click', aoSim, { once: true });
        confirmar.addEventListener('close', () => {
            sim.removeEventListener('click', aoSim);
            resolve(resposta);
        }, { once: true });
        confirmar.showModal();
    });

    const enviar = async (form, submitter = null) => {
        // "ao inscrever, receber em": a forma escolhida vai junto
        const formaJunto = form.querySelector('[data-forma-junto]');
        if (formaJunto) {
            formaJunto.value = conteudo.querySelector('[data-pagar-junto] input:checked')?.value ?? '';
        }

        // monta o corpo antes de desabilitar os botões: botão desabilitado não
        // entra no envio, e o clicado conta (ex.: Pix / Dinheiro no pagamento)
        const corpo = new FormData(form, submitter);

        const botoes = form.querySelectorAll('button[type="submit"]');
        botoes.forEach((botao) => { botao.disabled = true; });
        form.classList.add('is-enviando');

        try {
            const resposta = await fetch(form.action, {
                method: 'POST', // _method (PUT/DELETE) vai no corpo
                body: corpo,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const dados = await resposta.json().catch(() => ({}));

            if (resposta.ok) {
                if (dados.redirecionar) {
                    listaDesatualizada = false;
                    window.location.href = dados.redirecionar;
                    return;
                }
                listaDesatualizada = true;
                // inscreveu alguém: o cursor volta para a busca, para o próximo nome
                const daInscricao = form.matches('[data-participant-name]') || form.closest('[data-provisorio-detalhes]');
                await ir(urlAtual, null, { manterRolagem: true, focar: daInscricao ? '[data-inscrever-busca]' : null });
                if (dados.mensagem) {
                    toast(dados.mensagem);
                }
                return;
            }

            if (resposta.status === 419) {
                mostrarErros(form, null, 'A sessão expirou. Recarregue a página e tente de novo.');
            } else {
                mostrarErros(form, resposta.status === 422 ? dados.errors : null, dados.message || 'Não foi possível salvar. Tente de novo.');
            }
        } catch (erro) {
            mostrarErros(form, null, 'Sem conexão com o servidor. Tente de novo.');
        } finally {
            botoes.forEach((botao) => { botao.disabled = false; });
            form.classList.remove('is-enviando');
        }
    };

    modal.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-champ-form]');
        if (!form) {
            return;
        }
        event.preventDefault();
        if (form.classList.contains('is-enviando')) {
            return;
        }
        const submitter = event.submitter ?? null;
        if (form.dataset.confirmar && !(await pedirConfirmacao(form))) {
            return;
        }
        enviar(form, submitter);
    });

    // checklist: salva a cada clique
    modal.addEventListener('change', (event) => {
        const form = event.target.closest('form[data-champ-auto]');
        if (form) {
            enviar(form);
        }
    });

    // --- navegação -------------------------------------------------------

    document.addEventListener('click', (event) => {
        const alvo = event.target.closest('[data-champ-ir]');
        if (alvo && !(event.ctrlKey || event.metaKey || event.shiftKey) && !alvo.disabled) {
            event.preventDefault();
            ir(alvo.dataset.champIr, alvo.dataset.champEstado);
            return;
        }

        if (event.target.closest('[data-champ-fechar]')) {
            fechar();
            return;
        }

        // pílula "Em aberto": mostra as formas de pagamento da linha
        const pilula = event.target.closest('[data-pagar-abrir]');
        if (pilula) {
            const linha = pilula.closest('[data-participante]');
            const receber = linha.querySelector('.champ-receber');
            const abrir = receber.hidden;
            conteudo.querySelectorAll('.champ-receber').forEach((outro) => { outro.hidden = true; });
            conteudo.querySelectorAll('[data-pagar-abrir]').forEach((outra) => outra.setAttribute('aria-expanded', 'false'));
            receber.hidden = !abrir;
            pilula.setAttribute('aria-expanded', String(abrir));
            if (abrir) receber.querySelector('button')?.focus();
            return;
        }

        const lixeira = event.target.closest('[data-champ-apagar]');
        if (lixeira && apagar) {
            const form = apagar.querySelector('form');
            form.action = lixeira.dataset.champApagar;
            apagar.querySelector('[data-champ-apagar-titulo]').textContent = lixeira.dataset.champApagarTitulo;
            const semanal = apagar.querySelector('[data-champ-apagar-semanal]');
            semanal.hidden = lixeira.dataset.champApagarSemanal !== '1';
            semanal.querySelector('input').checked = false;
            apagar.showModal();
        }
    });

    // diálogos pequenos (apagar, confirmar): "Manter"/"Voltar" e o fundo fecham
    document.querySelectorAll('.champ-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog || event.target.closest('[data-dialog-fechar]')) {
                dialog.close();
            }
        });
    });

    // --- partes interativas do conteúdo ----------------------------------

    function iniciar(raiz) {
        raiz.querySelectorAll('[data-imagem-campo]').forEach((campo) => {
            iniciarCampoDeImagem(campo);
            // a área do banner também abre pelo teclado
            const area = campo.querySelector('[data-imagem-area]');
            area?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    campo.querySelector('[data-imagem-arquivo]').click();
                }
            });
        });
        raiz.querySelectorAll('form[data-banner-url]').forEach(iniciarFormulario);
        raiz.querySelectorAll('[data-participant-search]').forEach(iniciarBuscaDoFormulario);
        raiz.querySelectorAll('[data-inscrever]').forEach(iniciarInscricaoRapida);
        raiz.querySelectorAll('[data-premiacao]').forEach(iniciarPremiacao);
        raiz.querySelectorAll('[data-stepper]').forEach(iniciarStepper);
        raiz.querySelectorAll('input[name="whatsapp"], [data-provisorio-campo="whatsapp"]').forEach(mascararWhatsapp);
        usarBannerReserva(raiz);

        // "ao inscrever, receber em": lembra a última escolha
        raiz.querySelectorAll('[data-pagar-junto]').forEach((grupo) => {
            const salvo = lerPreferencia(CHAVE_PAGAR_JUNTO);
            const opcao = grupo.querySelector(`input[value="${CSS.escape(salvo)}"]`);
            if (opcao) opcao.checked = true;
            grupo.addEventListener('change', (event) => salvarPreferencia(CHAVE_PAGAR_JUNTO, event.target.value));
        });
    }

    // estado inicial: ?criar=1, ?copiar=ID, ?abrir=ID, ?checkin=ID, ?editar=ID, ?premiar=ID
    const parametros = new URLSearchParams(window.location.search);
    const inicial = Object.keys(ESTADOS).find((chave) => parametros.has(chave));
    if (inicial) {
        const id = parametros.get(inicial);
        ir(modal.dataset[ESTADOS[inicial]].replace('__ID__', id), `${inicial}=${id}`);
    }

    usarBannerReserva(pagina);
});

/**
 * Imagem de banner que não carrega (link quebrado, arquivo apagado) volta
 * para o banner do sistema.
 */
function usarBannerReserva(raiz) {
    raiz.querySelectorAll('img[data-banner-reserva]').forEach((img) => {
        const trocar = () => {
            if (img.src !== img.dataset.bannerReserva) {
                img.src = img.dataset.bannerReserva;
                img.classList.add('champ-banner--sistema');
            }
        };
        img.addEventListener('error', trocar, { once: true });
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) {
            trocar();
        }
    });
}

const formatarReais = (valor) => valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const lerReais = (texto) => parseFloat(String(texto).replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;

/**
 * Pop-up de criar/editar: tipo (descrição e bloco dos oficiais), banner do
 * sistema ao vivo, dica do "repete toda semana" e o total dos prêmios.
 */
function iniciarFormulario(form) {
    const tipos = form.querySelectorAll('[data-tipo-radio]');
    const descricao = form.querySelector('[data-tipo-descricao]');
    const oficial = form.querySelector('[data-oficial-bloco]');
    const jogo = form.querySelector('[data-jogo-select]');
    const bannerSistema = form.querySelector('[data-banner-sistema]');
    const semanal = form.querySelector('[data-semanal-switch]');
    const dica = form.querySelector('[data-semanal-dica]');
    const data = form.querySelector('[data-data-campo]');
    const horario = form.querySelector('[data-horario-campo]');
    const inscricao = form.querySelector('[data-inscricao-campo]');
    const premios = form.querySelectorAll('[data-premio-campo]');
    const total = form.querySelector('[data-premios-total]');
    const dicaPadrao = dica?.textContent;
    const totalPadrao = total?.textContent;
    let semanalMexido = Boolean(semanal?.dataset.semanalMexido);

    const tipoMarcado = () => [...tipos].find((radio) => radio.checked);

    const atualizarBanner = () => {
        if (!bannerSistema) return;
        const url = new URL(form.dataset.bannerUrl, window.location.origin);
        url.searchParams.set('jogo', jogo.value);
        url.searchParams.set('tipo', tipoMarcado()?.value || 'liga');
        bannerSistema.src = url;
    };

    const atualizarSemanal = () => {
        if (!semanal || !dica) return;
        horario.required = semanal.checked;

        if (!semanal.checked || !data.value) {
            dica.textContent = dicaPadrao;
            return;
        }
        const dia = new Date(`${data.value}T00:00:00`);
        const nomeDia = dia.toLocaleDateString('pt-BR', { weekday: 'long' });
        const prefixo = [0, 6].includes(dia.getDay()) ? 'Todo' : 'Toda';
        dica.textContent = `${prefixo} ${nomeDia}${horario.value ? ` às ${horario.value}` : ' (informe o horário)'}. `
            + 'Ao finalizar, o da semana seguinte já fica pré-cadastrado.';
    };

    // total distribuído em créditos x quantas inscrições pagas isso representa
    const atualizarTotal = () => {
        if (!total) return;
        const soma = [...premios].reduce((acc, campo) => acc + lerReais(campo.value), 0);
        const valorInscricao = lerReais(inscricao?.value);

        if (soma <= 0) {
            total.textContent = totalPadrao;
            total.classList.remove('champ-premios__total--valor');
            return;
        }
        total.classList.add('champ-premios__total--valor');
        total.textContent = `Total em prêmios: ${formatarReais(soma)} em créditos`
            + (valorInscricao > 0 ? ` · equivale a ${Math.ceil(soma / valorInscricao)} ${Math.ceil(soma / valorInscricao) === 1 ? 'inscrição paga' : 'inscrições pagas'}` : '');
    };

    tipos.forEach((radio) => radio.addEventListener('change', () => {
        const marcado = tipoMarcado();
        if (descricao) descricao.textContent = marcado.dataset.descricao;
        if (oficial) oficial.hidden = !marcado.dataset.oficial;
        // liga semanal costuma repetir: sugere ligado enquanto ninguém mexeu
        if (!semanalMexido && semanal) {
            semanal.checked = marcado.value === 'liga';
            atualizarSemanal();
        }
        atualizarBanner();
    }));
    jogo?.addEventListener('change', atualizarBanner);
    semanal?.addEventListener('change', () => { semanalMexido = true; atualizarSemanal(); });
    data?.addEventListener('change', atualizarSemanal);
    horario?.addEventListener('change', atualizarSemanal);
    [...premios, inscricao].forEach((campo) => campo?.addEventListener('input', atualizarTotal));

    if (!semanalMexido && semanal && tipoMarcado()?.value === 'liga') {
        semanal.checked = true;
    }
    atualizarSemanal();
    atualizarTotal();
}

/**
 * Participantes no pop-up de criar: a busca mostra os clientes com o texto
 * no nome (com o campo vazio, só os marcados). Quem não é cliente entra
 * como provisório — linha com nome e WhatsApp, enviada em provisorios[n].
 */
function iniciarBuscaDoFormulario(busca) {
    const input = busca.querySelector('[data-participant-search-input]');
    const linhas = busca.querySelectorAll('[data-participant-name]');
    const contador = busca.querySelector('[data-participant-count]');
    const sugerir = busca.querySelector('[data-provisorio-sugerir]');
    const sugerirNome = busca.querySelector('[data-provisorio-sugerir-nome]');
    const lista = busca.querySelector('[data-provisorios-lista]');
    const modelo = busca.querySelector('[data-provisorio-modelo]');
    let proximo = 0;

    const contar = () => {
        const marcados = [...linhas].filter((linha) => linha.querySelector('input').checked).length;
        const provisorios = lista.children.length;
        const partes = [];
        if (marcados) partes.push(`${marcados} ${marcados === 1 ? 'cliente' : 'clientes'}`);
        if (provisorios) partes.push(`${provisorios} ${provisorios === 1 ? 'provisório' : 'provisórios'}`);
        contador.textContent = partes.length ? `${partes.join(' + ')} na lista` : '';
    };

    const filtrar = () => {
        const termo = input.value.trim().toLowerCase();
        let visiveis = 0;

        linhas.forEach((linha) => {
            const marcado = linha.querySelector('input').checked;
            linha.hidden = termo === '' ? !marcado : !linha.dataset.participantName.toLowerCase().includes(termo);
            visiveis += termo !== '' && !linha.hidden ? 1 : 0;
        });

        sugerir.hidden = termo === '' || visiveis > 0;
        sugerirNome.textContent = `“${input.value.trim()}”`;
        contar();
    };

    const adicionarProvisorio = (nome = '') => {
        const item = modelo.content.firstElementChild.cloneNode(true);
        item.querySelectorAll('[data-provisorio-campo]').forEach((campo) => {
            campo.name = `provisorios[${proximo}][${campo.dataset.provisorioCampo}]`;
        });
        proximo += 1;
        item.querySelector('[data-provisorio-campo="nome"]').value = nome;
        item.querySelector('[data-provisorio-remover]').addEventListener('click', () => {
            item.remove();
            contar();
        });
        mascararWhatsapp(item.querySelector('[data-provisorio-campo="whatsapp"]'));
        lista.append(item);
        item.querySelector(nome ? '[data-provisorio-campo="whatsapp"]' : '[data-provisorio-campo="nome"]').focus();
        contar();
    };

    input.addEventListener('input', filtrar);
    busca.addEventListener('change', filtrar);
    busca.querySelector('[data-provisorio-adicionar]').addEventListener('click', () => adicionarProvisorio());
    sugerir.addEventListener('click', () => {
        adicionarProvisorio(input.value.trim());
        input.value = '';
        filtrar();
    });
    // Enter na busca não envia o formulário inteiro
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
    });
    filtrar();
}

/**
 * Inscrição rápida no pop-up do campeonato: digitar mostra os clientes; o
 * clique inscreve na hora. Se ninguém tem o nome, o atalho de provisório
 * já vem com ele preenchido. Enter com um resultado só inscreve.
 */
function iniciarInscricaoRapida(bloco) {
    const input = bloco.querySelector('[data-inscrever-busca]');
    const resultados = bloco.querySelectorAll('[data-participant-name]');
    const detalhes = bloco.querySelector('[data-provisorio-detalhes]');
    const resumo = bloco.querySelector('[data-provisorio-resumo]');
    const nome = bloco.querySelector('[data-provisorio-nome]');
    const resumoPadrao = resumo.textContent;

    const filtrar = () => {
        const termo = input.value.trim();
        const minusculo = termo.toLowerCase();
        let visiveis = 0;

        resultados.forEach((form) => {
            form.hidden = minusculo === '' || !form.dataset.participantName.toLowerCase().includes(minusculo);
            visiveis += form.hidden ? 0 : 1;
        });

        bloco.classList.toggle('tem-resultados', visiveis > 0);
        bloco.classList.toggle('sem-resultados', termo !== '' && visiveis === 0);
        resumo.textContent = termo && visiveis === 0 ? `Ninguém com esse nome. Inscrever “${termo}” como provisório` : resumoPadrao;
        if (termo && visiveis === 0) {
            nome.value = termo;
        }
    };

    input.addEventListener('input', filtrar);
    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const visiveis = [...resultados].filter((form) => !form.hidden);
        if (visiveis.length === 1) {
            visiveis[0].requestSubmit();
        } else if (visiveis.length === 0 && input.value.trim()) {
            detalhes.open = true;
            nome.focus();
        }
    });
    detalhes.addEventListener('toggle', () => {
        if (detalhes.open) {
            if (!nome.value) nome.value = input.value.trim();
            nome.focus();
        }
    });
    filtrar();
}

/**
 * Premiação: quem já está numa colocação some das outras, e escolher alguém
 * com a inscrição em aberto mostra o aviso.
 */
function iniciarPremiacao(grade) {
    const selects = [...grade.querySelectorAll('[data-premio-participante]')];

    const atualizar = () => {
        const escolhidos = selects.map((select) => select.value).filter(Boolean);

        selects.forEach((select) => {
            [...select.options].forEach((opcao) => {
                if (!opcao.value) return;
                const emOutra = escolhidos.includes(opcao.value) && select.value !== opcao.value;
                opcao.disabled = emOutra;
                opcao.hidden = emOutra;
            });
            const aviso = select.closest('.campo').querySelector('[data-premio-aviso]');
            if (aviso) aviso.hidden = !select.selectedOptions[0]?.hasAttribute('data-em-aberto');
        });
    };

    selects.forEach((select) => select.addEventListener('change', atualizar));
    atualizar();
}

/**
 * Setas de crédito (premiações): somam/subtraem R$ 1,00.
 */
function iniciarStepper(stepper) {
    const input = stepper.querySelector('[data-stepper-input]');

    stepper.querySelectorAll('[data-stepper-step]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const atual = lerReais(input.value);
            const novo = Math.max(0, atual + Number(botao.dataset.stepperStep));
            input.value = novo.toFixed(2).replace('.', ',');
        });
    });
}

/**
 * "(11) 98765-4321" enquanto digita (o servidor guarda só os dígitos).
 */
function mascararWhatsapp(input) {
    if (!input || input.dataset.mascarado) return;
    input.dataset.mascarado = '1';

    input.addEventListener('input', () => {
        const digitos = input.value.replace(/\D/g, '').slice(0, 11);
        let formatado = digitos;
        if (digitos.length > 2) {
            const meio = digitos.length > 10 ? 7 : 6;
            formatado = `(${digitos.slice(0, 2)}) ${digitos.slice(2, meio)}${digitos.length > meio ? `-${digitos.slice(meio)}` : ''}`;
        } else if (digitos.length) {
            formatado = `(${digitos}`;
        }
        input.value = formatado;
    });
}
