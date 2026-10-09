<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampeonatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // O campo de valor aceita "R$15,00", "15,00" ou "15.00"
    protected function prepareForValidation(): void
    {
        $valor = preg_replace('/[^\d,.]/', '', (string) $this->input('valor_inscricao'));

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        $this->merge(['valor_inscricao' => $valor]);
    }

    public function rules(): array
    {
        return [
            'nome'            => 'required|string|max:255',
            'deck'            => 'required|string|max:255',
            'data'            => 'required|date',
            'horario'         => 'nullable|date_format:H:i',
            'valor_inscricao' => 'required|numeric|min:0',
            'descricao'       => 'nullable|string|max:350',
            'imagem'          => 'nullable|url|max:500',
            'status'          => 'required|in:ativo,finalizado',
            'participantes'   => 'nullable|array',
            'participantes.*' => 'integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'            => 'O campo nome é obrigatório.',
            'deck.required'            => 'O campo deck é obrigatório.',
            'data.required'            => 'O campo data é obrigatório.',
            'data.date'                => 'Informe uma data válida.',
            'horario.date_format'      => 'Informe um horário válido.',
            'valor_inscricao.required' => 'O campo valor da inscrição é obrigatório.',
            'valor_inscricao.numeric'  => 'O valor da inscrição deve ser numérico.',
            'valor_inscricao.min'      => 'O valor da inscrição não pode ser negativo.',
            'imagem.url'               => 'A imagem deve ser um link válido (https://...).',
            'status.in'                => 'Escolha um status válido.',
            'participantes.*.exists'   => 'Um dos participantes não foi encontrado.',
        ];
    }
}
