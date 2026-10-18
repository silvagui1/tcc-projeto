{{-- Card de campeonato da lista. Em andamento: data relativa ("Hoje",
     "Amanhã"...), inscritos e pagos, e o atalho para o check-in no dia.
     Finalizado: quem venceu e o atalho "Repetir". --}}
@php
    $finalizado = $campeonato['status'] === 'finalizado';
    $inscritos = count($campeonato['participantesLista']);
    $cobra = $campeonato['inscricao'] > 0;
    $ver = ['url' => route('campeonatos.show', $campeonato['id']), 'estado' => 'abrir='.$campeonato['id']];
    // no dia, o botão principal abre direto o check-in
    $principal = $campeonato['hoje'] && ! $finalizado
        ? ['url' => route('campeonatos.show', ['campeonato' => $campeonato['id'], 'modo' => 'checkin']), 'estado' => 'checkin='.$campeonato['id'], 'rotulo' => 'Abrir check-in', 'icone' => 'bi-person-check']
        : $ver + ['rotulo' => $finalizado ? 'Ver resultado' : ($inscritos ? 'Abrir' : 'Inscrever participantes'), 'icone' => 'bi-arrow-right'];
@endphp

<article class="champ-card {{ $finalizado ? 'champ-card--finalizado' : '' }} {{ $campeonato['hoje'] && ! $finalizado ? 'champ-card--hoje' : '' }}">
    <a href="{{ $ver['url'] }}" class="champ-card__banner" tabindex="-1" aria-hidden="true"
       data-champ-ir="{{ $ver['url'] }}" data-champ-estado="{{ $ver['estado'] }}">
        <img src="{{ $campeonato['imagem'] }}" alt="" loading="lazy"
             class="{{ $campeonato['bannerSistema'] ? 'champ-banner--sistema' : '' }}"
             data-banner-reserva="{{ $campeonato['imagemPadrao'] }}">
        <span class="champ-card__data" aria-hidden="true">
            <strong>{{ $campeonato['dataDia'] }}</strong>
            <span>{{ $campeonato['dataMes'] }}</span>
        </span>
        @if ($campeonato['recorrencia'] && ! $finalizado)
            <span class="champ-banner-selo"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> {{ $campeonato['recorrencia'] }}</span>
        @endif
    </a>

    <div class="champ-card__corpo">
        <div class="champ-card__topo">
            @if ($finalizado)
                <span class="champ-quando">{{ $campeonato['data'] }}</span>
            @else
                <span class="champ-quando {{ $campeonato['hoje'] ? 'champ-quando--hoje' : '' }} {{ $campeonato['atrasado'] ? 'champ-quando--atrasado' : '' }}">
                    @if ($campeonato['hoje'])
                        <span class="champ-quando__ponto" aria-hidden="true"></span>
                    @endif
                    {{ $campeonato['quando'] }}{{ $campeonato['horario'] ? ' · '.$campeonato['horario'] : '' }}
                    @if ($campeonato['atrasado'])
                        · finalize ou apague
                    @endif
                </span>
            @endif
            <span class="champ-tipo {{ $campeonato['oficial'] ? 'champ-tipo--oficial' : '' }}" title="{{ $campeonato['tipoEstrutura'] }}">
                <i class="bi {{ $campeonato['tipoIcone'] }}" aria-hidden="true"></i>{{ $campeonato['tipoRotulo'] }}
            </span>
        </div>

        <h3 class="champ-card__nome">
            <a href="{{ $ver['url'] }}" data-champ-ir="{{ $ver['url'] }}" data-champ-estado="{{ $ver['estado'] }}">{{ $campeonato['nome'] }}</a>
        </h3>

        @if ($finalizado)
            @if (count($campeonato['vencedores']))
                <p class="champ-card__vencedor">
                    <i class="bi bi-trophy-fill" aria-hidden="true"></i>
                    <span>{{ $campeonato['vencedores'][0]['nome'] }}</span>
                    <strong>+R$ {{ number_format($campeonato['vencedores'][0]['credito'], 2, ',', '.') }}</strong>
                </p>
            @endif
            <ul class="champ-meta">
                <li><i class="bi bi-people" aria-hidden="true"></i>{{ $inscritos }} {{ $inscritos === 1 ? 'participante' : 'participantes' }}</li>
                <li><i class="bi bi-collection" aria-hidden="true"></i>{{ $campeonato['deck'] }}</li>
            </ul>
        @else
            <ul class="champ-meta">
                <li><i class="bi bi-people" aria-hidden="true"></i>{{ $inscritos }} {{ $inscritos === 1 ? 'inscrito' : 'inscritos' }}{{ $cobra && $inscritos ? ' · '.$campeonato['pagos'].' '.($campeonato['pagos'] === 1 ? 'pago' : 'pagos') : '' }}</li>
                <li><i class="bi bi-ticket-perforated" aria-hidden="true"></i>{{ $cobra ? 'R$ '.number_format($campeonato['inscricao'], 2, ',', '.') : 'Gratuita' }}</li>
                @if (isset($campeonato['premios'][1]))
                    <li><i class="bi bi-coin" aria-hidden="true"></i>1° ganha R$ {{ number_format($campeonato['premios'][1], 2, ',', '.') }}</li>
                @endif
            </ul>

            {{-- quanto da inscrição já entrou --}}
            @if ($cobra && $inscritos)
                <div class="champ-card__caixa" title="{{ $campeonato['pagos'] }} de {{ $inscritos }} inscrições pagas">
                    <span class="champ-barra"><span style="width: {{ round($campeonato['pagos'] / $inscritos * 100) }}%"></span></span>
                </div>
            @endif
        @endif

        <div class="champ-card__rodape">
            <a href="{{ $principal['url'] }}" class="botao botao--pequeno {{ $finalizado ? 'botao--neutro' : 'botao--principal' }}"
               data-champ-ir="{{ $principal['url'] }}" data-champ-estado="{{ $principal['estado'] }}">
                @if ($principal['icone'] !== 'bi-arrow-right')
                    <i class="bi {{ $principal['icone'] }}" aria-hidden="true"></i>
                @endif
                {{ $principal['rotulo'] }}
                @if ($principal['icone'] === 'bi-arrow-right')
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                @endif
            </a>
            <span class="champ-actions">
                @if ($finalizado)
                    {{-- semanal já se repete sozinho --}}
                    @unless ($campeonato['semanal'])
                        <a href="{{ route('campeonatos.criar', ['copiar' => $campeonato['id']]) }}" title="Repetir (criar outro igual)" aria-label="Repetir {{ $campeonato['nome'] }}"
                           data-champ-ir="{{ route('campeonatos.criar', ['copiar' => $campeonato['id']]) }}" data-champ-estado="copiar={{ $campeonato['id'] }}">
                            <i class="bi bi-copy" aria-hidden="true"></i>
                        </a>
                    @endunless
                @else
                    <a href="{{ route('campeonatos.editar', $campeonato['id']) }}" aria-label="Editar {{ $campeonato['nome'] }}" title="Editar"
                       data-champ-ir="{{ route('campeonatos.editar', $campeonato['id']) }}" data-champ-estado="editar={{ $campeonato['id'] }}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                @endif
                @include('pages.campeonatos.partials.apagar-botao')
            </span>
        </div>
    </div>
</article>
