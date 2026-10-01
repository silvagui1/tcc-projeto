<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoModel extends Model
{
    use HasFactory;

    protected $table = 'campeonatos';
    protected $fillable = [
        'nome', 
        'deck', 
        'data', 
        'valor_inscricao', 
        'imagem', 
        'descricao', 
        'status'
    ];

    protected $casts = [
        'data' => 'datetime',
        'valor_inscricao' => 'decimal:2',
    ];

    public function participantes(){
        return $this->belongsToMany(User::class, 'campeonato_user')
        ->withPivot('valor_pago', 'colocacao')
        ->withTimestamps();
    }

    public function premios(){
        return $this->hasMany(Premio::class);
    }
}
