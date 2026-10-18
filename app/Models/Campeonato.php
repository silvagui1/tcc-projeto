<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Campeonato extends Model
{
    use HasFactory;
    use TemImagemDeEstoque;

    /**
     * Tipos de campeonato, do mais casual ao mais burocrático. Segue a escada
     * do Play! Pokémon (liga da loja → League Challenge → League Cup) e o
     * equivalente do Magic (Friday Night Magic → Store Championship → RCQ).
     * Os oficiais precisam de sanção, ID de jogador e envio de resultados.
     */
    public const TIPOS = [
        'liga' => [
            'rotulo' => 'Liga semanal',
            'icone' => 'bi-cup-hot',
            'oficial' => false,
            'resumo' => 'Casual, sem sanção — a liguinha da loja.',
            'exemplos' => 'Liga Pokémon, Friday Night Magic',
            'estrutura' => 'Formato combinado na loja',
        ],
        'challenge' => [
            'rotulo' => 'Challenge',
            'icone' => 'bi-flag',
            'oficial' => true,
            'resumo' => 'Oficial de entrada: precisa de sanção e Player ID.',
            'exemplos' => 'League Challenge, Store Championship',
            'estrutura' => 'Suíço, melhor de 1, sem top cut',
        ],
        'cup' => [
            'rotulo' => 'Cup',
            'icone' => 'bi-trophy',
            'oficial' => true,
            'resumo' => 'Oficial maior: sanção antecipada, listas de deck e top cut.',
            'exemplos' => 'League Cup, RCQ',
            'estrutura' => 'Suíço melhor de 1 + top cut melhor de 3, por divisão',
        ],
        'prerelease' => [
            'rotulo' => 'Pré-lançamento',
            'icone' => 'bi-box-seam',
            'oficial' => true,
            'resumo' => 'Selado, com os kits da coleção nova.',
            'exemplos' => 'Pré-release Pokémon e Magic',
            'estrutura' => 'Selado: cada jogador monta o deck com o kit',
        ],
    ];

    /**
     * Itens da burocracia dos eventos oficiais (checklist no detalhe).
     */
    public const REQUISITOS = [
        'sancao' => 'Evento sancionado (pedido aprovado pela organização oficial)',
        'player_id' => 'Todos os participantes com ID de jogador (Player ID)',
        'listas' => 'Listas de deck recebidas e conferidas',
        'divisoes' => 'Jogadores separados por divisão (Júnior, Sênior e Máster)',
        'kits' => 'Kits de pré-lançamento separados para cada inscrito',
        'software' => 'Torneio montado no software oficial (TOM / EventLink)',
        'resultados' => 'Resultados enviados depois do evento',
    ];

    /**
     * Quais itens do checklist valem para cada tipo, na ordem em que
     * acontecem. "resultados" é feito depois do evento.
     */
    private const REQUISITOS_POR_TIPO = [
        'liga' => [],
        'challenge' => ['sancao', 'player_id', 'listas', 'software', 'resultados'],
        'cup' => ['sancao', 'player_id', 'listas', 'divisoes', 'software', 'resultados'],
        'prerelease' => ['sancao', 'player_id', 'kits', 'software', 'resultados'],
    ];

    /**
     * Cores do banner do sistema por jogo [fundo, fundo claro, destaque].
     * Jogos cadastrados depois em Configurações usam a paleta de reserva.
     */
    private const CORES_BANNER = [
        'pokemon' => ['#2e4762', '#395573', '#f5c874'],
        'magic' => ['#2e3045', '#53577d', '#d7cffa'],
        'onepiece' => ['#723756', '#a14e79', '#ffdc9b'],
    ];

    private const CORES_BANNER_RESERVA = [
        ['#442133', '#a14e79', '#d4aec1'],
        ['#2e4762', '#53577d', '#bcd7f5'],
        ['#5e4217', '#a8772a', '#ffe9c2'],
    ];

    protected $fillable = [
        'nome',
        'jogo',
        'tipo',
        'codigo_sancao',
        'requisitos',
        'deck',
        'data',
        'horario',
        'semanal',
        'serie',
        'valor_inscricao',
        'premio_1',
        'premio_2',
        'premio_3',
        'premio_extra',
        'descricao',
        'imagem',
        'status',
    ];

    protected $casts = [
        'data' => 'date',
        'valor_inscricao' => 'decimal:2',
        'premio_1' => 'decimal:2',
        'premio_2' => 'decimal:2',
        'premio_3' => 'decimal:2',
        'semanal' => 'boolean',
        'requisitos' => 'array',
    ];

    public function participantes()
    {
        return $this->belongsToMany(Cliente::class, 'campeonato_cliente')
            ->withPivot('valor_pago', 'venda_id', 'colocacao')
            ->withTimestamps();
    }

    /**
     * Prêmios de cada colocação (preenchidos ao finalizar o campeonato).
     */
    public function premios()
    {
        return $this->hasMany(Premio::class)->orderBy('colocacao');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', 'ativo');
    }

    public function scopeFinalizados(Builder $query): Builder
    {
        return $query->where('status', 'finalizado');
    }

    public function finalizado(): bool
    {
        return $this->status === 'finalizado';
    }

    /**
     * @return array<string, string> chave => rótulo, para selects e chips
     */
    public static function opcoesTipo(): array
    {
        return array_map(fn (array $tipo) => $tipo['rotulo'], self::TIPOS);
    }

    public function oficial(): bool
    {
        return self::TIPOS[$this->tipo]['oficial'] ?? false;
    }

    /**
     * Checklist do tipo, com o que já foi cumprido.
     *
     * @return array<int, array{chave: string, rotulo: string, feito: bool}>
     */
    public function checklist(): array
    {
        $feitos = $this->requisitos ?? [];

        return array_map(fn (string $chave) => [
            'chave' => $chave,
            'rotulo' => self::REQUISITOS[$chave],
            'feito' => in_array($chave, $feitos, true),
        ], self::REQUISITOS_POR_TIPO[$this->tipo] ?? []);
    }

    /**
     * @return array<int, string>
     */
    public static function requisitosDoTipo(string $tipo): array
    {
        return self::REQUISITOS_POR_TIPO[$tipo] ?? [];
    }

    /**
     * Itens que ainda faltam antes de finalizar (o envio dos resultados é
     * feito depois, então não conta).
     *
     * @return array<int, string> rótulos
     */
    public function pendenciasAntesDeFinalizar(): array
    {
        return collect($this->checklist())
            ->reject(fn (array $item) => $item['feito'] || $item['chave'] === 'resultados')
            ->pluck('rotulo')
            ->all();
    }

    /**
     * Banner padrão gerado pelo sistema — usado quando não há imagem enviada
     * nem link. É servido pela rota campeonatos.banner (SVG).
     */
    public function getImagemPadraoAttribute(): string
    {
        return self::urlBanner((string) $this->jogo, (string) $this->tipo);
    }

    /**
     * Versão do desenho do banner: mudou o SVG, sobe o número — o navegador
     * guarda o banner por um dia e só busca de novo se a url mudar.
     */
    public const VERSAO_BANNER = 2;

    public static function urlBanner(string $jogo, string $tipo): string
    {
        return route('campeonatos.banner', ['jogo' => $jogo, 'tipo' => $tipo, 'v' => self::VERSAO_BANNER]);
    }

    /**
     * SVG do banner do sistema, nas cores do jogo. O texto (jogo + tipo) fica
     * à esquerda e o desenho à direita muda com o tipo: cartas na liga,
     * bandeira no Challenge, troféu na Cup e caixa no pré-lançamento. As
     * telas enquadram este banner pela esquerda (.champ-banner--sistema),
     * então num espaço estreito quem é cortado é o desenho, nunca o texto.
     */
    public static function svgBanner(string $jogo, string $tipoChave): string
    {
        [$fundo, $fundoClaro, $destaque] = self::CORES_BANNER[$jogo]
            ?? self::CORES_BANNER_RESERVA[crc32($jogo) % count(self::CORES_BANNER_RESERVA)];

        $nomeJogo = e(mb_strtoupper(JogoCarta::nomeDe($jogo) ?: 'Campeonato'));
        $tipo = e(self::TIPOS[$tipoChave]['rotulo'] ?? 'Campeonato');

        $cartas = function (array $angulos, float $opacidade) use ($fundoClaro, $destaque): string {
            $svg = '';
            foreach ($angulos as $i => $angulo) {
                $svg .= '<rect x="-62" y="-90" width="124" height="180" rx="14" transform="rotate('.$angulo.')" '
                    .'fill="'.$fundoClaro.'" fill-opacity="'.round($opacidade + $i * 0.12, 2).'" stroke="'.$destaque.'" stroke-opacity=".4" stroke-width="3"/>';
            }

            return $svg;
        };

        $desenho = match ($tipoChave) {
            // leque de cartas com um losango na da frente
            'liga' => $cartas([-24, -8, 8, 24], 0.45)
                .'<path d="M0-38 28 0 0 38-28 0z" transform="rotate(24)" fill="'.$destaque.'"/>',
            // bandeira fincada sobre as cartas
            'challenge' => $cartas([-14, 10], 0.4)
                .'<g fill="'.$destaque.'"><rect x="-8" y="-118" width="12" height="236" rx="6"/>'
                .'<path d="M4-112h112l-30 42 30 42H4z"/></g>',
            // troféu
            'cup' => $cartas([-18, 0, 18], 0.45)
                .'<g fill="'.$destaque.'"><path d="M-52-70h104v56a52 52 0 0 1-104 0z"/>'
                .'<path d="M-52-58h-30a30 30 0 0 0 34 50M52-58h30a30 30 0 0 1-34 50" fill="none" stroke="'.$destaque.'" stroke-width="12"/>'
                .'<rect x="-11" y="36" width="22" height="34"/><rect x="-44" y="70" width="88" height="20" rx="5"/></g>',
            // caixa (kit) com brilho
            default => '<g stroke="'.$destaque.'" stroke-width="4" stroke-linejoin="round">'
                .'<path d="M-90-40 0-84 90-40 0 4z" fill="'.$destaque.'" fill-opacity=".9"/>'
                .'<path d="M-90-40v100l90 44V4z" fill="'.$fundoClaro.'"/>'
                .'<path d="M90-40v100L0 104V4z" fill="'.$fundoClaro.'" fill-opacity=".7"/></g>'
                .'<path d="M70-110l8 18 18 8-18 8-8 18-8-18-18-8 18-8z" fill="'.$destaque.'"/>',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 480">'
            .'<defs><linearGradient id="f" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="'.$fundo.'"/><stop offset="1" stop-color="'.$fundoClaro.'"/></linearGradient>'
            .'<radialGradient id="b" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="'.$destaque.'" stop-opacity=".32"/>'
            .'<stop offset="1" stop-color="'.$destaque.'" stop-opacity="0"/></radialGradient></defs>'
            .'<rect width="1200" height="480" fill="url(#f)"/>'
            .'<g fill="none" stroke="'.$destaque.'" stroke-opacity=".12" stroke-width="2">'
            .'<circle cx="80" cy="440" r="170"/><circle cx="80" cy="440" r="240"/><circle cx="80" cy="440" r="310"/></g>'
            .'<circle cx="930" cy="240" r="250" fill="url(#b)"/>'
            .'<g transform="translate(930 240)">'.$desenho.'</g>'
            .'<g font-family="Inter, Segoe UI, Arial, sans-serif">'
            .'<text x="64" y="206" fill="'.$destaque.'" font-size="30" font-weight="700" letter-spacing="6">'.$nomeJogo.'</text>'
            .'<text x="64" y="282" fill="#ffffff" font-size="68" font-weight="800">'.$tipo.'</text>'
            .'<rect x="64" y="310" width="96" height="8" rx="4" fill="'.$destaque.'"/></g>'
            .'</svg>';
    }

    /**
     * Imagem do card: arquivo enviado, link externo ou o banner do sistema.
     * Caminhos "images/..." são de campeonatos antigos (pasta public/).
     */
    public function getImagemUrlAttribute(): string
    {
        if (! $this->imagem) {
            return $this->imagem_padrao;
        }
        if (self::ehLinkExterno($this->imagem)) {
            return $this->imagem;
        }

        return str_starts_with($this->imagem, 'images/') ? asset($this->imagem) : asset('storage/'.$this->imagem);
    }

    public function getBannerAttribute(): string
    {
        return $this->imagem_url;
    }

    /**
     * As ocorrências de um campeonato semanal dividem o mesmo arquivo de
     * banner: só apaga quando ninguém mais usa.
     */
    public function apagarArquivoDeImagem(): void
    {
        if (! $this->temArquivoDeImagem() || str_starts_with($this->imagem, 'images/')) {
            return;
        }

        if (! self::where('imagem', $this->imagem)->whereKeyNot($this->id)->exists()) {
            Storage::disk('public')->delete($this->imagem);
        }
    }

    /**
     * "22 de agosto de 2026".
     */
    public function getDataExtensoAttribute(): string
    {
        return $this->data->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y');
    }

    /**
     * "10:30" (o banco devolve "10:30:00").
     */
    public function getHorarioCurtoAttribute(): ?string
    {
        return $this->horario ? substr($this->horario, 0, 5) : null;
    }

    /**
     * Data relativa a hoje: "Hoje", "Amanhã", "Em 5 dias", "Ontem",
     * "Há 3 dias"; mais longe que duas semanas, a data curta ("27 out").
     */
    public function getQuandoAttribute(): string
    {
        $dias = (int) today()->diffInDays($this->data, false);

        return match (true) {
            $dias === 0 => 'Hoje',
            $dias === 1 => 'Amanhã',
            $dias === -1 => 'Ontem',
            $dias > 1 && $dias < 14 => "Em {$dias} dias",
            $dias < -1 && $dias > -14 => 'Há '.abs($dias).' dias',
            default => $this->data->locale('pt_BR')->translatedFormat('j M'),
        };
    }

    /**
     * "Toda quinta-feira · 18:00" (ou "Todo sábado").
     */
    public function getRecorrenciaAttribute(): ?string
    {
        if (! $this->semanal) {
            return null;
        }

        $dia = $this->data->locale('pt_BR')->dayName;
        $prefixo = in_array($this->data->dayOfWeek, [0, 6], true) ? 'Todo' : 'Toda';

        return "{$prefixo} {$dia}".($this->horario_curto ? ' · '.$this->horario_curto : '');
    }

    /**
     * Pré-cadastra a ocorrência da semana seguinte de um campeonato semanal
     * (mesmos dados, sem participantes nem checklist). Se a data já passou,
     * pula para a próxima semana a partir de hoje. Não cria se a série já
     * tem outra ocorrência em andamento.
     */
    public function criarProximaSemana(): ?self
    {
        if (! $this->semanal || ! $this->serie) {
            return null;
        }

        $jaTem = self::ativos()->where('serie', $this->serie)->whereKeyNot($this->id)->exists();
        if ($jaTem) {
            return null;
        }

        $data = $this->data->copy()->addWeek();
        while ($data->lt(today())) {
            $data->addWeek();
        }

        return self::create($this->only([
            'nome', 'jogo', 'tipo', 'deck', 'horario', 'semanal', 'serie', 'valor_inscricao',
            'premio_1', 'premio_2', 'premio_3', 'premio_extra', 'descricao', 'imagem',
        ]) + ['data' => $data, 'status' => 'ativo', 'requisitos' => []]);
    }

    public static function novaSerie(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Premiação planejada (créditos por colocação), sem as zeradas.
     *
     * @return array<int, float> colocação => créditos
     */
    public function premiacaoPlanejada(): array
    {
        return array_filter([
            1 => (float) $this->premio_1,
            2 => (float) $this->premio_2,
            3 => (float) $this->premio_3,
        ], fn (float $valor) => $valor > 0);
    }

    /**
     * Formato usado pelas telas de resources/views/pages/campeonatos (lista
     * e modais). Espera participantes e premios.cliente carregados.
     *
     * @return array<string, mixed>
     */
    public function dadosTela(): array
    {
        $tipo = self::TIPOS[$this->tipo] ?? self::TIPOS['liga'];
        $pagos = $this->participantes->filter(fn (Cliente $cliente) => $cliente->pivot->venda_id !== null);
        $premios = $this->premiacaoPlanejada();

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'jogo' => $this->jogo,
            'tipo' => $this->tipo,
            'tipoRotulo' => $tipo['rotulo'],
            'tipoIcone' => $tipo['icone'],
            'tipoEstrutura' => $tipo['estrutura'],
            'oficial' => $tipo['oficial'],
            'codigoSancao' => $this->codigo_sancao,
            'checklist' => $this->checklist(),
            'status' => $this->status,
            'data' => $this->data_extenso,
            'dataCurta' => $this->data->format('d/m/Y'),
            'dataIso' => $this->data->format('Y-m-d'),
            'atrasado' => ! $this->finalizado() && $this->data->lt(today()),
            'hoje' => $this->data->isToday(),
            'quando' => $this->quando,
            'dataMes' => mb_strtoupper($this->data->locale('pt_BR')->translatedFormat('M')),
            'dataDia' => $this->data->format('d'),
            'horario' => $this->horario_curto,
            'semanal' => $this->semanal,
            'recorrencia' => $this->recorrencia,
            'deck' => $this->deck,
            'inscricao' => (float) $this->valor_inscricao,
            'premios' => $premios,
            'totalPremios' => array_sum($premios),
            'premioExtra' => (string) $this->premio_extra,
            // caixa: quanto já entrou de inscrição e quanto ainda falta receber
            'pagos' => $pagos->count(),
            'arrecadado' => (float) $pagos->sum(fn (Cliente $cliente) => $cliente->pivot->valor_pago),
            'aReceber' => ($this->participantes->count() - $pagos->count()) * (float) $this->valor_inscricao,
            'descricao' => (string) $this->descricao,
            'imagem' => $this->imagem_url,
            'imagemPadrao' => $this->imagem_padrao,
            'bannerSistema' => ! $this->imagem,
            // campo de imagem do pop-up de editar: link externo e prévia da imagem própria
            'imagemLink' => self::ehLinkExterno($this->imagem) ? $this->imagem : '',
            'imagemPreview' => $this->imagem ? $this->imagem_url : '',
            'participantesLista' => $this->participantes
                ->map(fn (Cliente $cliente) => $cliente->dadosParticipante() + [
                    // inscrição paga = lançada como venda (ver CampeonatoController::registrarPagamento)
                    'pago' => $cliente->pivot->venda_id !== null,
                    'saldo' => (float) $cliente->creditos,
                ])
                ->all(),
            'vencedores' => $this->premios
                ->map(fn (Premio $premio) => [
                    'posicao' => "{$premio->colocacao}° Lugar",
                    'nome' => $premio->cliente->nome,
                    'credito' => (float) $premio->valor,
                    'extra' => (string) $premio->descricao,
                    'avatar' => $premio->cliente->avatar_url,
                ])
                ->all(),
        ];
    }
}
