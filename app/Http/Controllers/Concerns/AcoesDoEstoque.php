<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\AjustarEstoqueRequest;
use App\Models\Atividade;
use App\Models\Carta;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Ações iguais para produtos e cartas: arquivar ("apagar"), restaurar,
 * excluir de vez e o ajuste rápido de quantidade. Cada controller diz como
 * o item se chama em rotulo() — ex.: ['nome' => 'produto', 'o' => 'o',
 * 'rota' => 'estoque.produtos'].
 */
trait AcoesDoEstoque
{
    /**
     * @return array{nome: string, o: string, rota: string}
     */
    abstract protected function rotulo(): array;

    /**
     * Endereço da lista para onde voltar quando a página do item deixa de
     * existir (exclusão definitiva).
     */
    abstract protected function urlDaLista(Produto|Carta $item): string;

    /**
     * Arquiva o item: sai das listas e das vendas, mas continua com o
     * histórico e pode ser restaurado (o aviso traz "Desfazer").
     */
    protected function arquivarItem(Produto|Carta $item): RedirectResponse
    {
        ['nome' => $nome, 'o' => $o, 'rota' => $rota] = $this->rotulo();

        $item->delete();

        Atividade::registrar('estoque', ucfirst($nome)." arquivad{$o}: {$item->nome}");

        return back()->with('aviso', [
            'texto' => "\"{$item->nome}\" foi arquivad{$o}.",
            'tipo' => 'sucesso',
            'desfazer' => ['url' => route("{$rota}.restaurar", $item), 'metodo' => 'POST'],
        ]);
    }

    protected function restaurarItem(Produto|Carta $item): RedirectResponse
    {
        ['nome' => $nome, 'o' => $o] = $this->rotulo();

        $item->restore();

        Atividade::registrar('estoque', ucfirst($nome)." restaurad{$o}: {$item->nome}");

        return back()->with('aviso', ['texto' => "\"{$item->nome}\" voltou para o estoque.", 'tipo' => 'sucesso']);
    }

    /**
     * Exclui de vez um item já arquivado (com o histórico e a imagem). As
     * vendas antigas guardam o nome e o preço, então continuam corretas.
     */
    protected function excluirItem(Produto|Carta $item): RedirectResponse
    {
        ['nome' => $nome, 'o' => $o, 'rota' => $rota] = $this->rotulo();

        abort_unless($item->trashed(), 404);

        $veioDosDetalhes = url()->previous() === route("{$rota}.show", $item);

        $item->apagarArquivoDeImagem();
        $item->forceDelete();

        Atividade::registrar('estoque', ucfirst($nome)." excluíd{$o} definitivamente: {$item->nome}");

        $resposta = $veioDosDetalhes ? redirect($this->urlDaLista($item)) : back();

        return $resposta->with('aviso', ['texto' => "\"{$item->nome}\" foi excluíd{$o} definitivamente.", 'tipo' => 'sucesso']);
    }

    /**
     * Ajuste rápido de quantidade (página de detalhes): entrada de
     * mercadoria, perda, contagem... sempre com um motivo no histórico.
     */
    protected function ajustarItem(AjustarEstoqueRequest $request, Produto|Carta $item): RedirectResponse
    {
        $diferenca = $request->diferenca();
        $motivo = $request->motivo();

        DB::transaction(function () use ($item, $diferenca, $motivo) {
            $atual = $item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $atual->movimentarQuantidade($diferenca, $motivo);
            $item->setRawAttributes($atual->getAttributes(), true);
        });

        Atividade::registrar('estoque', "Ajuste de estoque: {$item->nome} ("
            .($diferenca > 0 ? '+' : '−').abs($diferenca).", {$motivo})");

        return back()->with('aviso', [
            'texto' => ($diferenca > 0 ? 'Entrada' : 'Saída')." de ".abs($diferenca)." registrada. Agora há {$item->quantidade} em estoque.",
            'tipo' => 'sucesso',
        ]);
    }
}
