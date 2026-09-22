/**
 * Lógica da tela de Clientes: busca, seleção/exclusão em lote e o modal de
 * criar/editar cliente (incluindo o ajuste de créditos).
 *
 * Escrito em JS puro (sem framework), no mesmo estilo do restante do
 * projeto, e só é inicializado quando a página atual é a de clientes.
 */

const AVATAR_CORES = ['#68a6e9', '#e07180', '#87ca9e', '#d3c37e', '#a3a7ae'];

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

    const overlay = document.querySelector('[data-modal-overlay]');
    const form = document.querySelector('[data-form-cliente]');
    const modalTitulo = document.querySelector('[data-modal-titulo]');
    const modalErros = document.querySelector('[data-modal-erros]');
    const inputId = form.querySelector('[data-form-id]');
    const inputMethod = form.querySelector('[data-form-method]');
    const inputCreditos = form.querySelector('[data-form-creditos]');
    const inputNome = form.querySelector('[data-input-nome]');
    const inputNascimento = form.querySelector('[data-input-nascimento]');
    const inputObservacoes = form.querySelector('[data-input-observacoes]');
    const inputFoto = form.querySelector('[data-input-foto]');
    const botaoSelecionarFoto = form.querySelector('[data-selecionar-foto]');
    const previewAvatar = form.querySelector('[data-preview-avatar]');
    const previewImagem = form.querySelector('[data-preview-imagem]');
    const previewIniciais = form.querySelector('[data-preview-iniciais]');
    const creditosExibicao = form.querySelector('[data-creditos-exibicao]');

    let termoAtual = '';
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
        const total = listaWrapper.querySelectorAll('[data-cliente-linha]').length;
        if (contador) {
            contador.textContent = total === 1 ? '1 cadastrado' : total + ' cadastrados';
        }
    }

    // ---- Busca ------------------------------------------------------------

    buscaForm.addEventListener('submit', (evento) => {
        evento.preventDefault();
        termoAtual = buscaCampo.value.trim();
        carregarLista();
    });

    buscaCampo.addEventListener('input', () => {
        window.clearTimeout(timeoutBusca);
        timeoutBusca = window.setTimeout(() => {
            termoAtual = buscaCampo.value.trim();
            carregarLista();
        }, 300);
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
            await carregarLista();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao excluir clientes.', 'erro');
        }
    });

    // ---- Modal de criar/editar ---------------------------------------------

    function limparPreviewFoto(iniciais, cor) {
        previewImagem.hidden = true;
        previewImagem.src = '';
        previewIniciais.hidden = false;
        previewIniciais.textContent = iniciais || '--';
        previewAvatar.style.backgroundColor = cor || AVATAR_CORES[0];
    }

    function abrirModal() {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function fecharModal() {
        overlay.hidden = true;
        document.body.style.overflow = '';
        form.reset();
        modalErros.hidden = true;
        modalErros.innerHTML = '';
        inputFoto.value = '';
    }

    function abrirModalCriacao() {
        form.reset();
        inputId.value = '';
        inputMethod.value = 'POST';
        inputCreditos.value = '0';
        creditosExibicao.textContent = formatarMoeda(0);
        modalTitulo.textContent = 'Adicionar cliente';
        modalErros.hidden = true;
        modalErros.innerHTML = '';
        limparPreviewFoto('--', AVATAR_CORES[0]);
        abrirModal();
        window.setTimeout(() => inputNome.focus(), 50);
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

            form.reset();
            inputId.value = cliente.id;
            inputMethod.value = 'PUT';
            inputNome.value = cliente.nome;
            inputNascimento.value = cliente.data_nascimento;
            inputObservacoes.value = cliente.observacoes || '';
            inputCreditos.value = cliente.creditos;
            creditosExibicao.textContent = formatarMoeda(cliente.creditos);
            modalTitulo.textContent = 'Editar cliente';
            modalErros.hidden = true;
            modalErros.innerHTML = '';

            if (cliente.foto_url) {
                previewImagem.hidden = false;
                previewImagem.src = cliente.foto_url;
                previewIniciais.hidden = true;
                previewAvatar.style.backgroundColor = '';
            } else {
                limparPreviewFoto(cliente.iniciais, cliente.cor_avatar);
            }

            abrirModal();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao carregar cliente.', 'erro');
        }
    }

    botaoAbrirCriar.addEventListener('click', abrirModalCriacao);
    pagina.addEventListener('cliente:novo', abrirModalCriacao);

    document.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
        botao.addEventListener('click', fecharModal);
    });

    overlay.addEventListener('click', (evento) => {
        if (evento.target === overlay) fecharModal();
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !overlay.hidden) fecharModal();
    });

    // Foto: seleção e pré-visualização
    botaoSelecionarFoto.addEventListener('click', () => inputFoto.click());

    inputFoto.addEventListener('change', () => {
        const arquivo = inputFoto.files[0];
        if (!arquivo) return;

        const leitor = new FileReader();
        leitor.onload = (evento) => {
            previewImagem.src = evento.target.result;
            previewImagem.hidden = false;
            previewIniciais.hidden = true;
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

    // Envio do formulário (criação ou edição)
    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const editando = Boolean(inputId.value);
        const url = editando ? '/clientes/' + inputId.value : '/clientes';
        const formData = new FormData(form);

        const botaoSalvar = form.querySelector('[data-botao-salvar]');
        botaoSalvar.disabled = true;
        botaoSalvar.textContent = 'Salvando...';

        try {
            const resposta = await fetch(url, {
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
            fecharModal();
            termoAtual = buscaCampo.value.trim();
            await carregarLista();
        } catch (erro) {
            mostrarMensagem('Erro de conexão ao salvar cliente.', 'erro');
        } finally {
            botaoSalvar.disabled = false;
            botaoSalvar.textContent = 'Salvar alterações';
        }
    });
}

document.addEventListener('DOMContentLoaded', iniciarPaginaClientes);
