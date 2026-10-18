{{-- Contas da equipe (só admin). A lista é desenhada por configuracoes.js a
     partir de data-itens; cada ação é salva na hora. Conta nova ou senha
     redefinida gera uma senha provisória, mostrada uma única vez. --}}
<section class="config-secao" id="usuarios" aria-labelledby="titulo-usuarios">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-person-gear"></i></span>
        <div>
            <h2 id="titulo-usuarios">Usuários</h2>
            <p>Só quem tem conta entra no sistema — não existe cadastro pelo site. Contas são desativadas, nunca apagadas, para o histórico continuar mostrando quem fez o quê.</p>
        </div>
    </header>

    <div class="config-card" data-usuarios data-url="{{ route('config.usuarios') }}" data-itens='@json($usuarios)'>
        <div class="config-bloco config-bloco__cabecalho">
            <div>
                <h3>Equipe</h3>
                <p class="config-bloco__ajuda" data-usuarios-resumo></p>
            </div>
            <button type="button" class="botao botao--principal" data-usuario-novo>
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Novo usuário
            </button>
        </div>
        <ul class="usuarios__lista" data-usuarios-lista></ul>
    </div>

    <div class="config-card usuarios__papeis">
        <div class="config-bloco">
            <h3>O que cada papel pode fazer</h3>
            <dl>
                <div>
                    <dt><span class="usuario-papel usuario-papel--admin">Administrador</span></dt>
                    <dd>Tudo: o dia a dia da loja, as configurações, os usuários, as exportações e o registro de atividades.</dd>
                </div>
                <div>
                    <dt><span class="usuario-papel usuario-papel--intermediario">Intermediário</span></dt>
                    <dd>O dia a dia da loja: vendas (inclusive cancelar), mesas e aluguéis, clientes (inclusive ajustar créditos), estoque e campeonatos. Em Configurações vê só a própria conta e a aparência.</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Criar / editar usuário --}}
    <div class="modal-overlay modal-overlay--confirmar" data-usuario-modal hidden>
        <form class="modal-usuario" role="dialog" aria-modal="true" aria-labelledby="usuario-modal-titulo" novalidate data-usuario-form>
            <header class="modal-usuario__topo">
                <h2 id="usuario-modal-titulo" data-usuario-modal-titulo>Novo usuário</h2>
                <button type="button" class="config-icone-botao" data-usuario-fechar aria-label="Fechar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="campo">
                <label for="usuario-nome">Nome</label>
                <input type="text" id="usuario-nome" name="nome" maxlength="80" autocomplete="off" required>
                <span class="campo__erro" data-erro="nome" hidden></span>
            </div>
            <div class="campo">
                <label for="usuario-usuario">Usuário <span class="campo__opcional">(usado para entrar)</span></label>
                <input type="text" id="usuario-usuario" name="usuario" maxlength="30" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="ex.: maria" required>
                <span class="campo__erro" data-erro="usuario" hidden></span>
            </div>
            <div class="campo">
                <label for="usuario-papel">Papel</label>
                <select id="usuario-papel" name="papel">
                    @foreach (\App\Models\User::PAPEIS as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($valor === 'intermediario')>{{ $rotulo }}</option>
                    @endforeach
                </select>
                <span class="campo__erro" data-erro="papel" hidden></span>
                <small class="config-bloco__ajuda" data-usuario-papel-aviso hidden>Você não pode mudar o seu próprio papel.</small>
            </div>
            <p class="config-bloco__ajuda" data-usuario-aviso-senha>
                <i class="bi bi-key" aria-hidden="true"></i>
                Uma senha provisória será gerada. A pessoa cria a própria senha no primeiro acesso.
            </p>

            <div class="modal-confirmar__acoes">
                <button type="submit" class="botao botao--principal botao--full" data-usuario-salvar>Criar conta</button>
                <button type="button" class="botao botao--neutro botao--full" data-usuario-fechar>Cancelar</button>
            </div>
        </form>
    </div>

    {{-- Senha provisória: mostrada uma vez só --}}
    <div class="modal-overlay modal-overlay--confirmar" data-senha-modal hidden>
        <div class="modal-usuario" role="alertdialog" aria-modal="true" aria-labelledby="senha-modal-titulo" aria-describedby="senha-modal-texto">
            <i class="bi bi-key-fill modal-confirmar__icone modal-usuario__icone-senha" aria-hidden="true"></i>
            <h2 id="senha-modal-titulo">Senha provisória</h2>
            <p id="senha-modal-texto">
                Passe para <strong data-senha-nome></strong> entrar com o usuário <strong data-senha-usuario></strong>.
                No primeiro acesso a pessoa cria a própria senha.
            </p>
            <div class="senha-provisoria">
                <code data-senha-valor></code>
                <button type="button" class="config-icone-botao" data-senha-copiar title="Copiar" aria-label="Copiar senha">
                    <i class="bi bi-copy" aria-hidden="true"></i>
                </button>
            </div>
            <p class="config-bloco__ajuda senha-provisoria__aviso">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                Ela não aparece de novo. Se perder, redefina a senha.
            </p>
            <div class="modal-confirmar__acoes">
                <button type="button" class="botao botao--principal botao--full" data-senha-ok>Anotei a senha</button>
            </div>
        </div>
    </div>
</section>
