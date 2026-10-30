import './clientes';
import './vendas';
import './configuracoes';
import './campeonatos';
import './bootstrap';
import { avisarDoServidor, iniciarCampoDeImagem } from './comum';
import './busca-geral';
import './atalhos';

// aviso deixado pelo servidor antes de um redirect (session('aviso'))
document.addEventListener('DOMContentLoaded', avisarDoServidor);

// <form data-enviar-ao-mudar>: envia sozinho ao trocar um <select> (ex.:
// "Ordenar por" do estoque). Sem JS, o <noscript> mostra o botão.
document.addEventListener('change', (evento) => {
    const form = evento.target.closest('form[data-enviar-ao-mudar]');
    if (form && evento.target.matches('select')) {
        form.requestSubmit();
    }
});

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

// Navbar do desktop — ao clicar numa página, o ícone dela (o mesmo do menu
// do celular) já começa a surgir enquanto a próxima página carrega; lá a
// animação de .is-current continua o efeito. Ctrl/Cmd+clique (nova aba) não
// anima, porque a página atual não muda.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.topbar__nav a').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || link.classList.contains('is-current')) {
                return;
            }
            document.querySelectorAll('.topbar__nav a.is-selecionando').forEach((outro) => outro.classList.remove('is-selecionando'));
            link.classList.add('is-selecionando');
        });
    });

    // voltando pelo "Voltar" do navegador a página pode vir do cache ainda
    // com o link clicado marcado
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll('.topbar__nav a.is-selecionando').forEach((link) => link.classList.remove('is-selecionando'));
        }
    });
});

// Estoque — pop-up de confirmação (arquivar / excluir de vez). Qualquer
// botão com data-delete-open="<url>" abre o <dialog> e usa a url como action
// do form; data-delete-title/-texto/-botao trocam os textos.
// (Campeonatos tem o próprio, em campeonatos.js.)
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-delete-dialog]');
    const form = dialog?.querySelector('[data-delete-form]');
    const titulo = dialog?.querySelector('[data-delete-title]');
    const texto = dialog?.querySelector('[data-delete-texto]');
    const botaoOk = dialog?.querySelector('[data-delete-botao]');

    if (!dialog || !form) {
        return;
    }

    document.querySelectorAll('[data-delete-open]').forEach((botao) => {
        botao.addEventListener('click', (event) => {
            // no estoque os botões ficam por cima do link que cobre a carta
            event.preventDefault();
            event.stopPropagation();
            form.action = botao.dataset.deleteOpen;
            if (titulo && botao.dataset.deleteTitle) {
                titulo.textContent = botao.dataset.deleteTitle;
            }
            if (texto) {
                texto.textContent = botao.dataset.deleteTexto || '';
                texto.hidden = !botao.dataset.deleteTexto;
            }
            if (botaoOk) {
                botaoOk.textContent = botao.dataset.deleteBotao || 'Confirmar';
            }
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

// Estoque — campo de imagem dos pop-ups (ver iniciarCampoDeImagem em comum.js).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-imagem-campo]').forEach(iniciarCampoDeImagem);
});

// Estoque — pop-ups de carta e de produto (adicionar / editar). Cada
// <dialog data-form-dialog="nome"> abre com os botões data-form-open="nome":
// sem data-form-dados o form vem vazio (valores de data-form-padrao) e
// cadastra; com data-form-dados (json do item) vem preenchido e envia um PUT
// para a url de edição. Se o form voltou com erros de validação, o pop-up já
// abre sozinho (data-open-on-load), no modo em que estava.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-form-dialog]').forEach((dialog) => {
        const form = dialog.querySelector('form');
        const metodo = form.querySelector('[data-form-metodo]');
        const editar = form.querySelector('[name="_editar"]');
        const titulo = form.querySelector('[data-form-titulo]');

        const preencher = (dados) => {
            Object.entries(dados).forEach(([campo, valor]) => {
                const input = form.elements.namedItem(campo);
                if (!input) {
                    return;
                }
                if (input.type === 'checkbox') {
                    input.checked = Boolean(valor);
                } else if (input.tagName === 'SELECT' && valor && !Array.from(input.options).some((o) => o.value === String(valor))) {
                    // valor que saiu da lista em Configurações (ex.: um estado
                    // de carta removido): entra como opção para não trocar o
                    // valor da carta sem a pessoa perceber
                    input.add(new Option(`${valor} (fora da lista)`, valor, true, true));
                } else {
                    input.value = valor ?? '';
                }
            });
        };

        const abrir = (dados) => {
            const editando = Boolean(dados);

            preencher(JSON.parse(dialog.dataset.formPadrao));
            if (editando) {
                preencher(dados);
            }
            // valores trocados via código não disparam "change"
            form.dispatchEvent(new Event('form-preenchido'));

            form.action = editando
                ? dialog.dataset.actionEditar.replace('__ID__', dados.id)
                : dialog.dataset.actionCriar;
            metodo.disabled = !editando;
            editar.value = editando ? dados.id : '';
            titulo.textContent = editando ? titulo.dataset.tituloEditar : titulo.dataset.tituloCriar;
            form.querySelector('[data-imagem-campo]')?.imagemDoEstoque?.reiniciar(editando ? dados.imagem_preview : '');

            // erros de uma tentativa anterior não valem para o novo item
            form.querySelector('[data-form-erros]')?.remove();

            dialog.showModal();
        };

        document.querySelectorAll(`[data-form-open="${dialog.dataset.formDialog}"]`).forEach((botao) => {
            botao.addEventListener('click', (event) => {
                // os botões ficam por cima do link que cobre a carta
                event.preventDefault();
                event.stopPropagation();
                abrir(botao.dataset.formDados ? JSON.parse(botao.dataset.formDados) : null);
            });
        });

        dialog.querySelectorAll('[data-form-close]').forEach((botao) => {
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

// Estoque — pop-up de carta: "Coleção" e "Raridade" sugerem os valores do
// jogo escolhido (um <datalist> por jogo, id = prefixo-jogo). Ao abrir o
// pop-up o jogo é preenchido sem disparar "change", por isso o campo também
// se ajusta ao receber foco.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-sugestoes-por-jogo]').forEach((campo) => {
        const jogo = campo.form.elements.namedItem('jogo');
        if (!jogo) {
            return;
        }

        const ajustar = () => campo.setAttribute('list', `${campo.dataset.sugestoesPorJogo}-${jogo.value}`);

        jogo.addEventListener('change', ajustar);
        campo.addEventListener('focus', ajustar);
    });
});

// Estoque — pop-up de produto: categoria não comercializável (item que só
// pode ser dado) desativa o preço. O servidor também salva esses itens com
// preço zero, já que um campo desativado não é enviado.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-preco-campo]').forEach((preco) => {
        const form = preco.form;
        const categoria = form.elements.namedItem('categoria_id');
        const aviso = form.querySelector('[data-preco-aviso]');
        if (!categoria) {
            return;
        }

        const ajustar = () => {
            const naoVende = Boolean(categoria.selectedOptions[0]?.hasAttribute('data-nao-comercializavel'));

            if (naoVende && !preco.disabled) {
                // guarda o preço digitado para devolver se a categoria mudar de novo
                preco.dataset.valorAnterior = preco.value;
                preco.value = '0';
            } else if (!naoVende && preco.disabled) {
                preco.value = preco.dataset.valorAnterior ?? '';
            }

            preco.disabled = naoVende;
            if (aviso) {
                aviso.hidden = !naoVende;
            }
        };

        categoria.addEventListener('change', ajustar);
        form.addEventListener('form-preenchido', () => {
            // ao abrir o pop-up o valor vem do produto (ou vazio), não do guardado
            delete preco.dataset.valorAnterior;
            preco.disabled = false;
            ajustar();
        });
        ajustar();
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
