/**
 * Lógica da tela de Clientes: busca, seleção/exclusão em lote e os modais de
 * criar e editar cliente (incluindo o ajuste de créditos).
 *
 * Criar e editar são dois modais independentes (não um só que se adapta),
 * cada um com seu próprio formulário e sua própria foto de campo — o de
 * criação usa um círculo vazio clicável (estilo redes sociais), o de edição
 * mantém o avatar + botão "Editar foto".
 *
 * Escrito em JS puro (sem framework), no mesmo estilo do restante do
 * projeto, e só é inicializado quando a página atual é a de clientes.
 */

// Mesmos valores de app/Models/Cliente.php (CORES_AVATAR) e dos tokens
// --blue-400/--pink-400/--purple-500/--purple-light-800/--navy-900 em
// resources/css/app.css — manter as três listas sincronizadas.
const AVATAR_CORES = ['#8bbaed', '#b47194', '#53577d', '#6c6588', '#2e3045'];

function formatarMoeda(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function iniciarPaginaClientes() {
    const pagina = document.querySelector('[data-clientes-page]');
    if (!pagina) {
        return;
    }

    const listaWrapper = pagina.querySelector('[data-lista-wrapper]');
    const contador = pagina.querySelector('[data-contador]');
    const buscaForm = pagina.querySelector('[data-busca-form]');
    const buscaCampo = pagina.querySelector('[data-busca-campo]');
    const botaoAbrirCriar = pagina.querySelector('[data-abrir-criar]');
    const botaoAlternarSelecao = pagina.querySelector('[data-alternar-selecao]');
    const labelSelecao = pagina.querySelector('[data-label-selecao]');
    const barraSelecao = pagina.querySelector('[data-barra-selecao]');
    const selecaoContagem = pagina.querySelector('[data-selecao-contagem]');
    const botaoCancelarSelecao = pagina.querySelector('[data-cancelar-selecao]');
    const botaoConfirmarExclusao = pagina.querySelector('[data-confirmar-exclusao]');
    const mensagemEl = document.querySelector('[data-mensagem]');

    let termoAtual = '';
    let paginaAtual = 1;
    let modoSelecao = false;
    const selecionados = new Set();
    let timeoutBusca = null;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    function mostrarMensagem(texto, tipo = 'sucesso') {
        if (!mensagemEl) return;
        mensagemEl.textContent = texto;
        mensagemEl.hidden = false;
        mensagemEl.className = 'mensagem-flutuante mensagem-flutuante--' + tipo;
        window.clearTimeout(mensagemEl._timeout);
        mensagemEl._timeout = window.setTimeout(() => {
            mensagemEl.hidden = true;
        }, 4000);
    }

    async function carregarLista() {
        const url = new URL(window.location.origin + '/clientes/buscar');
        if (termoAtual) {
            url.searchParams.set('nome', termoAtual);
        }
        if (paginaAtual > 1) {
            url.searchParams.set('page', paginaAtual);
        }

        const resposta = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!resposta.ok) {
            mostrarMensagem('Não foi possível carregar a lista de clientes.', 'erro');
            return;
        }

        listaWrapper.innerHTML = await resposta.text();
        sairDoModoSelecao();
        atualizarContador();
    }

    function atualizarContador() {
        // O total vem do servidor (data-total na <ul>), não da contagem de
        // linhas na tela — com paginação, cada página só mostra 20 por vez.
        const lista = listaWrapper.querySelector('[data-lista]');
        const total = lista ? parseInt(lista.dataset.total || '0', 10) : 0;
        if (contador) {
            contador.textContent = total === 1 ? '1 cadastrado' : total + ' cadastrados';
        }
    }

    // ---- Busca ------------------------------------------------------------

    buscaForm.addEventListener('submit', (evento) => {
        evento.preventDefault();
        termoAtual = buscaCampo.value.trim();
        paginaAtual = 1;
        carregarLista();
    });

    buscaCampo.addEventListener('input', () => {
        window.clearTimeout(timeoutBusca);
        timeoutBusca = window.setTimeout(() => {
            termoAtual = buscaCampo.value.trim();
            paginaAtual = 1;
            carregarLista();
        }, 300);
    });

    // ---- Paginação ----------------------------------------------------------

    listaWrapper.addEventListener('click', (evento) => {
        const linkPagina = evento.target.closest('[data-pagina-link]');
        if (!linkPagina) return;

        evento.preventDefault();

        if (linkPagina.classList.contains('paginacao__link--desabilitado')) {
            return;
        }

        const novaPagina = parseInt(linkPagina.getAttribute('data-pagina'), 10);
        if (isNaN(novaPagina) || novaPagina < 1) return;

        paginaAtual = novaPagina;
        carregarLista();
        listaWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // ---- Modo de seleção (apagar clientes) ---------------------------------

    function atualizarBarraSelecao() {
        const total = selecionados.size;
        selecaoContagem.textContent = total === 1 ? '1 selecionado' : total + ' selecionados';
        botaoConfirmarExclusao.disabled = total === 0;
        botaoConfirmarExclusao.style.opacity = total === 0 ? '0.5' : '1';
    }

    function entrarNoModoSelecao() {
        modoSelecao = true;
        selecionados.clear();
        pagina.classList.add('modo-selecao');
        barraSelecao.hidden = false;
        labelSelecao.textContent = 'toque para selecionar';
        atualizarBarraSelecao();
    }

    function sairDoModoSelecao() {
        modoSelecao = false;
        selecionados.clear();
        pagina.classList.remove('modo-selecao');
        barraSelecao.hidden = true;
        labelSelecao.textContent = 'apagar clientes';
        listaWrapper.querySelectorAll('[data-cliente-linha]').forEach((linha) => {
            linha.classList.remove('cliente-linha--selecionado');
            const checkbox = linha.querySelector('[data-linha-checkbox-input]');
            if (checkbox) checkbox.checked = false;
        });
    }

    botaoAlternarSelecao.addEventListener('click', () => {
        if (modoSelecao) {
            sairDoModoSelecao();
        } else {
            entrarNoModoSelecao();
        }
    });

    botaoCancelarSelecao.addEventListener('click', sairDoModoSelecao);

    listaWrapper.addEventListener('click', (evento) => {
        const linha = evento.target.closest('[data-cliente-linha]');
        if (!linha) return;

        if (modoSelecao) {
            evento.preventDefault();
            const id = linha.getAttribute('data-id');
            const checkbox = linha.querySelector('[data-linha-checkbox-input]');
            const selecionado = selecionados.has(id);

            if (selecionado) {
                selecionados.delete(id);
            } else {
                selecionados.add(id);
            }

            linha.classList.toggle('cliente-linha--selecionado', !selecionado);
            if (checkbox) checkbox.checked = !selecionado;
            atualizarBarraSelecao();
            return;
        }

        const botaoEditar = evento.target.closest('[data-abrir-editar]');
        if (botaoEditar) {
            abrirModalEdicao(botaoEditar.getAttribute('data-id'));
        }
    });

    botaoConfirmarExclusao.addEventListener('click', async () => {
        if (selecionados.size === 0) return;

        const confirmado = window.confirm(
            'Tem certeza que deseja excluir ' + selecionados.size + ' cliente(s)? Essa ação não pode ser desfeita.'
        );
        if (!confirmado) return;

        try {
            const resposta = await fetch('/clientes', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids: Array.from(selecionados) }),
            });

            const dados = await resposta.json();

            if (!resposta.ok) {
                mostrarMensagem(dados.message || 'Não foi possível excluir os clientes selecionados.', 'erro');
                return;
            }

            mostrarMensagem(dados.message, 'sucesso');
            termoAtual = buscaCampo.value.trim();
            // Volta para a primeira página: a página em que o usuário estava
            // pode não existir mais depois de excluir os clientes dela.
            paginaAtual = 1;
            await carregarLista();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao excluir clientes.', 'erro');
        }
    });

    // ---- Modais de criar/editar --------------------------------------------
    // Wiring compartilhado entre os dois modais (fechar, foto, créditos,
    // envio) — cada modal tem seu próprio formulário/overlay, então isso é
    // configurado uma vez para cada um, sem misturar estado entre eles.

    function configurarModal(raiz) {
        const form = raiz.querySelector('[data-form-cliente]');
        const modalErros = raiz.querySelector('[data-modal-erros]');
        const inputCreditos = form.querySelector('[data-form-creditos]');
        const inputFoto = form.querySelector('[data-input-foto]');
        const botaoSelecionarFoto = form.querySelector('[data-selecionar-foto]');
        const previewAvatar = form.querySelector('[data-preview-avatar]');
        const previewImagem = form.querySelector('[data-preview-imagem]');
        const previewIniciais = form.querySelector('[data-preview-iniciais]');
        const iconeVazio = form.querySelector('[data-preview-icone-vazio]');
        const creditosExibicao = form.querySelector('[data-creditos-exibicao]');

        function abrir() {
            raiz.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function fechar() {
            raiz.hidden = true;
            document.body.style.overflow = '';
            form.reset();
            modalErros.hidden = true;
            modalErros.innerHTML = '';
            inputFoto.value = '';
        }

        raiz.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
            botao.addEventListener('click', fechar);
        });

        raiz.addEventListener('click', (evento) => {
            if (evento.target === raiz) fechar();
        });

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !raiz.hidden) fechar();
        });

        // Foto: seleção e pré-visualização (preenche a imagem escolhida e
        // esconde o que estava no lugar dela antes — iniciais na edição,
        // ícone de câmera na criação).
        botaoSelecionarFoto.addEventListener('click', () => inputFoto.click());

        inputFoto.addEventListener('change', () => {
            const arquivo = inputFoto.files[0];
            if (!arquivo) return;

            const leitor = new FileReader();
            leitor.onload = (evento) => {
                previewImagem.src = evento.target.result;
                previewImagem.hidden = false;
                if (previewIniciais) previewIniciais.hidden = true;
                if (iconeVazio) iconeVazio.hidden = true;
                botaoSelecionarFoto.classList.add('tem-foto');
            };
            leitor.readAsDataURL(arquivo);
        });

        // Ajuste de créditos (adicionar / descontar)
        form.querySelectorAll('[data-ajustar-creditos]').forEach((botao) => {
            botao.addEventListener('click', () => {
                const acao = botao.getAttribute('data-ajustar-creditos');
                const rotulo = acao === 'adicionar' ? 'adicionar' : 'descontar';
                const entrada = window.prompt('Valor a ' + rotulo + ' (R$):', '0,00');
                if (entrada === null) return;

                const valor = parseFloat(entrada.replace(/\./g, '').replace(',', '.'));
                if (isNaN(valor) || valor <= 0) {
                    mostrarMensagem('Informe um valor válido, maior que zero.', 'erro');
                    return;
                }

                const atual = parseFloat(inputCreditos.value) || 0;
                let novoValor = acao === 'adicionar' ? atual + valor : atual - valor;
                if (novoValor < 0) {
                    novoValor = 0;
                    mostrarMensagem('O cliente não possui créditos suficientes; saldo ajustado para R$ 0,00.', 'erro');
                }

                inputCreditos.value = novoValor.toFixed(2);
                creditosExibicao.textContent = formatarMoeda(novoValor);
            });
        });

        // Envio do formulário
        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const formData = new FormData(form);
            const botaoSalvar = form.querySelector('[data-botao-salvar]');
            const textoOriginal = botaoSalvar.textContent;
            botaoSalvar.disabled = true;
            botaoSalvar.textContent = 'Salvando...';

            try {
                const resposta = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const dados = await resposta.json();

                if (resposta.status === 422) {
                    modalErros.hidden = false;
                    const mensagens = Object.values(dados.errors || {}).flat();
                    modalErros.innerHTML = '<ul>' + mensagens.map((m) => `<li>${m}</li>`).join('') + '</ul>';
                    return;
                }

                if (!resposta.ok) {
                    mostrarMensagem(dados.message || 'Não foi possível salvar o cliente.', 'erro');
                    return;
                }

                mostrarMensagem(dados.message, 'sucesso');
                fechar();
                termoAtual = buscaCampo.value.trim();
                await carregarLista();
            } catch (erro) {
                mostrarMensagem('Erro de conexão ao salvar cliente.', 'erro');
            } finally {
                botaoSalvar.disabled = false;
                botaoSalvar.textContent = textoOriginal;
            }
        });

        return {
            form,
            abrir,
            fechar,
            inputCreditos,
            creditosExibicao,
            previewAvatar,
            previewImagem,
            previewIniciais,
            iconeVazio,
            botaoSelecionarFoto,
        };
    }

    const modalCriar = configurarModal(document.querySelector('[data-modal-criar]'));
    const modalEditar = configurarModal(document.querySelector('[data-modal-editar]'));

    function abrirModalCriacao() {
        modalCriar.form.reset();
        modalCriar.inputCreditos.value = '0';
        modalCriar.creditosExibicao.textContent = formatarMoeda(0);
        modalCriar.previewImagem.hidden = true;
        modalCriar.previewImagem.src = '';
        if (modalCriar.iconeVazio) modalCriar.iconeVazio.hidden = false;
        modalCriar.botaoSelecionarFoto.classList.remove('tem-foto');
        modalCriar.abrir();
        window.setTimeout(() => modalCriar.form.querySelector('[data-input-nome]').focus(), 50);
    }

    async function abrirModalEdicao(id) {
        try {
            const resposta = await fetch('/clientes/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!resposta.ok) {
                mostrarMensagem('Não foi possível carregar os dados do cliente.', 'erro');
                return;
            }

            const cliente = await resposta.json();
            const { form, previewAvatar, previewImagem, previewIniciais, inputCreditos, creditosExibicao } = modalEditar;

            form.reset();
            form.action = form.dataset.urlBase + '/' + cliente.id;
            form.querySelector('[data-input-nome]').value = cliente.nome;
            form.querySelector('[data-input-nascimento]').value = cliente.data_nascimento;
            form.querySelector('[data-input-observacoes]').value = cliente.observacoes || '';
            inputCreditos.value = cliente.creditos;
            creditosExibicao.textContent = formatarMoeda(cliente.creditos);
            modalEditar.botaoSelecionarFoto.classList.remove('tem-foto');

            if (cliente.foto_url) {
                previewImagem.hidden = false;
                previewImagem.src = cliente.foto_url;
                previewIniciais.hidden = true;
                previewAvatar.style.backgroundColor = '';
            } else {
                previewImagem.hidden = true;
                previewImagem.src = '';
                previewIniciais.hidden = false;
                previewIniciais.textContent = cliente.iniciais || '--';
                previewAvatar.style.backgroundColor = cliente.cor_avatar || AVATAR_CORES[0];
            }

            modalEditar.abrir();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao carregar cliente.', 'erro');
        }
    }

    botaoAbrirCriar.addEventListener('click', abrirModalCriacao);
    pagina.addEventListener('cliente:novo', abrirModalCriacao);
}

document.addEventListener('DOMContentLoaded', iniciarPaginaClientes);
