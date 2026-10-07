<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Reserva de uma mesa num horário. Reservas que se repetem toda semana são
 * várias linhas com o mesmo `serie` (ver AluguelController::store).
 */
class Aluguel extends Model
{
    protected $table = 'alugueis';

    /**
     * Tipos de jogo aceitos e o rótulo mostrado na tela.
     */
    public const TIPOS_JOGO = [
        'rpg' => 'RPG',
        'cartas' => 'Cartas',
        'tabuleiro' => 'Tabuleiro',
        'outro' => 'Outro',
    ];

    public const STATUS = ['agendado', 'pago', 'cancelado'];

    private const DIAS_SEMANA = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];

    protected $fillable = [
        'mesa_id',
        'cliente_id',
        'responsavel',
        'inicio',
        'fim',
        'valor',
        'tipo_jogo',
        'jogo',
        'serie',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'valor' => 'decimal:2',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Item de venda que pagou este aluguel (só existe quando status = pago).
     */
    public function itemVenda()
    {
        return $this->hasOne(VendaItem::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelado');
    }

    /**
     * Reservas da mesma mesa que se sobrepõem ao intervalo informado — base
     * da regra "não pode haver duas reservas na mesma mesa ao mesmo tempo".
     * Encostar não conta como conflito (uma termina 20:00, outra começa 20:00).
     */
    public function scopeConflitantes(Builder $query, int $mesaId, $inicio, $fim): Builder
    {
        return $query->ativos()
            ->where('mesa_id', $mesaId)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio);
    }

    /**
     * Quem alugou: o nome do cliente cadastrado ou o responsável digitado.
     */
    public function getNomeExibicaoAttribute(): string
    {
        return $this->cliente?->nome ?? ($this->responsavel ?: 'Sem responsável');
    }

    public function getDuracaoMinutosAttribute(): int
    {
        return (int) $this->inicio->diffInMinutes($this->fim);
    }

    /**
     * "3h", "1h30", "45min".
     */
    public function getDuracaoTextoAttribute(): string
    {
        $horas = intdiv($this->duracao_minutos, 60);
        $minutos = $this->duracao_minutos % 60;

        if ($horas === 0) {
            return "{$minutos}min";
        }

        return $minutos ? sprintf('%dh%02d', $horas, $minutos) : "{$horas}h";
    }

    public function getHorarioAttribute(): string
    {
        return $this->inicio->format('H:i').'–'.$this->fim->format('H:i');
    }

    public function getDiaSemanaAttribute(): string
    {
        return self::DIAS_SEMANA[$this->inicio->dayOfWeek];
    }

    public function getTipoJogoRotuloAttribute(): string
    {
        return self::TIPOS_JOGO[$this->tipo_jogo] ?? 'Outro';
    }

    /**
     * Texto usado como descrição do item quando o aluguel é cobrado numa
     * venda — ex.: "Aluguel Mesa 2 · 07/10 18:00–21:00".
     */
    public function getDescricaoVendaAttribute(): string
    {
        return "Aluguel {$this->mesa->nome} · {$this->inicio->format('d/m')} {$this->horario}";
    }

    /**
     * Formato usado pelo JS (detalhes, edição e pagamento do aluguel).
     *
     * @return array<string, mixed>
     */
    public function dadosJson(): array
    {
        $serie = null;

        if ($this->serie) {
            $datas = self::where('serie', $this->serie)->orderBy('inicio')->get(['id', 'inicio', 'status']);
            $serie = [
                'total' => $datas->count(),
                'posicao' => $datas->search(fn ($a) => $a->id === $this->id) + 1,
                'proximas' => $datas->filter(fn ($a) => $a->inicio >= $this->inicio && $a->status === 'agendado')->count(),
                'ultima' => $datas->last()->inicio->format('d/m/Y'),
            ];
        }

        return [
            'id' => $this->id,
            'mesa_id' => $this->mesa_id,
            'mesa' => $this->mesa->nome,
            'cliente' => $this->cliente?->dadosResumidos(),
            'responsavel' => $this->responsavel,
            'nome_exibicao' => $this->nome_exibicao,
            'data' => $this->inicio->format('Y-m-d'),
            'data_texto' => $this->dia_semana.', '.$this->inicio->format('d/m/Y'),
            'hora_inicio' => $this->inicio->format('H:i'),
            'hora_fim' => $this->fim->format('H:i'),
            'horario' => $this->horario,
            'duracao_minutos' => $this->duracao_minutos,
            'duracao_texto' => $this->duracao_texto,
            'dia_semana' => $this->dia_semana,
            'valor' => (float) $this->valor,
            'tipo_jogo' => $this->tipo_jogo,
            'tipo_jogo_rotulo' => $this->tipo_jogo_rotulo,
            'jogo' => $this->jogo,
            'status' => $this->status,
            'observacoes' => $this->observacoes,
            'serie' => $serie,
            'venda_id' => $this->itemVenda?->venda_id,
            'descricao_venda' => $this->descricao_venda,
        ];
    }
}
