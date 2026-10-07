<?php

namespace App\Http\Requests;

use App\Models\Aluguel;
use App\Services\Configuracoes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Criação (POST) e edição de uma data (PUT) de aluguel de mesa. Repetir
 * toda semana só existe na criação.
 */
class SalvarAluguelRequest extends FormRequest
{
    // Limite de semanas, duração máxima e tipos de jogo vêm de
    // Configurações > Mesas e aluguéis (alugueis.*).

    private function maxSemanas(): int
    {
        return (int) Configuracoes::valor('alugueis.max_semanas');
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'responsavel' => trim((string) $this->responsavel) ?: null,
            'jogo' => trim((string) $this->jogo) ?: null,
            'observacoes' => trim((string) $this->observacoes) ?: null,
            'repetir' => $this->repetir ?: 'nao',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $criando = $this->isMethod('post');

        return [
            // Na criação só mesas ativas; na edição a reserva pode continuar
            // numa mesa que foi desativada depois.
            'mesa_id' => ['required', 'integer', $criando
                ? Rule::exists('mesas', 'id')->where('ativa', true)
                : Rule::exists('mesas', 'id')],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'responsavel' => ['nullable', 'required_without:cliente_id', 'string', 'max:120'],
            'valor' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'tipo_jogo' => ['required', Rule::in($this->tiposDeJogoAceitos())],
            'jogo' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:255'],
            'repetir' => [$criando ? 'required' : 'nullable', 'in:nao,semanal'],
            'repetir_ate' => ['nullable', 'required_if:repetir,semanal', 'date_format:Y-m-d', 'after_or_equal:data'],
        ];
    }

    /**
     * Regras que dependem de mais de um campo: duração e limite de semanas.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            [$inicio, $fim] = $this->intervalo();
            $minutos = $inicio->diffInMinutes($fim);

            if ($minutos < 15) {
                $validator->errors()->add('hora_fim', 'O aluguel precisa ter pelo menos 15 minutos.');
            } elseif ($minutos > ($maxima = (int) Configuracoes::valor('alugueis.duracao_maxima'))) {
                $validator->errors()->add('hora_fim', 'O aluguel pode durar no máximo '.($maxima / 60).' horas.');
            }

            if ($this->repetir === 'semanal' && count($this->inicios()) > $this->maxSemanas()) {
                $validator->errors()->add('repetir_ate', 'Repita por no máximo '.$this->maxSemanas().' semanas.');
            }
        });
    }

    /**
     * Tipos configurados + o tipo que a reserva editada já tem (pode ter
     * saído da lista depois que ela foi feita).
     *
     * @return array<int, string>
     */
    private function tiposDeJogoAceitos(): array
    {
        $tipos = array_keys(Configuracoes::tiposJogo());
        $aluguel = $this->route('aluguel');

        return $aluguel instanceof Aluguel ? [...$tipos, $aluguel->tipo_jogo] : $tipos;
    }

    /**
     * Início e fim da (primeira) data. Se o término for menor ou igual ao
     * início, ele é no dia seguinte (ex.: 22:00 até 01:00).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function intervalo(): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d H:i', $this->data.' '.$this->hora_inicio)->startOfMinute();
        $fim = Carbon::createFromFormat('Y-m-d H:i', $this->data.' '.$this->hora_fim)->startOfMinute();

        if ($fim->lessThanOrEqualTo($inicio)) {
            $fim->addDay();
        }

        return [$inicio, $fim];
    }

    /**
     * Inícios de todas as datas que serão criadas: só a primeira, ou uma
     * por semana até "repetir_ate".
     *
     * @return array<int, Carbon>
     */
    public function inicios(): array
    {
        [$inicio] = $this->intervalo();

        if ($this->repetir !== 'semanal') {
            return [$inicio];
        }

        $ate = Carbon::createFromFormat('Y-m-d', $this->repetir_ate)->endOfDay();
        $inicios = [];

        for ($data = $inicio->copy(); $data->lessThanOrEqualTo($ate) && count($inicios) <= $this->maxSemanas(); $data->addWeek()) {
            $inicios[] = $data->copy();
        }

        return $inicios;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mesa_id.required' => 'Escolha a mesa.',
            'mesa_id.exists' => 'Essa mesa não está disponível.',
            'data.required' => 'Informe a data.',
            'hora_inicio.required' => 'Informe o horário de início.',
            'hora_inicio.date_format' => 'Horário de início inválido.',
            'hora_fim.required' => 'Informe o horário de término.',
            'hora_fim.date_format' => 'Horário de término inválido.',
            'responsavel.required_without' => 'Escolha um cliente ou digite o nome de quem está alugando.',
            'responsavel.max' => 'O nome pode ter no máximo :max caracteres.',
            'valor.required' => 'Informe o valor.',
            'valor.numeric' => 'Informe um valor numérico.',
            'valor.min' => 'O valor não pode ser negativo.',
            'tipo_jogo.required' => 'Escolha o tipo de jogo.',
            'tipo_jogo.in' => 'Tipo de jogo inválido.',
            'jogo.max' => 'O nome do jogo pode ter no máximo :max caracteres.',
            'observacoes.max' => 'As observações podem ter no máximo :max caracteres.',
            'repetir_ate.required_if' => 'Até quando o aluguel se repete?',
            'repetir_ate.after_or_equal' => 'A data final precisa ser depois da primeira data.',
        ];
    }
}
