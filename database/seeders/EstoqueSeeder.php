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
        $categoria = fn (string $nome) => Categoria::firstOrCreate(['nome' => $nome], ['comercializavel' => $nome !== 'Não comercializável'])->id;

        $produtos = [
            ['nome' => 'Booster Pokémon', 'preco' => 12.00, 'quantidade' => 20, 'descricao' => 'booster pokemon evolving skies', 'categoria' => 'Cartas'],
            ['nome' => 'Chaveiro Gengar', 'preco' => 10.00, 'quantidade' => 5, 'descricao' => 'Chaveiro gengar 10cm', 'categoria' => 'Acessórios'],
            ['nome' => 'Coca-Cola', 'preco' => 8.00, 'quantidade' => 24, 'descricao' => 'lata de coca-cola 350ml.', 'categoria' => 'Bebida'],
            // não comercializáveis: brindes de evento, um por jogo (só podem ser dados)
            ['nome' => 'Carta Promo Pokémon (brinde)', 'preco' => 0, 'quantidade' => 10, 'descricao' => 'Carta promocional dada em eventos e premiações de Pokémon', 'categoria' => 'Não comercializável'],
            ['nome' => 'Token Magic (brinde)', 'preco' => 0, 'quantidade' => 10, 'descricao' => 'Token promocional dado em eventos e premiações de Magic', 'categoria' => 'Não comercializável'],
            ['nome' => 'Carta DON!! One Piece (brinde)', 'preco' => 0, 'quantidade' => 10, 'descricao' => 'Carta DON!! promocional dada em eventos e premiações de One Piece', 'categoria' => 'Não comercializável'],
        ];

        foreach ($produtos as $produto) {
            $produto['categoria_id'] = $categoria($produto['categoria']);
            unset($produto['categoria']);
            Produto::create($produto);
        }

        $cartas = [
            ['pokemon', 'Trevenant 096/217', 'teste', 'Rara (R) ★', 'Semi-Novo', 'Português', true, 4, 0.90],
            ['pokemon', "Ethan's Typhlosion 190/182", 'Destined Rivals', 'Ilustração Rara (AR) ★ dourada', 'Novo', 'Inglês', false, 1, 120.50],
            ['pokemon', 'Shiftry 163/162', 'Temporal Forces', 'Ilustração Rara (AR) ★ dourada', 'Semi-Novo', 'Japonês', false, 2, 35.50],
            ['magic', 'Llanowar Elves 234/280', 'Dominaria', 'Comum (C)', 'Semi-Novo', 'Inglês', false, 12, 1.50],
            ['magic', 'Sheoldred, the Apocalypse 107/281', 'Dominaria United', 'Mítica Rara (M)', 'Novo', 'Inglês', true, 1, 389.90],
            ['magic', 'Counterspell 045/281', 'Modern Horizons 2', 'Incomum (U)', 'Usado', 'Português', false, 5, 4.00],
            ['magic', 'Lightning Bolt 146/303', 'Magic 2011', 'Comum (C)', 'Novo', 'Português', false, 9, 6.50],
            ['onepiece', 'Monkey.D.Luffy OP01-003', 'OP-01 Romance Dawn', 'Líder (L)', 'Novo', 'Japonês', false, 4, 18.00],
            ['onepiece', 'Roronoa Zoro OP01-025', 'OP-01 Romance Dawn', 'Super Rara (SR)', 'Semi-Novo', 'Inglês', true, 2, 32.50],
            ['onepiece', 'Shanks OP09-004', 'OP-09 Emperors in the New World', 'Rara Secreta (SEC)', 'Novo', 'Japonês', true, 1, 540.00],
        ];

        foreach ($cartas as [$jogo, $nome, $colecao, $raridade, $estado, $idioma, $foil, $quantidade, $preco]) {
            Carta::create(compact('jogo', 'nome', 'colecao', 'raridade', 'estado', 'idioma', 'foil', 'quantidade', 'preco'));
        }
    }
}
