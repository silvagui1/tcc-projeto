import './bootstrap';

// Abre/fecha o menu em arco do botão flutuante inferior (mobile).
document.addEventListener('DOMContentLoaded', () => {
    const bottomNav = document.querySelector('[data-bottom-nav]');
    const toggle = document.querySelector('[data-bottom-nav-toggle]');
    const toggleIcon = toggle?.querySelector('i');

    if (!bottomNav || !toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const aberto = bottomNav.classList.toggle('is-open');
        toggleIcon?.classList.toggle('bi-list', !aberto);
        toggleIcon?.classList.toggle('bi-x-lg', aberto);
    });

    // fecha o menu se o usuário clicar fora dele
    document.addEventListener('click', (event) => {
        if (!bottomNav.contains(event.target)) {
            bottomNav.classList.remove('is-open');
            toggleIcon?.classList.remove('bi-x-lg');
            toggleIcon?.classList.add('bi-list');
        }
    });
});

// Campeonatos — pop-up "Deseja apagar este campeonato?". Qualquer botão com
// data-delete-open="<url>" abre o <dialog> e usa a url como action do form.
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-delete-dialog]');
    const form = dialog?.querySelector('[data-delete-form]');

    if (!dialog || !form) {
        return;
    }

    document.querySelectorAll('[data-delete-open]').forEach((botao) => {
        botao.addEventListener('click', () => {
            form.action = botao.dataset.deleteOpen;
            dialog.showModal();
        });
    });

    dialog.querySelector('[data-delete-cancel]')?.addEventListener('click', () => dialog.close());

    // clicar no fundo escuro também fecha
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

// Estoque de cartas — pop-up "Adicionar carta". Qualquer botão com
// data-card-dialog-open abre o <dialog>; se o form voltou com erros de
// validação, o pop-up já abre sozinho (data-open-on-load).
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-card-dialog]');

    if (!dialog) {
        return;
    }

    document.querySelectorAll('[data-card-dialog-open]').forEach((botao) => {
        botao.addEventListener('click', () => dialog.showModal());
    });

    dialog.querySelectorAll('[data-card-dialog-close]').forEach((botao) => {
        botao.addEventListener('click', () => dialog.close());
    });

    // clicar no fundo escuro também fecha
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
    }
});

// Campeonatos — busca de participantes (criar): esconde os clientes cujo
// nome não contém o texto digitado.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-participant-search]').forEach((busca) => {
        const input = busca.querySelector('[data-participant-search-input]');
        const linhas = busca.querySelectorAll('[data-participant-name]');

        input?.addEventListener('input', () => {
            const termo = input.value.trim().toLowerCase();
            linhas.forEach((linha) => {
                linha.hidden = !linha.dataset.participantName.toLowerCase().includes(termo);
            });
        });
    });
});

// Campeonatos — setas de crédito (premiações): somam/subtraem R$ 1,00.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('[data-stepper-input]');

        stepper.querySelectorAll('[data-stepper-step]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const atual = parseFloat(input.value.replace(/\./g, '').replace(',', '.')) || 0;
                const novo = Math.max(0, atual + Number(botao.dataset.stepperStep));
                input.value = novo.toFixed(2).replace('.', ',');
            });
        });
    });
});


// Estoque de cartas — filtros laterais. No desktop o painel ocupa o lugar de
// uma carta: fica na 1ª coluna da primeira fileira, alinhado com a borda de
// cima das cartas (abaixo do título delas) e com a mesma altura. Da segunda
// fileira em diante as cartas usam as 4 colunas. Como a altura da carta muda
// com a largura da tela, medimos de novo a cada redimensionamento.
document.addEventListener('DOMContentLoaded', () => {
    const lista = document.querySelector('[data-card-list]');
    const filtros = lista?.querySelector('[data-cartas-filtros]');

    if (!lista || !filtros) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 992px)');

    const ajustar = () => {
        if (!desktop.matches) {
            lista.style.removeProperty('--filtros-topo');
            lista.style.removeProperty('--filtros-altura');
            return;
        }

        // no desktop o painel fica sempre aberto
        filtros.open = true;

        // mede as cartas no tamanho natural (sem esticar para a altura da fileira)
        lista.classList.add('is-medindo');

        const cartas = [...lista.querySelectorAll(':scope > article')].slice(0, 3);
        const quadros = cartas.map((carta) => carta.querySelector('.trading-card')).filter(Boolean);

        if (quadros.length) {
            const topo = quadros[0].getBoundingClientRect().top - cartas[0].getBoundingClientRect().top;
            const altura = Math.max(...quadros.map((quadro) => quadro.offsetHeight));

            lista.style.setProperty('--filtros-topo', `${topo}px`);
            lista.style.setProperty('--filtros-altura', `${altura}px`);
        } else {
            // sem cartas: o painel fica no tamanho do próprio conteúdo
            lista.style.removeProperty('--filtros-topo');
            lista.style.removeProperty('--filtros-altura');
        }

        lista.classList.remove('is-medindo');
    };

    ajustar();
    window.addEventListener('load', ajustar);
    window.addEventListener('resize', ajustar);
    filtros.addEventListener('toggle', ajustar);
});

// Filtros que se aplicam sozinhos: qualquer form com data-auto-submit é
// enviado ao trocar uma opção (chips e selects). Campos de texto continuam
// sendo enviados com Enter ou pelo botão.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
        form.addEventListener('change', (event) => {
            if (event.target.matches('input[type="radio"], input[type="checkbox"], select')) {
                form.requestSubmit();
            }
        });
    });
});

// Filtros por GET: campos vazios não vão para a url (evita "?busca=&status=").
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('input, select').forEach((campo) => {
                const vazio = campo.type === 'radio' || campo.type === 'checkbox'
                    ? campo.checked && campo.value === ''
                    : campo.value.trim() === '';
                campo.disabled = vazio;
            });
        });

        // ao voltar com o botão "voltar" do navegador, reativa os campos
        window.addEventListener('pageshow', () => {
            form.querySelectorAll(':disabled').forEach((campo) => { campo.disabled = false; });
        });
    });
});

// Tema do site (claro / escuro / automático). A escolha fica salva no
// navegador; o script no <head> do layout já aplica ao carregar cada página.
// Aqui: o seletor em Configurações > Aparência e a troca automática quando o
// tema do aparelho muda (opção "Automático").
const temaDoSistema = window.matchMedia('(prefers-color-scheme: dark)');

const lerTema = () => {
    try {
        return localStorage.getItem('tema') || 'claro';
    } catch (e) {
        return 'claro';
    }
};

const aplicarTema = (tema) => {
    const escuro = tema === 'escuro' || (tema === 'sistema' && temaDoSistema.matches);
    if (escuro) {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
};

temaDoSistema.addEventListener('change', () => aplicarTema(lerTema()));

document.addEventListener('DOMContentLoaded', () => {
    const seletor = document.querySelector('[data-theme-switch]');

    if (!seletor) {
        return;
    }

    const atual = seletor.querySelector(`input[value="${lerTema()}"]`);
    if (atual) {
        atual.checked = true;
    }

    seletor.addEventListener('change', (event) => {
        const tema = event.target.value;
        try {
            localStorage.setItem('tema', tema);
        } catch (e) {
            // navegador sem armazenamento: o tema vale só até recarregar
        }
        aplicarTema(tema);
    });
});
