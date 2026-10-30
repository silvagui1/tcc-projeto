/**
 * Funções pequenas usadas por mais de uma tela (clientes.js, vendas.js,
 * campeonatos.js e app.js).
 */

const ICONES_AVISO = {
    sucesso: 'bi-check-circle-fill',
    erro: 'bi-exclamation-circle-fill',
    info: 'bi-info-circle-fill',
};

/**
 * Aviso flutuante do sistema (o único elemento [data-mensagem] fica no
 * layout). Ícone + texto, e opcionalmente o botão "Desfazer":
 *   avisar('Produto arquivado.', 'sucesso', { desfazer: () => ... })
 * Com "Desfazer" o aviso fica mais tempo na tela (ou o tempo de `duracao`, em ms,
 * quando a ação só é confirmada depois de uma janela — ver clientes.js).
 */
export function avisar(texto, tipo = 'sucesso', { desfazer = null, duracao = null } = {}) {
    const el = document.querySelector('[data-mensagem]');
    if (!el || !texto) return;

    el.innerHTML = `<i class="bi ${ICONES_AVISO[tipo] || ICONES_AVISO.sucesso}" aria-hidden="true"></i><span></span>`;
    el.querySelector('span').textContent = texto;
    el.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');

    if (desfazer) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'mensagem-flutuante__desfazer';
        botao.textContent = 'Desfazer';
        botao.addEventListener('click', () => {
            el.hidden = true;
            window.clearTimeout(el._timeout);
            desfazer();
        });
        el.append(botao);
    }

    el.className = `mensagem-flutuante mensagem-flutuante--${tipo}`;
    el.hidden = false;
    window.clearTimeout(el._timeout);
    el._timeout = window.setTimeout(() => { el.hidden = true; }, duracao ?? (desfazer ? 8000 : (tipo === 'erro' ? 6000 : 4000)));
}

/**
 * Aviso vindo do servidor depois de um redirect (session('aviso'), lido no
 * layout). O "Desfazer" de um aviso do servidor é um formulário: envia
 * {url, metodo} com o token CSRF.
 */
export function avisarDoServidor() {
    const dados = document.querySelector('[data-aviso-inicial]');
    if (!dados) return;

    let aviso;
    try {
        aviso = JSON.parse(dados.textContent);
    } catch (erro) {
        return;
    }

    const desfazer = aviso.desfazer ? () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = aviso.desfazer.url;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.innerHTML = `<input type="hidden" name="_token" value="${token}">`
            + `<input type="hidden" name="_method" value="${aviso.desfazer.metodo || 'POST'}">`;
        document.body.append(form);
        form.submit();
    } : null;

    avisar(aviso.texto, aviso.tipo || 'sucesso', { desfazer });
}

export function formatarMoeda(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/**
 * Focus trap simples: mantém o Tab/Shift+Tab preso dentro do container
 * enquanto ele estiver ativo, para o teclado não escapar pro conteúdo atrás
 * do overlay do modal.
 */
export function ativarFocusTrap(container) {
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

export function desativarFocusTrap(container) {
    if (container && container._focusTrapHandler) {
        container.removeEventListener('keydown', container._focusTrapHandler);
        container._focusTrapHandler = null;
    }
}

// Estoque — campo de imagem dos pop-ups (partials/imagem-campo). A imagem
// pode vir de um arquivo do aparelho, de Ctrl+V (print, imagem copiada do
// navegador...), de arrastar e soltar ou de um link. Colar/arrastar coloca o
// arquivo dentro do próprio <input type="file">, então o envio é o mesmo de
// "Escolher imagem". Expõe campo.imagemDoEstoque.reiniciar(url) para o
// pop-up limpar/preencher o campo ao abrir.
export const iniciarCampoDeImagem = (campo) => {
    // Ctrl+V vale no pop-up todo; nos campeonatos o conteúdo do pop-up é
    // trocado, então o escopo é o próprio form (data-imagem-escopo)
    const dialog = campo.closest('[data-imagem-escopo]') ?? campo.closest('dialog');
    const area = campo.querySelector('[data-imagem-area]');
    const preview = campo.querySelector('[data-imagem-preview]');
    const vazio = campo.querySelector('[data-imagem-vazio]');
    const arquivo = campo.querySelector('[data-imagem-arquivo]');
    const url = campo.querySelector('[data-imagem-url]');
    const remover = campo.querySelector('[data-imagem-remover]');
    const removerInput = campo.querySelector('[data-imagem-remover-input]');
    let urlTemporaria = null;

    const mostrar = (src) => {
        if (urlTemporaria && src !== urlTemporaria) {
            URL.revokeObjectURL(urlTemporaria);
            urlTemporaria = null;
        }
        preview.hidden = !src;
        vazio.hidden = Boolean(src);
        remover.hidden = !src;
        if (src) {
            preview.src = src;
        } else {
            preview.removeAttribute('src');
        }
    };

    const usarArquivo = (file) => {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }
        const transferencia = new DataTransfer();
        transferencia.items.add(file);
        arquivo.files = transferencia.files;
        url.value = '';
        removerInput.value = '0';
        mostrar(urlTemporaria = URL.createObjectURL(file));
    };

    arquivo.addEventListener('change', () => usarArquivo(arquivo.files[0]));

    url.addEventListener('input', () => {
        arquivo.value = '';
        removerInput.value = url.value.trim() ? '0' : removerInput.value;
        mostrar(url.value.trim());
    });

    // link quebrado: volta para o quadro vazio em vez de mostrar ícone quebrado
    preview.addEventListener('error', () => mostrar(''));

    remover.addEventListener('click', () => {
        arquivo.value = '';
        url.value = '';
        removerInput.value = '1';
        mostrar('');
    });

    // Ctrl+V em qualquer lugar do pop-up (se o que foi copiado for imagem;
    // texto colado num campo continua funcionando normalmente)
    dialog.addEventListener('paste', (event) => {
        const imagem = [...(event.clipboardData?.files ?? [])].find((file) => file.type.startsWith('image/'));
        if (imagem) {
            event.preventDefault();
            usarArquivo(imagem);
        }
    });

    ['dragenter', 'dragover'].forEach((tipo) => area.addEventListener(tipo, (event) => {
        event.preventDefault();
        area.classList.add('is-arrastando');
    }));
    ['dragleave', 'drop'].forEach((tipo) => area.addEventListener(tipo, () => area.classList.remove('is-arrastando')));
    area.addEventListener('drop', (event) => {
        event.preventDefault();
        usarArquivo(event.dataTransfer.files[0]);
    });
    area.addEventListener('click', () => arquivo.click());

    campo.imagemDoEstoque = {
        reiniciar: (src) => {
            arquivo.value = '';
            removerInput.value = '0';
            mostrar(src || '');
        },
    };

    // estado inicial (ex.: pop-up reaberto por erro de validação)
    mostrar(url.value.trim() || campo.dataset.imagemInicial || '');
};
