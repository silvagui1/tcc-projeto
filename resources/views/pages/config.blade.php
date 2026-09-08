@extends('layouts.app')

@section('title', 'Configurações')

@section('content')

    <div class="page-topbar">
        <a href="{{ route('home') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
        <p class="page-topbar__title">Configurações</p>
        <span style="width: 30px;"></span>
    </div>

    <h3 style="margin-bottom: 12px;">Conta atual</h3>

    <div class="settings-card">
        <div class="settings-row">
            <div class="settings-row__label">
                <span class="icon"><i class="bi bi-person-fill"></i></span>
                <div>
                    <span>Nome de usuário</span>
                    <strong>{{ $conta['usuario'] }}</strong>
                </div>
            </div>
            <a href="#"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="settings-row">
            <div class="settings-row__label">
                <span class="icon"><i class="bi bi-envelope-fill"></i></span>
                <div>
                    <span>E-mail</span>
                    <strong>{{ $conta['email'] }}</strong>
                </div>
            </div>
            <a href="#"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="settings-row">
            <div class="settings-row__label">
                <span class="icon"><i class="bi bi-lock-fill"></i></span>
                <div>
                    <span>Senha</span>
                    <strong>*********</strong>
                </div>
            </div>
            <a href="#"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <h3>Todas contas cadastradas</h3>

@endsection
