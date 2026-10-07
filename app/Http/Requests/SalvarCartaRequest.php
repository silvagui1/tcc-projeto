<?php

namespace App\Http\Requests;

use App\Models\Carta;
use App\Models\JogoCarta;
use App\Services\Configuracoes;
use App\Http\Requests\Concerns\TrataImagemDoEstoque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação do pop-up de carta — o mesmo formulário serve para adicionar e
 * para editar, então as regras são as mesmas nos dois casos.
 */
class SalvarCartaRequest extends FormRequest
{
    use TrataImagemDoEstoque;

    /**
     * Os erros ficam num "bag" próprio ($errors->carta) para não se
     * misturarem com os de outros formulários da mesma página.
     */
    protected $errorBag = 'carta';

    public function authorize(): bool
    {
        // Sem sistema de autenticação implementado ainda.
        return true;
    }

    /**
     * O checkbox "foil" desmarcado simplesmente não é enviado pelo
     * navegador — aqui ele vira false explicitamente.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['foil' => $this->boolean('foil')]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'jogo' => ['required', Rule::in(JogoCarta::chaves())],
            'colecao' => ['nullable', 'string', 'max:150'],
            'raridade' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', Rule::in($this->opcoesComValorAtual('estoque.estados_carta', 'estado'))],
            'idioma' => ['required', Rule::in($this->opcoesComValorAtual('estoque.idiomas_carta', 'idioma'))],
            'foil' => ['boolean'],
            'quantidade' => ['required', 'integer', 'min:0', 'max:9999'],
            'preco' => ['required', 'numeric', 'min:0', 'max:999999'],
        ] + $this->regrasDeImagem();
    }

    /**
     * @return array<string, string>
     */
    /**
     * Opções configuradas + o valor que a carta editada já tem: se alguém
     * tirou "Danificado" da lista, uma carta antiga com esse estado ainda
     * pode ser salva sem trocar de estado à força.
     *
     * @return array<int, string>
     */
    private function opcoesComValorAtual(string $configuracao, string $campo): array
    {
        $opcoes = Configuracoes::valor($configuracao);
        $carta = $this->route('carta');

        return $carta instanceof Carta ? [...$opcoes, $carta->{$campo}] : $opcoes;
    }

    public function attributes(): array
    {
        return [
            'colecao' => 'coleção',
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
            'in' => 'Escolha uma opção válida em :attribute.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute passou do limite (máx. :max).',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'url' => 'O campo :attribute deve ser um link válido (https://...).',
            'boolean' => 'O campo :attribute é inválido.',
        ] + $this->mensagensDeImagem();
    }
}
