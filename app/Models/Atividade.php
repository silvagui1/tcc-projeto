<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de atividades: o que foi feito no sistema, quando e por quem. Só
 * é criado (nunca editado), por Atividade::registrar() nos pontos que mudam
 * dados — "quem" é sempre quem está conectado naquele momento.
 */
class Atividade extends Model
{
    public const UPDATED_AT = null;

    public const AREAS = [
        'vendas' => ['rotulo' => 'Vendas', 'icone' => 'bi-receipt'],
        'alugueis' => ['rotulo' => 'Mesas e aluguéis', 'icone' => 'bi-calendar-week'],
        'clientes' => ['rotulo' => 'Clientes', 'icone' => 'bi-people'],
        'estoque' => ['rotulo' => 'Estoque', 'icone' => 'bi-box-seam'],
        'campeonatos' => ['rotulo' => 'Campeonatos', 'icone' => 'bi-trophy'],
        'configuracoes' => ['rotulo' => 'Configurações', 'icone' => 'bi-gear'],
        'usuarios' => ['rotulo' => 'Usuários', 'icone' => 'bi-person-gear'],
        'acesso' => ['rotulo' => 'Acessos', 'icone' => 'bi-shield-lock'],
    ];

    protected $table = 'atividades';

    protected $fillable = ['area', 'descricao', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function registrar(string $area, string $descricao): void
    {
        self::create([
            'area' => $area,
            'descricao' => mb_substr($descricao, 0, 255),
            'user_id' => auth()->id(),
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function dadosJson(): array
    {
        return [
            'id' => $this->id,
            'area' => $this->area,
            'area_rotulo' => self::AREAS[$this->area]['rotulo'] ?? $this->area,
            'icone' => self::AREAS[$this->area]['icone'] ?? 'bi-dot',
            'descricao' => $this->descricao,
            'usuario' => $this->usuario?->nome,
            'data' => $this->created_at->format('d/m/Y'),
            'hora' => $this->created_at->format('H:i'),
        ];
    }
}
