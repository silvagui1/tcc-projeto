/**
 * Atalhos de teclado (só quando não se está digitando num campo e nenhum
 * pop-up está aberto):
 *   /  vai para a busca da página ([data-busca-campo] ou .search-bar); se a
 *      página não tem busca própria, abre a busca geral
 *   N  clica no botão marcado com data-atalho="n" (ex.: "Nova venda")
 * Ctrl+K (busca geral) fica em busca-geral.js.
 */

function digitando(alvo) {
    return alvo.closest('input, textarea, select, [contenteditable="true"]');
}

function popupAberto() {
    return document.querySelector('dialog[open], .modal-overlay:not([hidden])');
}

document.addEventListener('keydown', (evento) => {
    if (evento.ctrlKey || evento.metaKey || evento.altKey || digitando(evento.target) || popupAberto()) {
        return;
    }

    if (evento.key === '/') {
        evento.preventDefault();
        const campo = document.querySelector('[data-busca-campo], main .search-bar input[type="search"]');
        if (campo) {
            campo.focus();
            campo.select?.();
        } else {
            document.dispatchEvent(new Event('busca-geral:abrir'));
        }
        return;
    }

    const alvo = document.querySelector(`[data-atalho="${evento.key.toLowerCase()}"]`);
    if (alvo && alvo.offsetParent !== null) {
        evento.preventDefault();
        alvo.click();
    }
});
