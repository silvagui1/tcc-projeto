<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarMesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nome' => trim((string) $this->nome)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('mesas', 'nome')->ignore($this->route('mesa'))],
            'capacidade' => ['required', 'integer', 'min:1', 'max:50'],
            'preco_hora' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'ativa' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Dê um nome para a mesa.',
            'nome.unique' => 'Já existe uma mesa com esse nome.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
            'capacidade.required' => 'Informe quantos lugares a mesa tem.',
            'capacidade.min' => 'A mesa precisa ter pelo menos 1 lugar.',
            'capacidade.max' => 'Máximo de :max lugares.',
            'preco_hora.required' => 'Informe o preço por hora.',
            'preco_hora.numeric' => 'Informe um valor numérico.',
            'preco_hora.min' => 'O preço não pode ser negativo.',
        ];
    }
}
