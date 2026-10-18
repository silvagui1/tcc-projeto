@extends('errors.layout')

@section('codigo', 'Sessão expirada')
@section('icone', 'bi-clock-history')
@section('titulo', 'A sessão expirou')
@section('mensagem', 'Por segurança, a página ficou tempo demais aberta sem uso. Entre de novo e repita o que estava fazendo.')

@section('acoes')
    <a href="{{ url('/login') }}" class="erro__botao"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Entrar de novo</a>
@endsection
