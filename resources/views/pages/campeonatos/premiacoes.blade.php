@extends('layouts.app')

@section('title', 'Premiações')

@section('content')

    <div class="podium-hero">
        <div class="podium">
            <div class="podium__place" style="height: 90px;">
                <span class="avatar" style="background-image: url('{{ $podio[1]['avatar'] }}');"></span>
                <strong>2°</strong>
            </div>
            <div class="podium__place podium__place--first" style="height: 121px;">
                <span class="avatar" style="background-image: url('{{ $podio[0]['avatar'] }}');"></span>
                <strong>1°</strong>
            </div>
            <div class="podium__place" style="height: 74px;">
                <span class="avatar" style="background-image: url('{{ $podio[2]['avatar'] }}');"></span>
                <strong>3°</strong>
            </div>
        </div>
    </div>

    <div class="credit-transfer-list">
        @foreach ($podio as $colocado)
            <div class="credit-transfer-row">
                <span class="avatar" style="background-image: url('{{ $colocado['avatar'] }}');"></span>
                <strong>{{ $colocado['nome'] }}</strong>
                <button type="button">transferir créditos</button>
            </div>
        @endforeach
    </div>

@endsection
