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

// Estoque — pop-ups "Adicionar carta" e "Adicionar produto". Cada <dialog
// data-card-dialog="nome"> abre pelos botões com data-card-dialog-open="nome";
// se o form voltou com erros de validação, o pop-up já abre sozinho
// (data-open-on-load).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-card-dialog]').forEach((dialog) => {
        document.querySelectorAll(`[data-card-dialog-open="${dialog.dataset.cardDialog}"]`).forEach((botao) => {
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
});

// Campeonatos — carrossel dos ativos (mobile). Ao arrastar, a bolinha do card
// mais próximo do centro fica marcada; tocar numa bolinha rola até o card.
document.addEventListener('DOMContentLoaded', () => {
    const carrossel = document.querySelector('[data-carousel]');
    const bolinhas = [...(document.querySelectorAll('[data-carousel-dots] button'))];

    if (!carrossel || !bolinhas.length) {
        return;
    }

    const cards = [...carrossel.querySelectorAll('[data-carousel-item]')];

    const marcar = (indice) => {
        bolinhas.forEach((bolinha, i) => {
            bolinha.classList.toggle('is-active', i === indice);
            bolinha.toggleAttribute('aria-current', i === indice);
        });
    };

    carrossel.addEventListener('scroll', () => {
        const centro = carrossel.scrollLeft + carrossel.clientWidth / 2;
        const distancias = cards.map((card) => Math.abs(card.offsetLeft - carrossel.offsetLeft + card.offsetWidth / 2 - centro));
        marcar(distancias.indexOf(Math.min(...distancias)));
    }, { passive: true });

    bolinhas.forEach((bolinha, i) => {
        bolinha.addEventListener('click', () => {
            cards[i]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        });
    });
});

// Estoque — campo de imagem ([data-image-drop]): arrastar um arquivo para a
// zona, colar (Ctrl+V) uma imagem copiada com o formulário aberto ou clicar
// para escolher. O arquivo vai para o <input type="file"> e aparece a prévia.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-image-drop]').forEach((campo) => {
        const zona = campo.querySelector('[data-image-drop-zona]');
        const input = campo.querySelector('[data-image-drop-input]');
        const previa = campo.querySelector('[data-image-drop-previa]');
        const nome = campo.querySelector('[data-image-drop-nome]');
        const remover = campo.querySelector('[data-image-drop-remover]');
        const vazio = zona.querySelector('.image-drop__vazio');
        const dialog = campo.closest('dialog');

        const mostrar = () => {
            const arquivo = input.files[0];
            if (previa.src.startsWith('blob:')) {
                URL.revokeObjectURL(previa.src);
            }

            vazio.hidden = !!arquivo;
            previa.parentElement.hidden = !arquivo;
            remover.hidden = !arquivo;

            if (arquivo) {
                previa.src = URL.createObjectURL(arquivo);
                nome.textContent = arquivo.name;
            } else {
                previa.removeAttribute('src');
            }
        };

        // coloca o arquivo no input (arrastar e colar não passam pelo seletor)
        const usar = (arquivo) => {
            if (!arquivo || !arquivo.type.startsWith('image/')) {
                return false;
            }
            const lista = new DataTransfer();
            lista.items.add(arquivo);
            input.files = lista.files;
            mostrar();
            return true;
        };

        input.addEventListener('change', mostrar);

        remover.addEventListener('click', () => {
            input.value = '';
            mostrar();
        });

        ['dragenter', 'dragover'].forEach((tipo) => zona.addEventListener(tipo, (event) => {
            event.preventDefault();
            zona.classList.add('is-arrastando');
        }));

        ['dragleave', 'drop'].forEach((tipo) => zona.addEventListener(tipo, () => {
            zona.classList.remove('is-arrastando');
        }));

        zona.addEventListener('drop', (event) => {
            event.preventDefault();
            usar([...event.dataTransfer.files].find((arquivo) => arquivo.type.startsWith('image/')));
        });

        // colar: só com o pop-up aberto. Se a área de transferência também tem
        // texto e o cursor está num campo de texto, deixa colar o texto normal.
        document.addEventListener('paste', (event) => {
            if (dialog && !dialog.open) {
                return;
            }

            const imagem = [...event.clipboardData.files].find((arquivo) => arquivo.type.startsWith('image/'));
            const emCampoDeTexto = event.target.matches?.('input:not([type="file"]), textarea');
            if (!imagem || (emCampoDeTexto && event.clipboardData.getData('text/plain'))) {
                return;
            }

            event.preventDefault();
            usar(imagem);
        });

        // o "Cancelar" limpa o form: a prévia some junto
        campo.closest('form')?.addEventListener('reset', () => setTimeout(mostrar));
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
