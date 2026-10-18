<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Produto;
use App\Services\Configuracoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Limite de "estoque baixo" por produto (definido no cadastro); sem limite
 * próprio vale o padrão de Configurações > Estoque.
 */
class EstoqueBaixoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(Configuracoes::class)->salvar(['estoque.alerta_ativo' => true, 'estoque.alerta_minimo' => 5]);
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'nome' => 'Booster Pokémon',
            'categoria_id' => Categoria::firstOrCreate(['nome' => 'Cartas'])->id,
            'quantidade' => 8,
            'preco' => '12',
        ];
    }

    public function test_formulario_mostra_o_campo_com_o_padrao(): void
    {
        $this->get('/estoque')->assertOk()
            ->assertSee('name="estoque_minimo"', false)
            ->assertSee('padrão: 5');
    }

    public function test_limite_do_cadastro_dispara_o_alerta(): void
    {
        // 8 unidades: acima do padrão (5), mas abaixo do limite do produto (10)
        $this->post('/estoque/produtos', $this->dados(['estoque_minimo' => '10']))->assertSessionHasNoErrors();

        $produto = Produto::firstOrFail();
        $this->assertSame(10, $produto->estoque_minimo);
        $this->assertTrue($produto->estoque_baixo);
        $this->assertSame(10, $produto->limite_estoque_baixo);

        $this->get('/estoque')->assertSee('com estoque baixo')->assertSee('Booster Pokémon (8)');
        $this->get('/inicio')->assertSee('1 produto está com estoque baixo');
    }

    public function test_sem_limite_proprio_usa_o_padrao(): void
    {
        $this->post('/estoque/produtos', $this->dados(['estoque_minimo' => '']))->assertSessionHasNoErrors();

        $produto = Produto::firstOrFail();
        $this->assertNull($produto->estoque_minimo);
        $this->assertFalse($produto->estoque_baixo); // 8 > 5
        $this->get('/estoque')->assertDontSee('com estoque baixo');

        // a quantidade cai até o padrão: entra no alerta
        $produto->update(['quantidade' => 5]);
        $this->assertCount(1, Produto::estoqueBaixo()->get());
    }

    public function test_limite_proprio_menor_que_o_padrao(): void
    {
        // limite 2: com 4 unidades não alerta, mesmo abaixo do padrão (5)
        $this->post('/estoque/produtos', $this->dados(['quantidade' => 4, 'estoque_minimo' => '2']));
        $this->assertCount(0, Produto::estoqueBaixo()->get());

        Produto::firstOrFail()->update(['quantidade' => 2]);
        $this->assertCount(1, Produto::estoqueBaixo()->get());
    }

    public function test_editar_muda_e_limpa_o_limite(): void
    {
        $this->post('/estoque/produtos', $this->dados(['estoque_minimo' => '10']));
        $produto = Produto::firstOrFail();

        $this->put("/estoque/produtos/{$produto->id}", $this->dados(['estoque_minimo' => '3']))->assertSessionHasNoErrors();
        $this->assertSame(3, $produto->fresh()->estoque_minimo);
        $this->assertFalse($produto->fresh()->estoque_baixo);

        // vazio volta para o padrão
        $this->put("/estoque/produtos/{$produto->id}", $this->dados(['estoque_minimo' => '']))->assertSessionHasNoErrors();
        $this->assertNull($produto->fresh()->estoque_minimo);
        $this->assertSame(5, $produto->fresh()->limite_estoque_baixo);
    }

    public function test_alerta_desligado_ignora_o_limite_do_produto(): void
    {
        $this->post('/estoque/produtos', $this->dados(['estoque_minimo' => '10']));
        app(Configuracoes::class)->salvar(['estoque.alerta_ativo' => false]);

        $this->assertCount(0, Produto::estoqueBaixo()->get());
        $this->assertFalse(Produto::firstOrFail()->estoque_baixo);
        $this->get('/estoque')->assertDontSee('com estoque baixo');
    }

    public function test_limite_invalido_e_recusado(): void
    {
        $this->post('/estoque/produtos', $this->dados(['estoque_minimo' => '-1']))
            ->assertSessionHasErrors('estoque_minimo', null, 'produto');
        $this->assertSame(0, Produto::count());
    }
}
