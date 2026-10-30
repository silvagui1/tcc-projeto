/**
 * Busca geral (partials/busca-geral.blade.php): Ctrl+K / ⌘K de qualquer
 * tela, "/" quando a página não tem busca própria, ou botões
 * [data-abrir-busca]. Busca em /busca enquanto digita (com uma pequena
 * espera), setas navegam pelos resultados e Enter abre o selecionado.
 */

const ESPERA_MS = 220;

function iniciarBuscaGeral() {
    const dialog = document.querySelector('[data-busca-geral]');
    if (!dialog) return;

    const form = dialog.querySelector('[data-busca-geral-form]');
    const input = dialog.querySelector('[data-busca-geral-input]');
    const resultados = dialog.querySelector('[data-busca-geral-resultados]');
    const dicaInicial = resultados.innerHTML;
    let timer = null;
    let controlador = null;
    let selecionado = -1;

    const links = () => Array.from(resultados.querySelectorAll('.busca-geral__item'));

    function marcar(indice) {
        const itens = links();
        if (!itens.length) return;
        selecionado = (indice + itens.length) % itens.length;
        itens.forEach((item, i) => item.setAttribute('aria-selected', i === selecionado ? 'true' : 'false'));
        itens[selecionado].scrollIntoView({ block: 'nearest' });
    }

    function mostrar(grupos) {
        selecionado = -1;
        resultados.innerHTML = '';

        if (!grupos.length) {
            resultados.innerHTML = '<p class="busca-geral__dica">Nada encontrado.</p>';
            return;
        }

        grupos.forEach((grupo) => {
            const titulo = document.createElement('p');
            titulo.className = 'busca-geral__grupo';
            titulo.textContent = grupo.titulo;
            resultados.append(titulo);

            grupo.itens.forEach((item) => {
                const link = document.createElement('a');
                link.className = 'busca-geral__item';
                link.href = item.url;
                link.setAttribute('role', 'option');
                link.innerHTML = `<i class="bi ${item.icone}" aria-hidden="true"></i><span><strong></strong><small></small></span>`;
                link.querySelector('strong').textContent = item.titulo;
                link.querySelector('small').textContent = item.subtitulo;
                resultados.append(link);
            });
        });

        marcar(0);
    }

    async function buscar() {
        const termo = input.value.trim();
        if (termo.length < 2 && !/^#?\d+$/.test(termo)) {
            resultados.innerHTML = dicaInicial;
            selecionado = -1;
            return;
        }

        controlador?.abort();
        controlador = new AbortController();
        resultados.classList.add('esta-carregando');

        try {
            const resposta = await fetch(`${dialog.dataset.url}?q=${encodeURIComponent(termo)}`, {
                headers: { Accept: 'application/json' },
                signal: controlador.signal,
            });
            if (!resposta.ok) throw new Error(resposta.status);
            mostrar((await resposta.json()).grupos);
        } catch (erro) {
            if (erro.name !== 'AbortError') {
                resultados.innerHTML = '<p class="busca-geral__dica">Não foi possível buscar agora. Tente de novo.</p>';
            }
        } finally {
            resultados.classList.remove('esta-carregando');
        }
    }

    function abrir() {
        if (dialog.open) return;
        dialog.showModal();
        input.select();
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(buscar, ESPERA_MS);
    });

    input.addEventListener('keydown', (evento) => {
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            marcar(selecionado + 1);
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            marcar(selecionado - 1);
        } else if (evento.key === 'Escape') {
            // no <input type="search"> o Esc só limparia o texto: fecha direto
            evento.preventDefault();
            dialog.close();
        }
    });

    form.addEventListener('submit', (evento) => {
        evento.preventDefault();
        const item = links()[selecionado];
        if (item) window.location.href = item.href;
    });

    // clique no fundo escuro fecha
    dialog.addEventListener('click', (evento) => {
        if (evento.target === dialog) dialog.close();
    });

    document.addEventListener('click', (evento) => {
        if (evento.target.closest('[data-abrir-busca]')) {
            evento.preventDefault();
            abrir();
        }
    });

    document.addEventListener('keydown', (evento) => {
        if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'k') {
            evento.preventDefault();
            abrir();
        }
    });

    // usado pelo atalho "/" (atalhos.js) nas páginas sem busca própria
    document.addEventListener('busca-geral:abrir', abrir);
}

document.addEventListener('DOMContentLoaded', iniciarBuscaGeral);
