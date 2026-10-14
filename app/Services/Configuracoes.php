<?php

namespace App\Services;

use App\Models\Configuracao;

/**
 * Configurações gerais da loja (tela Configurações). Valem para todos os
 * computadores da loja — não há login, então não existe configuração "por
 * usuário" (o tema claro/escuro, que é por aparelho, fica no navegador).
 *
 * Os valores padrão moram aqui; o banco (tabela configuracoes) só guarda o
 * que alguém alterou. É registrado como singleton (ver AppServiceProvider):
 * a tabela é lida uma vez por requisição.
 *
 * Uso: Configuracoes::valor('vendas.por_pagina').
 */
class Configuracoes
{
    /**
     * Cores e ícones que um tipo de jogo de mesa pode usar. A cor vira uma
     * classe CSS (ver .jogo-chip--{cor} em resources/css/vendas.css).
     */
    public const CORES_TIPO_JOGO = [
        'roxo' => 'Roxo',
        'azul' => 'Azul',
        'rosa' => 'Rosa',
        'verde' => 'Verde',
        'laranja' => 'Laranja',
        'cinza' => 'Cinza',
    ];

    public const ICONES_TIPO_JOGO = [
        'bi-dice-6' => 'Dado',
        'bi-suit-spade' => 'Cartas',
        'bi-grid-3x3-gap' => 'Tabuleiro',
        'bi-puzzle' => 'Quebra-cabeça',
        'bi-book' => 'Livro',
        'bi-trophy' => 'Troféu',
        'bi-people' => 'Grupo',
        'bi-controller' => 'Controle',
    ];

    public const PADROES = [
        // Loja
        'loja.nome' => 'ArtPlay',
        'loja.cnpj' => null,
        'loja.telefone' => null,
        'loja.whatsapp' => null,
        'loja.endereco' => null,
        'loja.logo' => null,
        // índice = dia da semana (0 = domingo ... 6 = sábado)
        'loja.horario' => [
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
            ['aberto' => true, 'abre' => '10:00', 'fecha' => '22:00'],
        ],

        // Vendas
        'vendas.formas_pagamento' => ['dinheiro', 'pix', 'debito', 'credito'],
        'vendas.permitir_creditos' => true,
        'vendas.exigir_motivo_cancelamento' => false,
        'vendas.por_pagina' => 20,

        // Mesas e aluguéis
        'alugueis.tipos_jogo' => [
            ['chave' => 'rpg', 'nome' => 'RPG', 'cor' => 'roxo', 'icone' => 'bi-dice-6'],
            ['chave' => 'cartas', 'nome' => 'Cartas', 'cor' => 'azul', 'icone' => 'bi-suit-spade'],
            ['chave' => 'tabuleiro', 'nome' => 'Tabuleiro', 'cor' => 'rosa', 'icone' => 'bi-grid-3x3-gap'],
            ['chave' => 'outro', 'nome' => 'Outro', 'cor' => 'cinza', 'icone' => 'bi-controller'],
        ],
        'alugueis.duracao_padrao' => 180,   // minutos
        'alugueis.duracao_maxima' => 720,   // minutos
        'alugueis.max_semanas' => 26,
        'alugueis.intervalo' => 0,          // minutos livres entre reservas da mesma mesa

        // Estoque
        'estoque.alerta_ativo' => true,
        'estoque.alerta_minimo' => 5,
        'estoque.estados_carta' => ['Novo', 'Semi-Novo', 'Usado', 'Danificado'],
        'estoque.idiomas_carta' => ['Português', 'Inglês', 'Japonês', 'Espanhol', 'Outro'],

        // Clientes e créditos
        'clientes.valores_rapidos' => [10, 20, 50, 100],
        'clientes.motivos_credito' => ['Compra no balcão', 'Estorno', 'Bonificação', 'Correção de lançamento'],
        'clientes.whatsapp_ddi' => '55',
    ];

    private ?array $valores = null;

    public static function valor(string $chave): mixed
    {
        return app(self::class)->get($chave);
    }

    public function get(string $chave): mixed
    {
        $todas = $this->todas();

        return array_key_exists($chave, $todas) ? $todas[$chave] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function todas(): array
    {
        if ($this->valores === null) {
            $salvas = Configuracao::query()->get()->mapWithKeys(fn (Configuracao $c) => [$c->chave => $c->valor])->all();
            $this->valores = array_replace(self::PADROES, array_intersect_key($salvas, self::PADROES));
        }

        return $this->valores;
    }

    /**
     * Grava várias chaves de uma vez. Chaves desconhecidas são ignoradas.
     *
     * @param  array<string, mixed>  $valores
     */
    public function salvar(array $valores): void
    {
        foreach (array_intersect_key($valores, self::PADROES) as $chave => $valor) {
            Configuracao::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
        }

        $this->valores = null;
    }

    // ---------------------------------------------------------------------
    // Atalhos usados em vários lugares
    // ---------------------------------------------------------------------

    /**
     * Tipos de jogo das mesas, indexados pela chave.
     *
     * @return array<string, array{chave: string, nome: string, cor: string, icone: string}>
     */
    public static function tiposJogo(): array
    {
        return collect(self::valor('alugueis.tipos_jogo'))->keyBy('chave')->all();
    }

    /**
     * Dados de exibição de um tipo de jogo — inclusive de um tipo que já foi
     * removido da lista mas ficou gravado numa reserva antiga.
     *
     * @return array{chave: string, nome: string, cor: string, icone: string}
     */
    public static function tipoJogo(?string $chave): array
    {
        return self::tiposJogo()[$chave] ?? [
            'chave' => (string) $chave,
            'nome' => $chave ? ucfirst($chave) : 'Outro',
            'cor' => 'cinza',
            'icone' => 'bi-controller',
        ];
    }

    /**
     * Horário de um dia: ['aberto', 'abre', 'fecha'] (fecha "00:00" = meia-noite).
     *
     * @return array{aberto: bool, abre: string, fecha: string}
     */
    public static function horarioDoDia(int $diaDaSemana): array
    {
        return self::valor('loja.horario')[$diaDaSemana] ?? self::PADROES['loja.horario'][$diaDaSemana];
    }

    /**
     * Abertura e fechamento de um dia em minutos desde a meia-noite, ou null
     * se a loja não abre. Fechar no mesmo horário ou antes de abrir (ex.:
     * 18:00 às 02:00, ou "00:00") vira o dia seguinte: 18:00–26:00.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function faixaDoDia(int $diaDaSemana): ?array
    {
        $horario = self::horarioDoDia($diaDaSemana);

        if (empty($horario['aberto'])) {
            return null;
        }

        [$h, $m] = array_map('intval', explode(':', $horario['abre']));
        $abre = $h * 60 + $m;
        [$h, $m] = array_map('intval', explode(':', $horario['fecha']));
        $fecha = $h * 60 + $m;

        return [$abre, $fecha <= $abre ? $fecha + 1440 : $fecha];
    }

    /**
     * URL pública da logo enviada, ou null quando a loja usa a logo padrão.
     */
    public static function logoUrl(): ?string
    {
        $logo = self::valor('loja.logo');

        return $logo ? asset('storage/'.$logo) : null;
    }
}
