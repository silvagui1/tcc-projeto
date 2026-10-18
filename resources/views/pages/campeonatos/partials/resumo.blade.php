{{-- Panorama geral (não muda com os filtros), como em Clientes e Vendas, e
     o aviso dos provisórios que esperam a decisão "vira cliente ou não". --}}
<div class="resumo-tira">
    <div class="resumo-item {{ $resumo['ativos'] > 0 ? 'resumo-item--destaque' : '' }}">
        <span class="resumo-item__valor">{{ $resumo['ativos'] }}</span>
        <span class="resumo-item__rotulo">
            Em andamento{{ $resumo['semanais'] ? ' · '.$resumo['semanais'].' '.($resumo['semanais'] === 1 ? 'semanal' : 'semanais') : '' }}
        </span>
    </div>
    <div class="resumo-item">
        <span class="resumo-item__valor">{{ $resumo['finalizados'] }}</span>
        <span class="resumo-item__rotulo">{{ $resumo['finalizados'] === 1 ? 'Finalizado' : 'Finalizados' }}</span>
    </div>
    <div class="resumo-item">
        <span class="resumo-item__valor">{{ $resumo['inscritos'] }}</span>
        <span class="resumo-item__rotulo">{{ $resumo['inscritos'] === 1 ? 'Inscrição em andamento' : 'Inscrições em andamento' }}</span>
    </div>
    <div class="resumo-item">
        <span class="resumo-item__valor">R$ {{ number_format($resumo['creditos'], 2, ',', '.') }}</span>
        <span class="resumo-item__rotulo">Em prêmios (créditos)</span>
    </div>
</div>

@if (count($provisoriosPendentes))
    <div class="champ-alerta" role="status">
        <i class="bi bi-person-exclamation champ-alerta__icone" aria-hidden="true"></i>
        <div class="champ-alerta__texto">
            <strong>
                {{ count($provisoriosPendentes) }}
                {{ count($provisoriosPendentes) === 1 ? 'participante provisório espera' : 'participantes provisórios esperam' }}
                a decisão: vira cliente ou não?
            </strong>
            <span>
                @foreach (array_slice($provisoriosPendentes, 0, 5) as $provisorio)
                    <a href="{{ route('campeonatos.show', $provisorio['campeonato']) }}"
                       data-champ-ir="{{ route('campeonatos.show', $provisorio['campeonato']) }}"
                       data-champ-estado="abrir={{ $provisorio['campeonato'] }}">{{ $provisorio['nome'] }}</a>{{ $loop->last ? '' : ' · ' }}
                @endforeach
                {{ count($provisoriosPendentes) > 5 ? ' e mais '.(count($provisoriosPendentes) - 5) : '' }}
            </span>
        </div>
    </div>
@endif
