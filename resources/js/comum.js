/**
 * Funções pequenas usadas por mais de uma tela (clientes.js, vendas.js).
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
