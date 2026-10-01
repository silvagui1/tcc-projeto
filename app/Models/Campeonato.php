<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campeonato extends Model
{
    use HasFactory;

    protected $table = 'campeonatos';

    protected $fillable = ['nome', 'deck', 'data', 'horario', 'valor', 'descricao', 'imagem', 'status'];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
    ];

    // Usuários inscritos no campeonato
    public function users()
    {
        return $this->belongsToMany(User::class, 'campeonato_user')->withTimestamps();
    }

    public function finalizado(): bool
    {
        return $this->status === 'finalizado';
    }

    // Imagem do card; sem url cadastrada usa o banner padrão
    public function getBannerAttribute(): string
    {
        return $this->imagem ?: asset('images/campeonatos/banner-torneio-pokemon.png');
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
