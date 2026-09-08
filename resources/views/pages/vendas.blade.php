@extends('layouts.app')

@section('title', 'Vendas')

@section('content')

    <div class="page-topbar">
        <p class="page-topbar__title" style="font-size: 20px;">Vendas</p>
    </div>

    {{-- Assim como "Clientes", esta tela ainda não existe no protótipo do
         Figma — placeholder para o menu inferior/lateral não ficar quebrado. --}}
    <div class="empty-state">
        <div class="icon"><i class="bi bi-credit-card"></i></div>
        <h2>Em construção</h2>
        <p>A tela de vendas ainda não foi desenhada no protótipo.</p>
    </div>

@endsection
