<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campeonato extends Model
{
    use HasFactory;

    protected $table = 'campeonatos';

    protected $fillable = ['nome', 'deck', 'data', 'horario', 'valor_inscricao', 'descricao', 'imagem', 'status'];

    protected $casts = [
        'data' => 'date',
        'valor_inscricao' => 'decimal:2',
    ];

    // Usuários inscritos no campeonato
    public function participantes()
    {
        return $this->belongsToMany(User::class, 'campeonato_user')
            ->withPivot('valor_pago', 'colocacao')
            ->withTimestamps();
    }

    // Prêmios de cada colocação (preenchidos ao finalizar o campeonato)
    public function premios()
    {
        return $this->hasMany(Premio::class);
    }

    public function finalizado(): bool
    {
        return $this->status === 'finalizado';
    }

    // Imagem do card; sem url cadastrada usa o banner padrão.
    // Caminhos relativos (ex.: os do seeder) viram url de public/.
    public function getBannerAttribute(): string
    {
        $imagem = $this->imagem ?: 'images/campeonatos/banner-torneio-pokemon.png';

        return str_starts_with($imagem, 'http') ? $imagem : asset($imagem);
    }

    // "22 de agosto de 2026"
    public function getDataExtensoAttribute(): string
    {
        return $this->data->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y');
    }

    // "10:30" (o banco devolve "10:30:00")
    public function getHorarioCurtoAttribute(): ?string
    {
        return $this->horario ? substr($this->horario, 0, 5) : null;
    }
}
