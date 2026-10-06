<?php

namespace Database\Seeders;

use App\Models\Carta;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Database\Seeder;

/**
 * Produtos e cartas de exemplo — os mesmos que ficavam fixos em
 * routes/web.php antes do estoque ter banco de dados.
 */
class EstoqueSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = fn (string $nome) => Categoria::firstOrCreate(['nome' => $nome])->id;

        $produtos = [
            ['nome' => 'Booster Pokémon', 'preco' => 12.00, 'quantidade' => 20, 'descricao' => 'booster pokemon evolving skies', 'categoria' => 'Cartas'],
            ['nome' => 'Chaveiro Gengar', 'preco' => 10.00, 'quantidade' => 5, 'descricao' => 'Chaveiro gengar 10cm', 'categoria' => 'Acessórios'],
            ['nome' => 'Coca-Cola', 'preco' => 8.00, 'quantidade' => 24, 'descricao' => 'lata de coca-cola 350ml.', 'categoria' => 'Bebida'],
        ];

        foreach ($produtos as $produto) {
            $produto['categoria_id'] = $categoria($produto['categoria']);
            unset($produto['categoria']);
            Produto::create($produto);
        }

        $cartas = [
            ['pokemon', 'Trevenant 096/217', 'teste', 'Rara Comum', 'Semi-Novo', 'Português', true, 4, 0.90],
            ['pokemon', "Ethan's Typhlosion 190/182", 'Destined Rivals', 'Rara Secreta', 'Novo', 'Inglês', false, 1, 120.50],
            ['pokemon', 'Shiftry 163/162', 'teste', 'Rara Secreta', 'Semi-Novo', 'Japonês', false, 2, 35.50],
            ['magic', 'Llanowar Elves 234/280', 'Dominaria', 'Comum', 'Semi-Novo', 'Inglês', false, 12, 1.50],
            ['magic', 'Sheoldred, the Apocalypse 107/281', 'Dominaria United', 'Mítica', 'Novo', 'Inglês', true, 1, 389.90],
            ['magic', 'Counterspell 045/281', 'Modern Horizons 2', 'Incomum', 'Usado', 'Português', false, 5, 4.00],
            ['magic', 'Lightning Bolt 146/303', 'Magic 2011', 'Comum', 'Novo', 'Português', false, 9, 6.50],
            ['onepiece', 'Monkey.D.Luffy OP01-003', 'Romance Dawn', 'Líder', 'Novo', 'Japonês', false, 4, 18.00],
            ['onepiece', 'Roronoa Zoro OP01-025', 'Romance Dawn', 'Super Rara', 'Semi-Novo', 'Inglês', true, 2, 32.50],
            ['onepiece', 'Shanks OP09-004', 'Emperors in the New World', 'Secreta Rara', 'Novo', 'Japonês', true, 1, 540.00],
        ];

        foreach ($cartas as [$jogo, $nome, $colecao, $raridade, $estado, $idioma, $foil, $quantidade, $preco]) {
            Carta::create(compact('jogo', 'nome', 'colecao', 'raridade', 'estado', 'idioma', 'foil', 'quantidade', 'preco'));
        }
    }
}
