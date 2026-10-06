<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Um lançamento de ajuste de créditos de um cliente (adicionar, descontar ou
 * definir o saldo diretamente). Só leitura pelo app — os registros são
 * criados pelo ClienteController ao salvar uma mudança de saldo, nunca
 * editados ou apagados, para servir como trilha de auditoria simples.
 */
class ClienteCreditoHistorico extends Model
{
    const UPDATED_AT = null;

    protected $table = 'cliente_creditos_historico';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'valor',
        'saldo_anterior',
        'saldo_novo',
        'motivo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_novo' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
