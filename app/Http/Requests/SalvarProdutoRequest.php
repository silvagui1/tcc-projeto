<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TrataImagemDoEstoque;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação do pop-up de produto — o mesmo formulário serve para adicionar
 * e para editar.
 */
class SalvarProdutoRequest extends FormRequest
{
    use TrataImagemDoEstoque;

    /**
     * Erros num "bag" próprio ($errors->produto), como em SalvarCartaRequest.
     */
    protected $errorBag = 'produto';

    public function authorize(): bool
    {
        // Sem sistema de autenticação implementado ainda.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'quantidade' => ['required', 'integer', 'min:0', 'max:99999'],
            'preco' => ['required', 'numeric', 'min:0', 'max:999999'],
        ] + $this->regrasDeImagem();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'categoria_id' => 'categoria',
            'descricao' => 'descrição',
            'preco' => 'preço',
            'imagem' => 'url da imagem',
            'imagem_arquivo' => 'imagem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'exists' => 'Escolha uma opção válida em :attribute.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute passou do limite (máx. :max).',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'url' => 'O campo :attribute deve ser um link válido (https://...).',
        ] + $this->mensagensDeImagem();
    }
}
