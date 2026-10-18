@extends('errors.layout')

@section('codigo', 'Erro 429')
@section('icone', 'bi-hourglass-split')
@section('titulo', 'Muitas tentativas')
@section('mensagem', 'Foram feitas muitas tentativas em pouco tempo. Aguarde um minuto e tente de novo.')
