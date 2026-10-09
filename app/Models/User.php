<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nascimento',
        'avatar',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'nascimento' => 'date',
    ];

    // Campeonatos em que o usuário está inscrito
    public function campeonatos()
    {
        return $this->belongsToMany(Campeonato::class, 'campeonato_user')
            ->withPivot('valor_pago', 'colocacao')
            ->withTimestamps();
    }

    public function premios()
    {
        return $this->hasMany(Premio::class);
    }

    public function movimentacoesCredito()
    {
        return $this->hasMany(MovimentacaoCredito::class);
    }

    // Foto do participante; sem foto cadastrada usa o avatar padrão.
    // Caminhos relativos (ex.: os do seeder) viram url de public/.
    public function getFotoAttribute(): string
    {
        $avatar = $this->avatar ?: 'images/campeonatos/avatar-rogerio.jpg';

        return str_starts_with($avatar, 'http') ? $avatar : asset($avatar);
    }

    // "20/06/2009"
    public function getNascimentoCurtoAttribute(): string
    {
        return $this->nascimento?->format('d/m/Y') ?? '';
    }
}
