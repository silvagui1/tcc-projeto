<?php

namespace App\Http\Controllers;

use App\Models\Aluguel;
use App\Models\Atividade;
use App\Models\Carta;
use App\Models\Categoria;
use App\Models\JogoCarta;
use App\Models\Mesa;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\VendaItem;
use App\Services\Configuracoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Tela de Configurações. Cada seção (Loja, Vendas, Mesas e aluguéis,
 * Estoque, Clientes) é salva separadamente; categorias e jogos de carta são
 * cadastros à parte, salvos na hora. Tudo vale para a loja inteira — ainda
 * não há login, então não existe configuração por pessoa (o tema claro/escuro
 * é a exceção: fica no navegador de cada aparelho).
 */
class ConfiguracaoController extends Controller
{
    private const ATIVIDADES_POR_PAGINA = 20;

    private const OPCOES_POR_PAGINA = [10, 20, 30, 50];

    private const OPCOES_INTERVALO = [0, 5, 10, 15, 20, 30, 45, 60];

    public function __construct(private Configuracoes $configuracoes)
    {
    }

    public function index(): View
    {
        return view('pages.config', [
            'config' => $this->configuracoes->todas(),
            'logoUrl' => Configuracoes::logoUrl(),
            'formasPagamento' => Venda::FORMAS_PAGAMENTO,
            'opcoesPorPagina' => self::OPCOES_POR_PAGINA,
            'opcoesIntervalo' => self::OPCOES_INTERVALO,
            'cores' => Configuracoes::CORES_TIPO_JOGO,
            'icones' => Configuracoes::ICONES_TIPO_JOGO,
            'tiposEmUso' => Aluguel::query()->distinct()->pluck('tipo_jogo')->all(),
            'mesas' => ['total' => Mesa::count(), 'ativas' => Mesa::ativas()->count()],
            'categorias' => $this->listaCategorias(),
            'jogosCarta' => $this->listaJogos(),
            'atividades' => $this->paginaAtividades(null, null),
            'areas' => Atividade::AREAS,
        ]);
    }

    // ---------------------------------------------------------------------
    // Loja
    // ---------------------------------------------------------------------

    public function salvarLoja(Request $request): JsonResponse
    {
        $digitos = fn ($valor) => preg_replace('/\D/', '', (string) $valor) ?: null;
        $request->merge([
            'nome' => trim((string) $request->input('nome')),
            'cnpj' => $digitos($request->input('cnpj')),
            'telefone' => $digitos($request->input('telefone')),
            'whatsapp' => $digitos($request->input('whatsapp')),
            'endereco' => trim((string) $request->input('endereco')) ?: null,
        ]);

        $validador = validator($request->all(), [
            'nome' => ['required', 'string', 'max:60'],
            'cnpj' => ['nullable', 'digits:14'],
            'telefone' => ['nullable', 'digits_between:10,11'],
            'whatsapp' => ['nullable', 'digits_between:10,11'],
            'endereco' => ['nullable', 'string', 'max:200'],
            'horario' => ['required', 'array', 'size:7'],
            'horario.*.aberto' => ['nullable', 'boolean'],
            'horario.*.abre' => ['nullable', 'date_format:H:i'],
            'horario.*.fecha' => ['nullable', 'date_format:H:i'],
        ], [
            'nome.required' => 'Informe o nome da loja.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
            'cnpj.digits' => 'O CNPJ precisa ter 14 números.',
            'telefone.digits_between' => 'Telefone inválido: use DDD + número.',
            'whatsapp.digits_between' => 'WhatsApp inválido: use DDD + número.',
            'endereco.max' => 'O endereço pode ter no máximo :max caracteres.',
            'horario.*.abre.date_format' => 'Horário inválido.',
            'horario.*.fecha.date_format' => 'Horário inválido.',
        ]);

        $validador->after(function (Validator $v) use ($request) {
            foreach ((array) $request->input('horario') as $dia => $horario) {
                if (empty($horario['aberto'])) {
                    continue;
                }
                if (empty($horario['abre']) || empty($horario['fecha'])) {
                    $v->errors()->add("horario.{$dia}.abre", 'Informe a abertura e o fechamento.');
                } elseif ($horario['abre'] === $horario['fecha']) {
                    $v->errors()->add("horario.{$dia}.abre", 'Abertura e fechamento não podem ser iguais.');
                }
            }
        });

        $dados = $validador->validate();
        $atual = $this->configuracoes->get('loja.horario');

        $horario = collect(range(0, 6))->map(fn (int $dia) => [
            'aberto' => (bool) ($dados['horario'][$dia]['aberto'] ?? false),
            'abre' => $dados['horario'][$dia]['abre'] ?? $atual[$dia]['abre'],
            'fecha' => $dados['horario'][$dia]['fecha'] ?? $atual[$dia]['fecha'],
        ])->all();

        $this->configuracoes->salvar([
            'loja.nome' => $dados['nome'],
            'loja.cnpj' => $dados['cnpj'],
            'loja.telefone' => $dados['telefone'],
            'loja.whatsapp' => $dados['whatsapp'],
            'loja.endereco' => $dados['endereco'],
            'loja.horario' => $horario,
        ]);

        Atividade::registrar('configuracoes', 'Dados da loja atualizados');

        return $this->salvo('Dados da loja salvos.');
    }

    public function enviarLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ], [
            'logo.required' => 'Escolha uma imagem.',
            'logo.image' => 'O arquivo precisa ser uma imagem.',
            'logo.mimes' => 'Use PNG, JPG ou WEBP.',
            'logo.max' => 'A imagem pode ter no máximo 1 MB.',
        ]);

        $anterior = $this->configuracoes->get('loja.logo');
        $caminho = $request->file('logo')->store('loja', 'public');
        $this->configuracoes->salvar(['loja.logo' => $caminho]);

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        Atividade::registrar('configuracoes', 'Logo da loja trocada');

        return response()->json(['success' => true, 'message' => 'Logo atualizada.', 'logo_url' => Configuracoes::logoUrl()]);
    }

    public function removerLogo(): JsonResponse
    {
        if ($logo = $this->configuracoes->get('loja.logo')) {
            Storage::disk('public')->delete($logo);
        }

        $this->configuracoes->salvar(['loja.logo' => null]);
        Atividade::registrar('configuracoes', 'Logo da loja removida (voltou a padrão)');

        return response()->json(['success' => true, 'message' => 'A loja voltou a usar a logo padrão.', 'logo_url' => null]);
    }

    // ---------------------------------------------------------------------
    // Vendas
    // ---------------------------------------------------------------------

    public function salvarVendas(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'formas_pagamento' => ['required', 'array', 'min:1'],
            'formas_pagamento.*' => [Rule::in(array_keys(Venda::FORMAS_PAGAMENTO))],
            'permitir_creditos' => ['required', 'boolean'],
            'exigir_motivo_cancelamento' => ['required', 'boolean'],
            'por_pagina' => ['required', 'integer', Rule::in(self::OPCOES_POR_PAGINA)],
        ], [
            'formas_pagamento.required' => 'Deixe ao menos uma forma de pagamento ligada.',
            'formas_pagamento.min' => 'Deixe ao menos uma forma de pagamento ligada.',
        ]);

        // mantém a ordem de Venda::FORMAS_PAGAMENTO
        $formas = array_values(array_intersect(array_keys(Venda::FORMAS_PAGAMENTO), $dados['formas_pagamento']));

        $this->configuracoes->salvar([
            'vendas.formas_pagamento' => $formas,
            'vendas.permitir_creditos' => (bool) $dados['permitir_creditos'],
            'vendas.exigir_motivo_cancelamento' => (bool) $dados['exigir_motivo_cancelamento'],
            'vendas.por_pagina' => (int) $dados['por_pagina'],
        ]);

        Atividade::registrar('configuracoes', 'Configurações de vendas alteradas');

        return $this->salvo('Configurações de vendas salvas.');
    }

    // ---------------------------------------------------------------------
    // Mesas e aluguéis
    // ---------------------------------------------------------------------

    public function salvarAlugueis(Request $request): JsonResponse
    {
        $tipos = collect($request->input('tipos', []))
            ->map(fn ($tipo) => [
                'chave' => (string) ($tipo['chave'] ?? ''),
                'nome' => trim((string) ($tipo['nome'] ?? '')),
                'cor' => (string) ($tipo['cor'] ?? ''),
                'icone' => (string) ($tipo['icone'] ?? ''),
            ])
            ->values();
        $request->merge(['tipos' => $tipos->all()]);

        $validador = validator($request->all(), [
            'duracao_padrao' => ['required', 'integer', 'min:30', 'max:1440'],
            'duracao_maxima' => ['required', 'integer', 'min:60', 'max:1440', 'gte:duracao_padrao'],
            'max_semanas' => ['required', 'integer', 'min:1', 'max:52'],
            'intervalo' => ['required', 'integer', Rule::in(self::OPCOES_INTERVALO)],
            'tipos' => ['required', 'array', 'min:1', 'max:12'],
            'tipos.*.chave' => ['nullable', 'string', 'max:40'],
            'tipos.*.nome' => ['required', 'string', 'max:30', 'distinct:ignore_case'],
            'tipos.*.cor' => ['required', Rule::in(array_keys(Configuracoes::CORES_TIPO_JOGO))],
            'tipos.*.icone' => ['required', Rule::in(array_keys(Configuracoes::ICONES_TIPO_JOGO))],
        ], [
            'duracao_maxima.gte' => 'A duração máxima não pode ser menor que a padrão.',
            'max_semanas.min' => 'Use pelo menos 1 semana.',
            'max_semanas.max' => 'Use no máximo :max semanas (1 ano).',
            'tipos.required' => 'Deixe ao menos um tipo de jogo.',
            'tipos.min' => 'Deixe ao menos um tipo de jogo.',
            'tipos.max' => 'Use no máximo :max tipos de jogo.',
            'tipos.*.nome.required' => 'Dê um nome ao tipo de jogo.',
            'tipos.*.nome.max' => 'Nome muito longo (máx. :max).',
            'tipos.*.nome.distinct' => 'Já existe um tipo com esse nome.',
        ]);

        $atuais = collect($this->configuracoes->get('alugueis.tipos_jogo'))->pluck('chave')->all();

        // tipos removidos que já foram usados em reservas não podem sumir
        $validador->after(function (Validator $v) use ($tipos, $atuais) {
            $removidos = array_diff($atuais, $tipos->pluck('chave')->all());
            $emUso = Aluguel::whereIn('tipo_jogo', $removidos)->distinct()->pluck('tipo_jogo');

            foreach ($emUso as $chave) {
                $nome = Configuracoes::tipoJogo($chave)['nome'];
                $v->errors()->add('tipos', "“{$nome}” já foi usado em reservas e não pode ser removido. Renomeie, se precisar.");
            }
        });

        $dados = $validador->validate();

        // chave: a que o tipo já tinha (renomear mantém as reservas ligadas)
        // ou uma nova, a partir do nome
        $usadas = [];
        $tiposFinais = collect($dados['tipos'])->map(function (array $tipo) use ($atuais, &$usadas) {
            $chave = in_array($tipo['chave'], $atuais, true) && ! in_array($tipo['chave'], $usadas, true)
                ? $tipo['chave']
                : $this->chaveUnica(Str::slug($tipo['nome']) ?: 'tipo', [...$atuais, ...$usadas]);
            $usadas[] = $chave;

            return ['chave' => $chave, 'nome' => $tipo['nome'], 'cor' => $tipo['cor'], 'icone' => $tipo['icone']];
        })->all();

        $this->configuracoes->salvar([
            'alugueis.duracao_padrao' => (int) $dados['duracao_padrao'],
            'alugueis.duracao_maxima' => (int) $dados['duracao_maxima'],
            'alugueis.max_semanas' => (int) $dados['max_semanas'],
            'alugueis.intervalo' => (int) $dados['intervalo'],
            'alugueis.tipos_jogo' => $tiposFinais,
        ]);

        Atividade::registrar('configuracoes', 'Configurações de mesas e aluguéis alteradas');

        return $this->salvo('Configurações de mesas e aluguéis salvas.', ['tipos' => $tiposFinais]);
    }

    // ---------------------------------------------------------------------
    // Estoque (alerta, estados e idiomas de carta)
    // ---------------------------------------------------------------------

    public function salvarEstoque(Request $request): JsonResponse
    {
        $request->merge([
            'estados' => $this->limparLista($request->input('estados')),
            'idiomas' => $this->limparLista($request->input('idiomas')),
        ]);

        $dados = $request->validate([
            'alerta_ativo' => ['required', 'boolean'],
            'alerta_minimo' => ['required', 'integer', 'min:0', 'max:9999'],
            'estados' => ['required', 'array', 'min:1', 'max:20'],
            'estados.*' => ['string', 'max:30'],
            'idiomas' => ['required', 'array', 'min:1', 'max:20'],
            'idiomas.*' => ['string', 'max:30'],
        ], [
            'alerta_minimo.required' => 'Informe a quantidade mínima.',
            'alerta_minimo.integer' => 'Use um número inteiro.',
            'alerta_minimo.min' => 'A quantidade não pode ser negativa.',
            'estados.required' => 'Deixe ao menos um estado de carta.',
            'estados.min' => 'Deixe ao menos um estado de carta.',
            'estados.max' => 'Use no máximo :max estados.',
            'estados.*.max' => 'Cada estado pode ter no máximo :max caracteres.',
            'idiomas.required' => 'Deixe ao menos um idioma.',
            'idiomas.min' => 'Deixe ao menos um idioma.',
            'idiomas.max' => 'Use no máximo :max idiomas.',
            'idiomas.*.max' => 'Cada idioma pode ter no máximo :max caracteres.',
        ]);

        $this->configuracoes->salvar([
            'estoque.alerta_ativo' => (bool) $dados['alerta_ativo'],
            'estoque.alerta_minimo' => (int) $dados['alerta_minimo'],
            'estoque.estados_carta' => $dados['estados'],
            'estoque.idiomas_carta' => $dados['idiomas'],
        ]);

        Atividade::registrar('configuracoes', 'Configurações de estoque alteradas');

        return $this->salvo('Configurações de estoque salvas.');
    }

    public function salvarCategoria(Request $request, ?Categoria $categoria = null): JsonResponse
    {
        $request->merge(['nome' => trim((string) $request->input('nome'))]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nome')->ignore($categoria?->id)],
        ], [
            'nome.required' => 'Dê um nome à categoria.',
            'nome.unique' => 'Já existe uma categoria com esse nome.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
        ]);

        if ($categoria) {
            $antigo = $categoria->nome;
            $categoria->update($dados);
            Atividade::registrar('estoque', "Categoria renomeada: {$antigo} → {$categoria->nome}");
            $mensagem = 'Categoria renomeada.';
        } else {
            $categoria = Categoria::create($dados);
            Atividade::registrar('estoque', "Categoria criada: {$categoria->nome}");
            $mensagem = "Categoria “{$categoria->nome}” criada.";
        }

        return response()->json(['success' => true, 'message' => $mensagem, 'itens' => $this->listaCategorias()]);
    }

    public function excluirCategoria(Categoria $categoria): JsonResponse
    {
        $produtos = $categoria->produtos()->count();

        if ($produtos > 0) {
            return response()->json([
                'message' => "“{$categoria->nome}” tem {$produtos} ".($produtos === 1 ? 'produto' : 'produtos')
                    .'. Mude a categoria deles no Estoque antes de excluir.',
            ], 422);
        }

        $categoria->delete();
        Atividade::registrar('estoque', "Categoria excluída: {$categoria->nome}");

        return response()->json(['success' => true, 'message' => 'Categoria excluída.', 'itens' => $this->listaCategorias()]);
    }

    public function salvarJogo(Request $request, ?JogoCarta $jogoCarta = null): JsonResponse
    {
        $request->merge(['nome' => trim((string) $request->input('nome'))]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:60', Rule::unique('jogos_carta', 'nome')->ignore($jogoCarta?->id)],
        ], [
            'nome.required' => 'Dê um nome ao jogo.',
            'nome.unique' => 'Esse jogo já está cadastrado.',
            'nome.max' => 'O nome pode ter no máximo :max caracteres.',
        ]);

        if ($jogoCarta) {
            // só o nome muda: a chave fica, para as cartas continuarem ligadas
            $antigo = $jogoCarta->nome;
            $jogoCarta->update(['nome' => $dados['nome']]);
            Atividade::registrar('estoque', "Jogo de carta renomeado: {$antigo} → {$jogoCarta->nome}");
            $mensagem = 'Jogo renomeado.';
        } else {
            $chave = $this->chaveUnica(Str::slug($dados['nome'], '') ?: 'jogo', JogoCarta::pluck('chave')->all());
            $jogoCarta = JogoCarta::create(['chave' => $chave, 'nome' => $dados['nome']]);
            Atividade::registrar('estoque', "Jogo de carta cadastrado: {$jogoCarta->nome}");
            $mensagem = "“{$jogoCarta->nome}” cadastrado. Ele já aparece no estoque de cartas.";
        }

        return response()->json(['success' => true, 'message' => $mensagem, 'itens' => $this->listaJogos()]);
    }

    public function excluirJogo(JogoCarta $jogoCarta): JsonResponse
    {
        $cartas = $jogoCarta->cartas()->count();

        if ($cartas > 0) {
            return response()->json([
                'message' => "“{$jogoCarta->nome}” tem {$cartas} ".($cartas === 1 ? 'carta' : 'cartas').' no estoque e não pode ser excluído.',
            ], 422);
        }

        if (JogoCarta::count() === 1) {
            return response()->json(['message' => 'Deixe ao menos um jogo de carta cadastrado.'], 422);
        }

        $jogoCarta->delete();
        Atividade::registrar('estoque', "Jogo de carta excluído: {$jogoCarta->nome}");

        return response()->json(['success' => true, 'message' => 'Jogo excluído.', 'itens' => $this->listaJogos()]);
    }

    // ---------------------------------------------------------------------
    // Clientes e créditos
    // ---------------------------------------------------------------------

    public function salvarClientes(Request $request): JsonResponse
    {
        $request->merge([
            'valores_rapidos' => $this->limparLista($request->input('valores_rapidos')),
            'motivos' => $this->limparLista($request->input('motivos')),
            'whatsapp_ddi' => preg_replace('/\D/', '', (string) $request->input('whatsapp_ddi')),
        ]);

        $dados = $request->validate([
            'valores_rapidos' => ['required', 'array', 'min:1', 'max:6'],
            'valores_rapidos.*' => ['integer', 'min:1', 'max:10000'],
            'motivos' => ['nullable', 'array', 'max:12'],
            'motivos.*' => ['string', 'max:60'],
            'whatsapp_ddi' => ['required', 'digits_between:1,4'],
        ], [
            'valores_rapidos.required' => 'Deixe ao menos um valor rápido.',
            'valores_rapidos.min' => 'Deixe ao menos um valor rápido.',
            'valores_rapidos.max' => 'Use no máximo :max valores rápidos.',
            'valores_rapidos.*.integer' => 'Os valores rápidos precisam ser números inteiros (em reais).',
            'valores_rapidos.*.min' => 'Os valores rápidos precisam ser maiores que zero.',
            'valores_rapidos.*.max' => 'Valor rápido muito alto (máx. R$ 10.000).',
            'motivos.max' => 'Use no máximo :max motivos.',
            'motivos.*.max' => 'Cada motivo pode ter no máximo :max caracteres.',
            'whatsapp_ddi.required' => 'Informe o DDI (55 para o Brasil).',
            'whatsapp_ddi.digits_between' => 'O DDI tem de 1 a 4 números.',
        ]);

        $valores = array_map('intval', $dados['valores_rapidos']);
        sort($valores);

        $this->configuracoes->salvar([
            'clientes.valores_rapidos' => array_values(array_unique($valores)),
            'clientes.motivos_credito' => array_values($dados['motivos'] ?? []),
            'clientes.whatsapp_ddi' => $dados['whatsapp_ddi'],
        ]);

        Atividade::registrar('configuracoes', 'Configurações de clientes e créditos alteradas');

        return $this->salvo('Configurações de clientes salvas.');
    }

    // ---------------------------------------------------------------------
    // Dados e atividades
    // ---------------------------------------------------------------------

    /**
     * Mais atividades (botão "Ver mais" e filtro por área).
     */
    public function atividades(Request $request): JsonResponse
    {
        $area = array_key_exists((string) $request->query('area'), Atividade::AREAS) ? $request->query('area') : null;
        $antes = $request->integer('antes') ?: null;

        return response()->json($this->paginaAtividades($area, $antes));
    }

    public function exportarVendas(Request $request): Response
    {
        [$de, $ate, $rotulo] = $this->periodoExportacao($request);

        $vendas = Venda::with('itens', 'cliente')
            ->when($de, fn ($q) => $q->where('created_at', '>=', $de))
            ->when($ate, fn ($q) => $q->where('created_at', '<=', $ate))
            ->orderBy('created_at')
            ->get();

        $linhas = [['Venda', 'Data', 'Hora', 'Cliente', 'Itens', 'Total (R$)', 'Pago com créditos (R$)', 'Pago na forma (R$)', 'Forma de pagamento', 'Status', 'Motivo do cancelamento', 'Observações']];

        foreach ($vendas as $venda) {
            $linhas[] = [
                $venda->id,
                $venda->created_at->format('d/m/Y'),
                $venda->created_at->format('H:i'),
                $venda->cliente?->nome ?? '',
                $venda->itens->map(fn (VendaItem $i) => "{$i->quantidade}x {$i->descricao}")->implode(' | '),
                $this->numero($venda->total),
                $this->numero($venda->valor_creditos),
                $this->numero($venda->valor_restante),
                Venda::FORMAS_PAGAMENTO[$venda->forma_pagamento]['rotulo'] ?? '',
                $venda->cancelada ? 'Cancelada' : 'Concluída',
                $venda->motivo_cancelamento ?? '',
                $venda->observacoes ?? '',
            ];
        }

        Atividade::registrar('configuracoes', "Vendas exportadas ({$rotulo}, {$vendas->count()} vendas)");

        return $this->csv($linhas, "vendas-{$rotulo}");
    }

    public function exportarEstoque(): Response
    {
        $jogos = JogoCarta::opcoes();
        $linhas = [['Tipo', 'Nome', 'Categoria / Jogo', 'Detalhes', 'Quantidade', 'Preço (R$)', 'Valor em estoque (R$)']];

        foreach (Produto::with('categoria')->orderBy('nome')->get() as $produto) {
            $linhas[] = [
                'Produto', $produto->nome, $produto->categoria?->nome ?? '', $produto->descricao ?? '',
                $produto->quantidade, $this->numero($produto->preco), $this->numero($produto->preco * $produto->quantidade),
            ];
        }

        foreach (Carta::orderBy('jogo')->orderBy('nome')->get() as $carta) {
            $linhas[] = [
                'Carta', $carta->nome, $jogos[$carta->jogo] ?? $carta->jogo,
                implode(' · ', array_filter([$carta->colecao, $carta->raridade, $carta->estado, $carta->idioma, $carta->foil ? 'Foil' : null])),
                $carta->quantidade, $this->numero($carta->preco), $this->numero($carta->preco * $carta->quantidade),
            ];
        }

        Atividade::registrar('configuracoes', 'Estoque exportado');

        return $this->csv($linhas, 'estoque');
    }

    // ---------------------------------------------------------------------
    // Auxiliares
    // ---------------------------------------------------------------------

    private function salvo(string $mensagem, array $extra = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $mensagem] + $extra);
    }

    /**
     * Lista de texto vinda de um editor de "chips": tira espaços, vazios e
     * repetidos (sem diferenciar maiúsculas).
     *
     * @return array<int, string>
     */
    private function limparLista(mixed $lista): array
    {
        $vistos = [];

        return collect((array) $lista)
            ->map(fn ($item) => trim((string) $item))
            ->filter(function (string $item) use (&$vistos) {
                $chave = mb_strtolower($item);
                if ($item === '' || isset($vistos[$chave])) {
                    return false;
                }

                return $vistos[$chave] = true;
            })
            ->values()
            ->all();
    }

    private function chaveUnica(string $base, array $existentes): string
    {
        $chave = $base;
        for ($i = 2; in_array($chave, $existentes, true); $i++) {
            $chave = "{$base}{$i}";
        }

        return $chave;
    }

    private function listaCategorias(): array
    {
        return Categoria::withCount('produtos')->orderBy('nome')->get()
            ->map(fn (Categoria $c) => ['id' => $c->id, 'nome' => $c->nome, 'quantidade' => $c->produtos_count])
            ->all();
    }

    private function listaJogos(): array
    {
        return JogoCarta::withCount('cartas')->orderBy('id')->get()
            ->map(fn (JogoCarta $j) => ['id' => $j->id, 'nome' => $j->nome, 'quantidade' => $j->cartas_count])
            ->all();
    }

    /**
     * @return array{itens: array<int, array<string, mixed>>, tem_mais: bool}
     */
    private function paginaAtividades(?string $area, ?int $antes): array
    {
        $itens = Atividade::query()
            ->when($area, fn ($q) => $q->where('area', $area))
            ->when($antes, fn ($q) => $q->where('id', '<', $antes))
            ->latest('id')
            ->take(self::ATIVIDADES_POR_PAGINA + 1)
            ->get();

        return [
            'itens' => $itens->take(self::ATIVIDADES_POR_PAGINA)->map(fn (Atividade $a) => $a->dadosJson())->values()->all(),
            'tem_mais' => $itens->count() > self::ATIVIDADES_POR_PAGINA,
        ];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function periodoExportacao(Request $request): array
    {
        return match ($request->query('periodo')) {
            'mes_passado' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth(), today()->subMonthNoOverflow()->format('Y-m')],
            '30dias' => [today()->subDays(29), now(), 'ultimos-30-dias'],
            'tudo' => [null, null, 'todas'],
            'intervalo' => $this->intervaloExportacao($request),
            default => [today()->startOfMonth(), now(), today()->format('Y-m')],
        };
    }

    private function intervaloExportacao(Request $request): array
    {
        $dados = $request->validate([
            'de' => ['required', 'date_format:Y-m-d'],
            'ate' => ['required', 'date_format:Y-m-d', 'after_or_equal:de'],
        ], [
            'de.required' => 'Informe a data inicial.',
            'ate.required' => 'Informe a data final.',
            'ate.after_or_equal' => 'A data final precisa ser depois da inicial.',
        ]);

        $de = Carbon::createFromFormat('Y-m-d', $dados['de'])->startOfDay();
        $ate = Carbon::createFromFormat('Y-m-d', $dados['ate'])->endOfDay();

        return [$de, $ate, "{$dados['de']}-a-{$dados['ate']}"];
    }

    private function numero(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }

    /**
     * CSV separado por ";" com BOM UTF-8 (o Excel abre com acentos certos) —
     * mesmo formato da exportação de clientes.
     */
    private function csv(array $linhas, string $nome): Response
    {
        $saida = fopen('php://temp', 'r+');
        foreach ($linhas as $linha) {
            fputcsv($saida, $linha, ';');
        }
        rewind($saida);
        $csv = stream_get_contents($saida);
        fclose($saida);

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nome}.csv\"",
        ]);
    }
}
