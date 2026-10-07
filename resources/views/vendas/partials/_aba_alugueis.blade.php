@php
    $diasCurtos = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
    $semanaAnterior = $dia->copy()->subWeek()->format('Y-m-d');
    $proximaSemana = $dia->copy()->addWeek()->format('Y-m-d');
    $tituloDia = \Illuminate\Support\Str::ucfirst($dia->locale('pt_BR')->translatedFormat('l, d \d\e F'));
    $ativosDoDia = $alugueis->where('status', '!=', 'cancelado');

    // Quadro de ocupação: posição (%) de cada reserva dentro da faixa de horas.
    [$horaInicial, $horaFinal] = $faixaHoras;
    $minutosFaixa = ($horaFinal - $horaInicial) * 60;
    $posicao = function ($momento) use ($dia, $horaInicial, $minutosFaixa) {
        $minutos = $dia->diffInMinutes($momento, false) - $horaInicial * 60;

        return max(0, min(100, $minutos / $minutosFaixa * 100));
    };
    $agoraPosicao = $dia->isToday() ? $posicao(now()) : null;

    $statusRotulo = ['agendado' => 'Agendado', 'pago' => 'Pago', 'cancelado' => 'Cancelado'];
@endphp

<div class="agenda">
    {{-- Semana: escolher o dia. Cada dia mostra quantas reservas tem. --}}
    <div class="agenda-semana">
        <a href="{{ route('vendas.index', ['aba' => 'alugueis', 'dia' => $semanaAnterior]) }}" class="agenda-semana__seta" aria-label="Semana anterior">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div class="agenda-semana__dias">
            @foreach ($semana as $item)
                @php $data = $item['data']; @endphp
                <a
                    href="{{ route('vendas.index', ['aba' => 'alugueis', 'dia' => $data->format('Y-m-d')]) }}"
                    class="agenda-dia {{ $data->isSameDay($dia) ? 'agenda-dia--atual' : '' }} {{ $data->isToday() ? 'agenda-dia--hoje' : '' }}"
                    @if ($data->isSameDay($dia)) aria-current="date" @endif
                    aria-label="{{ $data->format('d/m') }}{{ $item['reservas'] ? ', '.$item['reservas'].' reservas' : '' }}"
                >
                    <span class="agenda-dia__semana">{{ $data->isToday() ? 'hoje' : $diasCurtos[$data->dayOfWeekIso - 1] }}</span>
                    <span class="agenda-dia__numero">{{ $data->format('d') }}</span>
                    <span class="agenda-dia__reservas">{{ $item['reservas'] ?: '' }}</span>
                </a>
            @endforeach
        </div>
        <a href="{{ route('vendas.index', ['aba' => 'alugueis', 'dia' => $proximaSemana]) }}" class="agenda-semana__seta" aria-label="Próxima semana">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="agenda-titulo">
        <div>
            <h2 class="agenda-titulo__dia">{{ $tituloDia }}</h2>
            <span class="agenda-titulo__info">
                @if ($ativosDoDia->isEmpty())
                    nenhuma reserva
                @else
                    {{ $ativosDoDia->count() }} {{ $ativosDoDia->count() === 1 ? 'reserva' : 'reservas' }}
                    · R$ {{ number_format($ativosDoDia->sum('valor'), 2, ',', '.') }}
                @endif
                {{-- horário de funcionamento do dia (Configurações > Loja) --}}
                @if ($funcionamento)
                    · loja aberta das {{ sprintf('%02d:%02d', intdiv($funcionamento[0], 60), $funcionamento[0] % 60) }}
                    às {{ sprintf('%02d:%02d', intdiv($funcionamento[1], 60) % 24, $funcionamento[1] % 60) }}
                @else
                    · <span class="agenda-titulo__fechada">loja fechada neste dia</span>
                @endif
            </span>
        </div>
        @unless ($dia->isToday())
            <a href="{{ route('vendas.index', ['aba' => 'alugueis']) }}" class="botao-texto agenda-titulo__hoje">
                <i class="bi bi-calendar-check" aria-hidden="true"></i> Ir para hoje
            </a>
        @endunless
    </div>

    @if ($mesas->isEmpty())
        <div class="clientes-vazio">
            <i class="bi bi-grid-3x2-gap clientes-vazio__icone" aria-hidden="true"></i>
            <p class="clientes-vazio__titulo">Nenhuma mesa cadastrada</p>
            <p class="clientes-vazio__texto">Cadastre as mesas da loja (nome, lugares e preço por hora) para começar a controlar os aluguéis.</p>
            <button type="button" class="botao botao--principal" data-abrir-mesas>
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Cadastrar mesas
            </button>
        </div>
    @else
        {{-- Quadro de ocupação: uma linha por mesa, com as reservas do dia
             desenhadas na faixa de horários. Clicar num espaço livre abre
             "Novo aluguel" já com a mesa e o horário (ver vendas.js). --}}
        <section class="ocupacao" aria-label="Ocupação das mesas" data-ocupacao data-hora-inicial="{{ $horaInicial }}" data-hora-final="{{ $horaFinal }}">
            <div class="ocupacao__rolagem">
                <div class="ocupacao__grade">
                    <div class="ocupacao__escala" aria-hidden="true">
                        <span class="ocupacao__mesa"></span>
                        <div class="ocupacao__horas">
                            @for ($h = $horaInicial; $h <= $horaFinal; $h += 2)
                                <span style="left: {{ ($h - $horaInicial) * 60 / $minutosFaixa * 100 }}%">{{ sprintf('%02d', $h % 24) }}h</span>
                            @endfor
                        </div>
                    </div>

                    @foreach ($mesasDoDia as $mesa)
                        <div class="ocupacao__linha">
                            <span class="ocupacao__mesa">
                                {{ $mesa->nome }}
                                <small>{{ $mesa->capacidade }} lugares{{ $mesa->ativa ? '' : ' · inativa' }}</small>
                            </span>
                            <div
                                class="ocupacao__trilho {{ $mesa->ativa ? '' : 'ocupacao__trilho--inativa' }}"
                                @if ($mesa->ativa) data-trilho data-mesa="{{ $mesa->id }}" title="Clique num horário livre para reservar a {{ $mesa->nome }}" @endif
                            >
                                @for ($h = $horaInicial + 1; $h < $horaFinal; $h++)
                                    <span class="ocupacao__marca" style="left: {{ ($h - $horaInicial) * 60 / $minutosFaixa * 100 }}%" aria-hidden="true"></span>
                                @endfor

                                @foreach ($ativosDoDia->where('mesa_id', $mesa->id) as $aluguel)
                                    @php
                                        $esquerda = $posicao($aluguel->inicio);
                                        $largura = max(1.5, $posicao($aluguel->fim) - $esquerda);
                                    @endphp
                                    <button
                                        type="button"
                                        class="ocupacao__bloco ocupacao__bloco--{{ $aluguel->tipo_jogo_cor }} {{ $aluguel->status === 'pago' ? 'ocupacao__bloco--pago' : '' }}"
                                        style="left: {{ $esquerda }}%; width: {{ $largura }}%"
                                        data-abrir-aluguel="{{ $aluguel->id }}"
                                        title="{{ $aluguel->horario }} · {{ $aluguel->nome_exibicao }} · {{ $aluguel->tipo_jogo_rotulo }}"
                                    >
                                        <span>{{ $aluguel->nome_exibicao }}</span>
                                    </button>
                                @endforeach

                                @if ($agoraPosicao !== null && $agoraPosicao > 0 && $agoraPosicao < 100)
                                    <span class="ocupacao__agora" style="left: {{ $agoraPosicao }}%" aria-hidden="true"></span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="ocupacao__legenda">
                @foreach ($tiposJogo as $tipo)
                    <span><i class="ocupacao__cor ocupacao__bloco--{{ $tipo['cor'] }}" aria-hidden="true"></i>{{ $tipo['nome'] }}</span>
                @endforeach
                <span><i class="ocupacao__cor ocupacao__cor--pago" aria-hidden="true"></i>Pago</span>
            </p>
        </section>

        <ul class="alugueis-lista">
            @forelse ($alugueis as $aluguel)
                <li>
                    <button type="button" class="aluguel-card aluguel-card--{{ $aluguel->status }}" data-abrir-aluguel="{{ $aluguel->id }}">
                        <span class="aluguel-card__horario">
                            <strong>{{ $aluguel->inicio->format('H:i') }}</strong>
                            <small>até {{ $aluguel->fim->format('H:i') }}</small>
                        </span>

                        <span class="aluguel-card__corpo">
                            <span class="aluguel-card__titulo">{{ $aluguel->nome_exibicao }}</span>
                            <span class="aluguel-card__meta">
                                <span class="jogo-chip jogo-chip--{{ $aluguel->tipo_jogo_cor }}">
                                    <i class="bi {{ $aluguel->tipo_jogo_icone }}" aria-hidden="true"></i>
                                    {{ $aluguel->tipo_jogo_rotulo }}
                                </span>
                                <span>{{ $aluguel->mesa->nome }} · {{ $aluguel->duracao_texto }}@if ($aluguel->jogo) · {{ $aluguel->jogo }}@endif</span>
                            </span>
                            @if ($aluguel->serie)
                                <span class="aluguel-card__serie">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                    toda {{ $aluguel->dia_semana }}
                                </span>
                            @endif
                        </span>

                        <span class="aluguel-card__lado">
                            <span class="aluguel-card__valor">R$ {{ number_format($aluguel->valor, 2, ',', '.') }}</span>
                            <span class="aluguel-status aluguel-status--{{ $aluguel->status }}">{{ $statusRotulo[$aluguel->status] ?? $aluguel->status }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="clientes-vazio">
                    <i class="bi bi-calendar2 clientes-vazio__icone" aria-hidden="true"></i>
                    <p class="clientes-vazio__titulo">Nenhuma reserva neste dia</p>
                    <p class="clientes-vazio__texto">Reserve uma mesa por aqui ou clicando num horário livre no quadro acima.</p>
                    <button type="button" class="botao botao--principal" data-abrir-novo-aluguel>
                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                        Novo aluguel
                    </button>
                </li>
            @endforelse
        </ul>
    @endif
</div>
