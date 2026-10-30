<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Ajuste rápido de quantidade de um produto ou carta (página de detalhes).
 * Os erros ficam no bag "ajuste", para não se misturar com os do pop-up de
 * edição na mesma página.
 */
class AjustarEstoqueRequest extends FormRequest
{
    protected $errorBag = 'ajuste';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:entrada,saida'],
            'quantidade' => ['required', 'integer', 'min:1', 'max:100000'],
            'motivo' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantidade.required' => 'Informe a quantidade.',
            'quantidade.min' => 'A quantidade precisa ser pelo menos 1.',
        ];
    }

    /**
     * Saída não pode deixar o estoque negativo.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $item = $this->route('produto') ?? $this->route('carta');

                if ($this->input('tipo') === 'saida' && (int) $this->input('quantidade') > $item->quantidade) {
                    $validator->errors()->add('quantidade', "Só há {$item->quantidade} em estoque.");
                }
            },
        ];
    }

    public function diferenca(): int
    {
        $quantidade = (int) $this->validated('quantidade');

        return $this->validated('tipo') === 'entrada' ? $quantidade : -$quantidade;
    }

    public function motivo(): string
    {
        $motivo = trim((string) $this->validated('motivo'));

        if ($motivo !== '') {
            return $motivo;
        }

        return $this->validated('tipo') === 'entrada' ? 'Entrada de mercadoria' : 'Saída manual';
    }
}
