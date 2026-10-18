@extends('layouts.acesso')

@section('title', 'Crie a sua senha')

{{-- Obrigatória depois de entrar com a senha provisória que o admin passou
     (conta nova ou senha redefinida). O resto do sistema fica bloqueado
     até aqui (middleware senha.trocada). --}}
@section('conteudo')
    <header class="login__cabecalho">
        <span class="login__cadeado" aria-hidden="true"><i class="bi bi-key-fill"></i></span>
        <h2>Olá, {{ auth()->user()->primeiroNome() }}! Crie a sua senha</h2>
        <p>Você entrou com uma senha provisória. Escolha uma senha só sua para continuar — nem o administrador vai saber qual é.</p>
    </header>

    @include('auth.partials.alertas')

    <form method="POST" action="{{ route('conta.criar-senha.salvar') }}" class="login__form" novalidate>
        @csrf
        @method('PUT')

        <div class="login__campo">
            <label for="senha">Nova senha</label>
            <div class="login__input">
                <i class="bi bi-key" aria-hidden="true"></i>
                <input type="password" id="senha" name="senha" autocomplete="new-password" required autofocus
                       @error('senha') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="login__campo">
            <label for="senha_confirmation">Repita a nova senha</label>
            <div class="login__input">
                <i class="bi bi-key" aria-hidden="true"></i>
                <input type="password" id="senha_confirmation" name="senha_confirmation" autocomplete="new-password" required>
            </div>
        </div>

        <p class="login__dica">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            Pelo menos 8 caracteres, com letras e números.
        </p>

        <button type="submit" class="login__entrar">
            Salvar e entrar
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="login__sem-conta login__sem-conta--sair">
        @csrf
        <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
        <p>Não é você? <button type="submit" class="login__link">Sair</button></p>
    </form>
@endsection
