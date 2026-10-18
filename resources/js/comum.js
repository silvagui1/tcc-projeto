/**
 * Funções pequenas usadas por mais de uma tela (clientes.js, vendas.js,
 * campeonatos.js e app.js).
 */

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
