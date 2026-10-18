{{-- A própria conta de quem está conectado: dados, e-mail (opcional),
     troca de senha e sair (no celular é o único lugar com "Sair"; no
     desktop também fica na navbar). Aparece para todos os papéis. --}}
@php $conta = auth()->user(); @endphp
<section class="config-secao" id="conta" aria-labelledby="titulo-conta">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-person-circle"></i></span>
        <div>
            <h2 id="titulo-conta">Sua conta</h2>
            <p>Conta de acesso ao sistema interno da loja.</p>
        </div>
    </header>

    <div class="config-card">
        <div class="config-linha">
            <div class="config-linha__texto">
                <strong>{{ $conta->nome }}</strong>
                <span>
                    Usuário <b>{{ $conta->usuario }}</b> ·
                    <span class="usuario-papel usuario-papel--{{ $conta->papel }}">{{ $conta->papelRotulo() }}</span>
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="botao botao--neutro">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Sair
                </button>
            </form>
        </div>
    </div>

    <div class="config-colunas">
        {{-- E-mail: opcional, a própria pessoa vincula. O login continua
             sendo pelo usuário. --}}
        <form class="config-card" data-config-form action="{{ route('conta.email') }}" novalidate>
            <input type="hidden" name="_method" value="PUT">
            <div class="config-bloco">
                <h3>E-mail <span class="campo__opcional">(opcional)</span></h3>
                <p class="config-bloco__ajuda">Para contato da loja. Você continua entrando pelo usuário <b>{{ $conta->usuario }}</b>.</p>
                <div class="campo">
                    <label for="conta-email">E-mail</label>
                    <input type="email" id="conta-email" name="email" maxlength="120" autocomplete="email" placeholder="seu@email.com" value="{{ $conta->email }}">
                    <span class="campo__erro" data-erro="email" hidden></span>
                </div>
            </div>
            @include('pages.configuracoes._rodape_salvar')
        </form>

        {{-- Senha: tratada à parte em configuracoes.js (data-conta-senha) para
             os campos nunca irem parar no HTML e serem limpos depois de salvar. --}}
        <form class="config-card" data-conta-senha action="{{ route('conta.senha') }}" novalidate>
            <div class="config-bloco">
                <h3>Trocar senha</h3>
                <p class="config-bloco__ajuda">Os outros aparelhos conectados à sua conta serão desconectados.</p>
                <div class="campo">
                    <label for="conta-senha-atual">Senha atual</label>
                    <input type="password" id="conta-senha-atual" name="senha_atual" autocomplete="current-password">
                    <span class="campo__erro" data-erro="senha_atual" hidden></span>
                </div>
                <div class="campo">
                    <label for="conta-senha">Nova senha</label>
                    <input type="password" id="conta-senha" name="senha" autocomplete="new-password" aria-describedby="conta-senha-dica">
                    <span class="campo__erro" data-erro="senha" hidden></span>
                    <small class="config-bloco__ajuda" id="conta-senha-dica">Pelo menos 8 caracteres, com letras e números.</small>
                </div>
                <div class="campo">
                    <label for="conta-senha-confirmacao">Repita a nova senha</label>
                    <input type="password" id="conta-senha-confirmacao" name="senha_confirmation" autocomplete="new-password">
                </div>
            </div>
            <footer class="config-card__rodape">
                <span class="config-card__status"></span>
                <button type="submit" class="botao botao--principal" data-conta-senha-salvar>
                    <i class="bi bi-key" aria-hidden="true"></i>
                    Trocar senha
                </button>
            </footer>
        </form>
    </div>
</section>
