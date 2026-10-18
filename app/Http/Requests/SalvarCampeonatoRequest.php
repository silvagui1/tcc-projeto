<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TrataImagemDoEstoque;
use App\Models\Campeonato;
use App\Models\Cliente;
use App\Models\JogoCarta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pop-up de criar e de editar campeonato (mesmos campos, ver
 * resources/views/pages/campeonatos/partials/modal-form.blade.php). Os
 * participantes só vêm ao criar; depois eles são inscritos e removidos
 * direto no pop-up do campeonato.
 */
class SalvarCampeonatoRequest extends FormRequest
{
    use TrataImagemDoEstoque;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Campos de dinheiro aceitam "R$15,00", "15,00" ou "15.00"; o WhatsApp
     * dos provisórios vem com máscara.
     */
    protected function prepareForValidation(): void
    {
        $dados = [
            'nome' => trim((string) $this->nome),
            'semanal' => $this->boolean('semanal'),
        ];

        foreach (['inscricao', 'premio_1', 'premio_2', 'premio_3'] as $campo) {
            $dados[$campo] = self::valorEmReais((string) $this->input($campo));
        }

        $dados['provisorios'] = collect($this->input('provisorios', []))
            ->filter(fn ($p) => is_array($p) && trim((string) ($p['nome'] ?? '')) !== '')
            ->map(fn (array $p) => [
                'nome' => trim((string) $p['nome']),
                'whatsapp' => preg_replace('/\D/', '', (string) ($p['whatsapp'] ?? '')) ?: null,
            ])
            ->values()
            ->all();

        $this->merge($dados);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(array_keys(Campeonato::TIPOS))],
            'jogo' => ['required', Rule::in(JogoCarta::chaves())],
            'deck' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date'],
            'horario' => ['nullable', 'date_format:H:i', 'required_if:semanal,true'],
            'semanal' => ['boolean'],
            // a inscrição é paga em dinheiro/pix/cartão (nunca com créditos)
            'inscricao' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            // o prêmio principal é sempre em créditos
            'premio_1' => ['required', 'numeric', 'gt:0', 'max:99999.99'],
            'premio_2' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'premio_3' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'premio_extra' => ['nullable', 'string', 'max:250'],
            'codigo_sancao' => ['nullable', 'string', 'max:60'],
            'descricao' => ['nullable', 'string', 'max:350'],
            // o status não vem do formulário: todo campeonato nasce "ativo" e
            // só vira "finalizado" ao salvar a premiação
            'participantes' => ['nullable', 'array'],
            'participantes.*' => ['integer', Rule::exists('clientes', 'id')->where('cadastro', Cliente::CADASTRO_CLIENTE)],
            'provisorios' => ['nullable', 'array', 'max:50'],
            'provisorios.*.nome' => ['required', 'string', 'max:255'],
            'provisorios.*.whatsapp' => ['nullable', 'regex:/^\d{10,11}$/'],
            // "Repetir": campeonato de onde vem o banner, se ele não for trocado
            'copiar_de' => ['nullable', 'integer', 'exists:campeonatos,id'],
        ] + $this->regrasDeImagem();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Dê um nome para o campeonato.',
            'tipo.required' => 'Escolha o tipo do campeonato.',
            'tipo.in' => 'Escolha um tipo válido.',
            'jogo.required' => 'Escolha o jogo do campeonato.',
            'jogo.in' => 'Escolha um jogo válido.',
            'deck.required' => 'Informe o formato ou deck.',
            'data.required' => 'Informe a data do campeonato.',
            'data.date' => 'Informe uma data válida.',
            'horario.date_format' => 'Informe um horário válido.',
            'horario.required_if' => 'Campeonato semanal precisa de horário.',
            'inscricao.required' => 'Informe o valor da inscrição (0 para gratuita).',
            'inscricao.numeric' => 'O valor da inscrição deve ser numérico.',
            'inscricao.min' => 'O valor da inscrição não pode ser negativo.',
            'premio_1.required' => 'Informe quantos créditos o 1° lugar ganha.',
            'premio_1.gt' => 'O prêmio do 1° lugar é obrigatório e sempre em créditos.',
            'premio_1.numeric' => 'Informe um valor válido de créditos.',
            'premio_2.numeric' => 'Informe um valor válido de créditos.',
            'premio_3.numeric' => 'Informe um valor válido de créditos.',
            'premio_extra.max' => 'O prêmio extra pode ter no máximo :max caracteres.',
            'codigo_sancao.max' => 'O código da sanção pode ter no máximo :max caracteres.',
            'descricao.max' => 'A descrição pode ter no máximo :max caracteres.',
            'imagem.url' => 'O link da imagem deve começar com https://',
            'participantes.*.exists' => 'Um dos participantes não foi encontrado.',
            'provisorios.*.nome.required' => 'Informe o nome do participante provisório.',
            'provisorios.*.whatsapp.regex' => 'WhatsApp do provisório: informe DDD + número.',
        ] + $this->mensagensDeImagem();
    }

    /**
     * Campos já com os nomes das colunas de campeonatos (e a imagem já
     * resolvida: arquivo enviado, link ou nenhuma).
     *
     * @return array<string, mixed>
     */
    public function dadosCampeonato(?Campeonato $atual = null): array
    {
        $dados = collect($this->dadosComImagem('campeonatos', $atual))
            ->except(['inscricao', 'participantes', 'provisorios', 'copiar_de'])
            ->all();

        // repetindo um campeonato sem mexer no banner: fica o do original
        // (as ocorrências dividem o arquivo, ver Campeonato::apagarArquivoDeImagem)
        if (! $atual && $dados['imagem'] === null && ! $this->boolean('remover_imagem') && $this->validated('copiar_de')) {
            $dados['imagem'] = Campeonato::whereKey($this->validated('copiar_de'))->value('imagem');
        }

        $dados['valor_inscricao'] = $this->validated('inscricao');
        foreach (['premio_2', 'premio_3'] as $campo) {
            $dados[$campo] = $dados[$campo] ?: 0;
        }

        // código de sanção só existe em evento oficial
        if (! (Campeonato::TIPOS[$dados['tipo']]['oficial'] ?? false)) {
            $dados['codigo_sancao'] = null;
        }

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
