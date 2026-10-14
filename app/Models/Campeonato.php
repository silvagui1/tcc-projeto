<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campeonato extends Model
{
    use HasFactory;

    /**
     * Banner usado quando o campeonato não tem imagem cadastrada, por jogo.
     */
    private const BANNERS_PADRAO = [
        'pokemon' => 'images/campeonatos/banner-pokemon.png',
        'magic' => 'images/campeonatos/banner-magic.png',
        'onepiece' => 'images/campeonatos/banner-onepiece.svg',
    ];

    protected $fillable = [
        'nome',
        'jogo',
        'deck',
        'data',
        'horario',
        'valor_inscricao',
        'descricao',
        'imagem',
        'status',
    ];

    protected $casts = [
        'data' => 'date',
        'valor_inscricao' => 'decimal:2',
    ];

    public function participantes()
    {
        return $this->belongsToMany(Cliente::class, 'campeonato_cliente')
            ->withPivot('valor_pago', 'colocacao')
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

    public function finalizado(): bool
    {
        return $this->status === 'finalizado';
    }

    /**
     * Imagem do card. Sem url cadastrada usa o banner padrão do jogo;
     * caminhos relativos (ex.: os do seeder) viram url de public/.
     */
    public function getBannerAttribute(): string
    {
        $imagem = $this->imagem ?: (self::BANNERS_PADRAO[$this->jogo] ?? 'images/campeonatos/banner-torneio-pokemon.png');

        return str_starts_with($imagem, 'http') ? $imagem : asset($imagem);
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
     * Formato usado pelas telas de resources/views/pages/campeonatos (lista,
     * acessar, editar). Espera participantes e premios.cliente carregados.
     *
     * @return array<string, mixed>
     */
    public function dadosTela(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'jogo' => $this->jogo,
            'status' => $this->status,
            'data' => $this->data_extenso,
            'dataCurta' => $this->data->format('d/m/Y'),
            'horario' => $this->horario_curto,
            'deck' => $this->deck,
            'inscricao' => (float) $this->valor_inscricao,
            'descricao' => (string) $this->descricao,
            'imagem' => $this->banner,
            'imagemUrl' => $this->imagem,
            'participantesLista' => $this->participantes
                ->map(fn (Cliente $cliente) => $cliente->dadosParticipante())
                ->all(),
            'vencedores' => $this->premios
                ->map(fn (Premio $premio) => [
                    'posicao' => "{$premio->colocacao}° Lugar",
                    'nome' => $premio->cliente->nome,
                    'credito' => (float) $premio->valor,
                    'avatar' => $premio->cliente->avatar_url,
                ])
                ->all(),
        ];
    }
}
