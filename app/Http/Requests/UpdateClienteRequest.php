<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Sem sistema de autenticação implementado ainda: qualquer
        // acesso à tela libera a edição.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'creditos' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'data_nascimento' => 'data de nascimento',
            'foto' => 'foto',
            'observacoes' => 'observações',
            'creditos' => 'créditos',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do cliente.',
            'nome.string' => 'O nome deve ser um texto.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.date' => 'Informe uma data de nascimento válida.',
            'data_nascimento.before_or_equal' => 'A data de nascimento não pode ser no futuro.',
            'foto.image' => 'O arquivo enviado precisa ser uma imagem.',
            'foto.mimes' => 'A foto deve estar em formato JPG, PNG ou WEBP.',
            'foto.max' => 'A foto deve ter no máximo 2MB.',
            'observacoes.string' => 'As observações devem ser um texto.',
            'observacoes.max' => 'As observações podem ter no máximo :max caracteres.',
            'creditos.required' => 'Informe o valor de créditos do cliente.',
            'creditos.numeric' => 'Os créditos devem ser um valor numérico.',
            'creditos.min' => 'Os créditos não podem ser negativos.',
        ];
    }
}
