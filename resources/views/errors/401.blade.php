@extends('errors.layout')

@section('codigo', 'Erro 401')
@section('icone', 'bi-person-lock')
@section('titulo', 'Entre para continuar')
@section('mensagem', 'Você não está conectado ou a sua sessão foi encerrada. Entre de novo com o seu usuário e senha.')

@section('acoes')
    <a href="{{ url('/login') }}" class="erro__botao"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Entrar de novo</a>
@endsection
