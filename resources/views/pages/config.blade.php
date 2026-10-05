@extends('layouts.app')

@section('title', 'Configurações')

{{-- no desktop a página de configurações usa uma área mais larga (ver .app-content--wide) --}}
@section('main_class', 'app-content--wide')

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

    {{-- Tema do site: salvo neste navegador (localStorage). O app.js aplica na
         hora; o script no <head> do layout aplica nas próximas páginas. --}}
    <h3 style="margin-bottom: 12px;">Aparência</h3>

    <div class="settings-card">
        <div class="settings-row settings-row--tema">
            <div class="settings-row__label">
                <span class="icon"><i class="bi bi-moon-stars-fill"></i></span>
                <div>
                    <span>Tema</span>
                    <strong>Modo claro, escuro ou igual ao do seu aparelho</strong>
                </div>
            </div>

            <div class="theme-switch" role="radiogroup" aria-label="Tema do site" data-theme-switch>
                <label>
                    <input type="radio" name="tema" value="claro">
                    <i class="bi bi-sun-fill"></i> Claro
                </label>
                <label>
                    <input type="radio" name="tema" value="escuro">
                    <i class="bi bi-moon-fill"></i> Escuro
                </label>
                <label>
                    <input type="radio" name="tema" value="sistema">
                    <i class="bi bi-circle-half"></i> Automático
                </label>
            </div>
        </div>
    </div>

    <h3>Todas contas cadastradas</h3>

@endsection
