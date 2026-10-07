<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uma configuração alterada pela loja. Não usar direto: o acesso passa por
 * App\Services\Configuracoes, que também conhece os valores padrão.
 */
class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $primaryKey = 'chave';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['chave', 'valor'];

    protected $casts = [
        'valor' => 'json',
    ];
}
