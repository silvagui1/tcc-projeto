import './bootstrap';

// Abre/fecha o menu em arco do botão flutuante inferior (mobile).
document.addEventListener('DOMContentLoaded', () => {
    const bottomNav = document.querySelector('[data-bottom-nav]');
    const toggle = document.querySelector('[data-bottom-nav-toggle]');

    if (!bottomNav || !toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        bottomNav.classList.toggle('is-open');
    });

    // fecha o menu se o usuário clicar fora dele
    document.addEventListener('click', (event) => {
        if (!bottomNav.contains(event.target)) {
            bottomNav.classList.remove('is-open');
        }
    });
});

// Abre/fecha o menu lateral em sobreposição (desktop).
//
// Ideia central: o JavaScript não mexe em estilo nenhum. Ele só liga e
// desliga UMA classe no <body> (.menu-aberto). Quem anima o painel,
// escurece o fundo e trava a rolagem é o CSS, reagindo a essa classe.
document.addEventListener('DOMContentLoaded', () => {
    const botoes = document.querySelectorAll('[data-menu-toggle]');
    const fundo = document.querySelector('[data-menu-backdrop]');
    const painel = document.querySelector('[data-menu-panel]');
    const fechar = document.querySelector('[data-menu-close]');

    if (!botoes.length || !painel) {
        return;
    }

    // guarda quem abriu o menu, para devolver o foco ao fechar
    let ultimoBotao = botoes[0];

    const estaAberto = () => document.body.classList.contains('menu-aberto');

    const abrirMenu = () => {
        document.body.classList.add('menu-aberto');
        botoes.forEach((b) => b.setAttribute('aria-expanded', 'true'));

        if (fundo) {
            fundo.hidden = false;
        }

        // manda o foco para dentro do menu, senão o teclado continua
        // navegando pelo conteúdo que está atrás da sobreposição
        const primeiroLink = painel.querySelector('a, button');
        if (primeiroLink) {
            primeiroLink.focus();
        }
    };

    const fecharMenu = () => {
        document.body.classList.remove('menu-aberto');
        botoes.forEach((b) => b.setAttribute('aria-expanded', 'false'));
        ultimoBotao.focus();

        // espera a transição terminar antes de esconder de vez
        window.setTimeout(() => {
            if (fundo && !estaAberto()) {
                fundo.hidden = true;
            }
        }, 250);
    };

    botoes.forEach((b) => {
        b.addEventListener('click', () => {
            ultimoBotao = b;
            estaAberto() ? fecharMenu() : abrirMenu();
        });
    });

    if (fundo) {
        fundo.addEventListener('click', fecharMenu);
    }

    if (fechar) {
        fechar.addEventListener('click', fecharMenu);
    }

    // Esc fecha: é o que o usuário espera de qualquer sobreposição
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && estaAberto()) {
            fecharMenu();
        }
    });
});
