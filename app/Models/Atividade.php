<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de atividades: o que foi feito no sistema e quando. Só é criado
 * (nunca editado), por Atividade::registrar() nos pontos que mudam dados.
 * Ainda não guarda "quem" — o sistema não tem login.
 */
class Atividade extends Model
{
    public const UPDATED_AT = null;

    public const AREAS = [
        'vendas' => ['rotulo' => 'Vendas', 'icone' => 'bi-receipt'],
        'alugueis' => ['rotulo' => 'Mesas e aluguéis', 'icone' => 'bi-calendar-week'],
        'clientes' => ['rotulo' => 'Clientes', 'icone' => 'bi-people'],
        'estoque' => ['rotulo' => 'Estoque', 'icone' => 'bi-box-seam'],
        'configuracoes' => ['rotulo' => 'Configurações', 'icone' => 'bi-gear'],
    ];

    protected $table = 'atividades';

    protected $fillable = ['area', 'descricao'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function registrar(string $area, string $descricao): void
    {
        self::create(['area' => $area, 'descricao' => mb_substr($descricao, 0, 255)]);
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
            'data' => $this->created_at->format('d/m/Y'),
            'hora' => $this->created_at->format('H:i'),
        ];
    }
}
