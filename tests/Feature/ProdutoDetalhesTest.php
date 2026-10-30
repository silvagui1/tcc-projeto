<?php

namespace Tests\Feature;

use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Atualização de design do estoque: página de detalhes, ajuste rápido de
 * quantidade, arquivar / restaurar / excluir de vez, histórico com link
 * para a venda, filtros da grade e a busca geral.
 */
class ProdutoDetalhesTest extends TestCase
{
    use RefreshDatabase;

    private function cadastrar(array $extra = []): Produto
    {
        $this->post('/estoque/produtos', $extra + [
            'nome' => 'Sleeve Dragon Shield',
            'categoria_id' => Categoria::firstOrCreate(['nome' => 'Acessórios'])->id,
            'quantidade' => 7,
            'preco' => '45',
        ])->assertSessionHasNoErrors();

        return Produto::latest('id')->firstOrFail();
    }

    private function carta(): Carta
    {
        return Carta::create(['nome' => 'Lightning Bolt', 'jogo' => 'magic', 'estado' => 'Novo', 'idioma' => 'Inglês', 'quantidade' => 3, 'preco' => 6.5]);
    }

    public function test_card_da_lista_leva_aos_detalhes(): void
    {
        $produto = $this->cadastrar();

        $this->get('/estoque')->assertOk()
            ->assertSee(route('estoque.produtos.show', $produto), false);
    }

    public function test_detalhes_mostram_dados_ajuste_e_historico(): void
    {
        $produto = $this->cadastrar();

        $this->get(route('estoque.produtos.show', $produto))->assertOk()
            ->assertSee('Sleeve Dragon Shield')
            ->assertSee('R$ 45,00')
            ->assertSee('R$ 315,00') // valor total em estoque (7 × 45)
            ->assertSee('Ajustar quantidade')
            ->assertSee('Histórico de movimentações')
            ->assertSee('Cadastro do produto')
            ->assertSee('0 → 7');
    }

    public function test_ajuste_rapido_de_entrada_e_saida(): void
    {
        $produto = $this->cadastrar();

        $this->post(route('estoque.produtos.ajustar', $produto), ['tipo' => 'entrada', 'quantidade' => 5, 'motivo' => 'Chegada do fornecedor'])
            ->assertSessionHasNoErrors();
        $this->assertSame(12, $produto->fresh()->quantidade);

        // sem motivo: usa um padrão
        $this->post(route('estoque.produtos.ajustar', $produto), ['tipo' => 'saida', 'quantidade' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(10, $produto->fresh()->quantidade);

        $this->assertSame(
            ['Saída manual', 'Chegada do fornecedor', 'Cadastro do produto'],
            $produto->movimentacoes()->pluck('descricao')->all(),
        );
    }

    public function test_saida_maior_que_o_estoque_e_recusada(): void
    {
        $produto = $this->cadastrar();

        $this->post(route('estoque.produtos.ajustar', $produto), ['tipo' => 'saida', 'quantidade' => 50])
            ->assertSessionHasErrorsIn('ajuste', ['quantidade']);
        $this->assertSame(7, $produto->fresh()->quantidade);
    }

    public function test_ajuste_de_carta(): void
    {
        $carta = $this->carta();

        $this->post(route('estoque.cartas.ajustar', $carta), ['tipo' => 'entrada', 'quantidade' => 4])
            ->assertSessionHasNoErrors();

        $this->assertSame(7, $carta->fresh()->quantidade);
        $this->get(route('estoque.cartas.show', $carta))->assertOk()
            ->assertSee('Entrada de mercadoria')
            ->assertSee('3 → 7');
    }

    public function test_apagar_arquiva_e_da_para_desfazer(): void
    {
        $produto = $this->cadastrar();

        $this->delete(route('estoque.produtos.destroy', $produto))
            ->assertSessionHas('aviso.desfazer.url', route('estoque.produtos.restaurar', $produto));

        // sai da lista, da busca de vendas e dos números; continua nos detalhes
        $this->assertSoftDeleted($produto);
        $this->get('/estoque')->assertDontSee(route('estoque.produtos.show', $produto), false);
        $this->getJson('/vendas/catalogo?tipo=produto&q=sleeve')->assertOk()->assertJsonCount(0);
        $this->get('/estoque?situacao=arquivados')->assertSee(route('estoque.produtos.show', $produto), false);
        $this->get(route('estoque.produtos.show', $produto))->assertOk()->assertSee('Produto arquivado em');

        $this->post(route('estoque.produtos.restaurar', $produto));
        $this->assertNotSoftDeleted($produto);
    }

    public function test_excluir_de_vez_so_depois_de_arquivar(): void
    {
        $produto = $this->cadastrar();

        // ativo: a rota de exclusão definitiva não vale
        $this->delete(route('estoque.produtos.excluir', $produto))->assertNotFound();

        $produto->delete();

        $this->from(route('estoque.produtos.show', $produto))
            ->delete(route('estoque.produtos.excluir', $produto))
            ->assertRedirect(route('estoque.index', ['situacao' => 'arquivados']));

        $this->assertDatabaseCount('produtos', 0);
        $this->assertDatabaseCount('produto_movimentacoes', 0);
    }

    public function test_carta_arquivada_aparece_no_filtro(): void
    {
        $carta = $this->carta();
        $this->delete(route('estoque.cartas.destroy', $carta));

        $this->get('/estoque/cartas?jogo=magic')->assertSee('0 cartas de');
        $this->get('/estoque/cartas?jogo=magic&arquivadas=1')->assertSee('1 carta arquivada de');
    }

    public function test_cancelar_venda_devolve_estoque_de_produto_arquivado(): void
    {
        $produto = $this->cadastrar();

        $this->postJson('/vendas', [
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 2]],
            'forma_pagamento' => 'pix',
        ])->assertCreated();
        $venda = Venda::firstOrFail();

        $produto->delete();
        $this->postJson("/vendas/{$venda->id}/cancelar", ['motivo' => 'Desistência'])->assertOk();

        $this->assertSame(7, Produto::withTrashed()->find($produto->id)->quantidade);
    }

    public function test_historico_leva_para_a_venda(): void
    {
        $produto = $this->cadastrar();

        $this->postJson('/vendas', [
            'itens' => [['tipo' => 'produto', 'id' => $produto->id, 'quantidade' => 1]],
            'forma_pagamento' => 'pix',
        ])->assertCreated();
        $venda = Venda::firstOrFail();

        $this->assertSame($venda->id, $produto->movimentacoes()->first()->venda_id);
        $this->get(route('estoque.produtos.show', $produto))
            ->assertSee(route('vendas.index', ['venda' => $venda->id]), false);
    }

    public function test_filtros_e_ordenacao_da_grade(): void
    {
        $this->cadastrar(['nome' => 'Dado', 'quantidade' => 30]);
        $this->cadastrar(['nome' => 'Playmat', 'quantidade' => 2]);

        $resposta = $this->get('/estoque?ordenar=menor_qtd')->assertOk();
        $this->assertSame(['Playmat', 'Dado'], collect($resposta->viewData('produtos')->items())->pluck('nome')->all());

        // abas antigas continuam funcionando
        $this->get('/estoque?tab=cartas')->assertRedirect(route('estoque.cartas'));
    }

    public function test_busca_geral(): void
    {
        $produto = $this->cadastrar();
        $cliente = Cliente::factory()->create(['nome' => 'Sleeveira da Silva']);

        $this->getJson('/busca?q=sleeve')->assertOk()
            ->assertJsonPath('grupos.0.titulo', 'Clientes')
            ->assertJsonPath('grupos.0.itens.0.url', route('clientes.index', ['abrir' => $cliente->id]))
            ->assertJsonPath('grupos.1.itens.0.url', route('estoque.produtos.show', $produto));

        // menos de 2 letras: nada
        $this->getJson('/busca?q=s')->assertOk()->assertJsonCount(0, 'grupos');
    }
}
