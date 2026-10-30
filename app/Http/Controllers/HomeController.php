<?php

namespace App\Http\Controllers;

use App\Models\Aluguel;
use App\Models\Campeonato;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\View\View;

/**
 * Página inicial: o que importa para o dia de trabalho — vendas de hoje,
 * mesas reservadas, o que está acabando no estoque, os próximos campeonatos
 * e as últimas vendas.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            // cartões do topo: vendido hoje x ontem (vendas concluídas)
            'hoje' => Venda::resumoDoDia(today()),
            'ontem' => Venda::resumoDoDia(today()->subDay()),
            // comparação justa: ontem só até este mesmo horário
            'ontemAteAgora' => Venda::resumoEntre(today()->subDay(), now()->subDay()),
            // alerta de estoque baixo (Configurações > Estoque)
            'estoqueBaixo' => Produto::estoqueBaixo()->orderBy('quantidade')->get(),
            // mesas reservadas para hoje (inclui as que começaram ontem e passam da meia-noite)
            'alugueisHoje' => Aluguel::ativos()
                ->with(['mesa', 'cliente'])
                ->where('inicio', '<', today()->addDay())
                ->where('fim', '>', today())
                ->orderBy('inicio')
                ->get(),
            'proximosCampeonatos' => Campeonato::ativos()
                ->whereDate('data', '>=', today())
                ->withCount('participantes')
                ->orderBy('data')
                ->orderBy('horario')
                ->take(4)
                ->get(),
            'ultimasVendas' => Venda::with(['cliente', 'itens'])->latest()->latest('id')->take(5)->get(),
        ]);
    }
}
