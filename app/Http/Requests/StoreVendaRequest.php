<?php

namespace App\Http\Requests;

use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sem autenticação no sistema ainda (mesmo caso de StoreClienteRequest).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'itens' => ['required', 'array', 'min:1', 'max:50'],
            'itens.*.tipo' => ['required', Rule::in(VendaItem::TIPOS)],
            'itens.*.id' => ['required', 'integer', 'min:1'],
            'itens.*.quantidade' => ['nullable', 'integer', 'min:1', 'max:999'],
            'usar_creditos' => ['boolean'],
            // só as formas ligadas em Configurações > Vendas
            'forma_pagamento' => ['nullable', Rule::in(array_keys(Venda::formasAtivas()))],
            'observacoes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Adicione ao menos um item à venda.',
            'itens.min' => 'Adicione ao menos um item à venda.',
            'itens.max' => 'Uma venda pode ter no máximo :max itens.',
            'itens.*.tipo.in' => 'Tipo de item inválido.',
            'itens.*.quantidade.min' => 'A quantidade mínima é 1.',
            'itens.*.quantidade.max' => 'A quantidade máxima por item é :max.',
            'cliente_id.exists' => 'O cliente selecionado não existe mais.',
            'forma_pagamento.in' => 'Essa forma de pagamento não é aceita pela loja.',
            'observacoes.max' => 'As observações podem ter no máximo :max caracteres.',
        ];
    }
}
