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
