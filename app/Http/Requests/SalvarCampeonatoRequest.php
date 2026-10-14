<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulário de criar e de editar campeonato (mesmos campos, ver
 * resources/views/pages/campeonatos/partials/campos-principais.blade.php).
 */
class SalvarCampeonatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O campo de inscrição aceita "R$15,00", "15,00" ou "15.00".
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->nome),
            'inscricao' => self::valorEmReais((string) $this->inscricao),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'jogo' => ['required', Rule::in(jogosDeCartas())],
            'deck' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date'],
            'horario' => ['nullable', 'date_format:H:i'],
            'inscricao' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'descricao' => ['nullable', 'string', 'max:350'],
            'imagem' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:ativo,finalizado'],
            'participantes' => ['nullable', 'array'],
            'participantes.*' => ['integer', 'exists:clientes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Dê um nome para o campeonato.',
            'jogo.required' => 'Escolha o jogo do campeonato.',
            'jogo.in' => 'Escolha um jogo válido.',
            'deck.required' => 'Informe o deck.',
            'data.required' => 'Informe a data do campeonato.',
            'data.date' => 'Informe uma data válida.',
            'horario.date_format' => 'Informe um horário válido.',
            'inscricao.required' => 'Informe o valor da inscrição.',
            'inscricao.numeric' => 'O valor da inscrição deve ser numérico.',
            'inscricao.min' => 'O valor da inscrição não pode ser negativo.',
            'descricao.max' => 'A descrição pode ter no máximo :max caracteres.',
            'imagem.url' => 'A imagem deve ser um link válido (https://...).',
            'status.in' => 'Escolha um status válido.',
            'participantes.*.exists' => 'Um dos participantes não foi encontrado.',
        ];
    }

    /**
     * Campos já com os nomes das colunas de campeonatos.
     *
     * @return array<string, mixed>
     */
    public function dadosCampeonato(): array
    {
        $dados = $this->safe()->except(['inscricao', 'participantes']);
        $dados['valor_inscricao'] = $this->validated('inscricao');

        return $dados;
    }

    /**
     * "R$ 1.234,56" / "10,00" / "10.5" → "1234.56" / "10.00" / "10.5".
     * Vazio continua vazio (para o "required" avisar).
     */
    public static function valorEmReais(string $valor): string
    {
        $valor = preg_replace('/[^\d,.]/', '', $valor);

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return $valor;
    }
}
