<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    use HasFactory;

    protected $table = 'premios';
    protected $fillable = [
        'campeonato_id',
        'user_id',
        'colocacao',
        'valor',
        'descricao'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'colocacao' => 'integer',
    ];

    public function campeonato(){
        return $this->belongsTo(Campeonato::class);
    }

    public function usuario(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
