@extends('layouts.acesso')

@section('title', 'Acesso da equipe')

@section('conteudo')
    <header class="login__cabecalho">
        <span class="login__cadeado" aria-hidden="true"><i class="bi bi-person-badge-fill"></i></span>
        <h2>Acesso da equipe</h2>
        <p>Entre com o usuário e a senha que o administrador da loja criou para você.</p>
    </header>

    @include('auth.partials.alertas')

    <form method="POST" action="{{ route('login.entrar') }}" class="login__form" novalidate>
        @csrf

        <div class="login__campo">
            <label for="usuario">Usuário</label>
            <div class="login__input">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}"
                       autocomplete="username" autocapitalize="none" spellcheck="false"
                       maxlength="30" required autofocus
                       @error('usuario') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="login__campo">
            <label for="password">Senha</label>
            <div class="login__input">
                <i class="bi bi-key" aria-hidden="true"></i>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required
                       @error('usuario') aria-invalid="true" @enderror>
                <button type="button" class="login__ver-senha" data-ver-senha aria-label="Mostrar senha" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <label class="login__lembrar">
            <input type="checkbox" name="lembrar" value="1" @checked(old('lembrar'))>
            <span>
                Manter conectado por 30 dias
                <small>Use só no seu aparelho pessoal, nunca no computador do balcão.</small>
            </span>
        </label>

        <button type="submit" class="login__entrar">
            Entrar no sistema
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
    </form>

    <div class="login__sem-conta">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <p>
            <strong>Não tem acesso?</strong>
            Não existe cadastro pelo site. As contas da equipe são criadas pelo administrador da loja.
        </p>
    </div>
@endsection
