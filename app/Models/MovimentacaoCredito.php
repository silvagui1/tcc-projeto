<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentacaoCredito extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes_credito';
    protected $fillable = [
        'user_id', 
        'campeonato_id',  
        'valor',  
        'descricao', 
        'tipo' 
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function usuario(){
        return $this->belongsTo(User::class);
    }

    public function campeonato(){
        return $this->belongsTo(CampeonatoModel::class);
    }
}

