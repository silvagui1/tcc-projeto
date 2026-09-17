<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampModel extends Model
{
    use HasFactory;

    protected $table = 'campeonatos'
    protected $fillable = ['nome', 'deck', 'hora', 'data', 'valor', 'participantes', 'descricao' ]
}
