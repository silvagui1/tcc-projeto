<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use Illuminate\Database\Eloquent\Model;

class Carta extends Model
{
    use TemImagemDeEstoque;

    /**
     * Jogos aceitos no estoque de cartas (filtro da página, campo do
     * formulário e filtro de campeonatos).
     */
    public const JOGOS = ['pokemon', 'magic', 'onepiece'];

    public const ESTADOS = ['Novo', 'Semi-Novo', 'Usado', 'Danificado'];

    public const IDIOMAS = ['Português', 'Inglês', 'Japonês', 'Espanhol', 'Outro'];

    protected $fillable = [
        'nome',
        'jogo',
        'colecao',
        'raridade',
        'estado',
        'idioma',
        'foil',
        'quantidade',
        'preco',
        'imagem',
    ];

    protected $casts = [
        'foil' => 'boolean',
        'quantidade' => 'integer',
        'preco' => 'decimal:2',
    ];

    public function movimentacoes()
    {
        return $this->hasMany(CartaMovimentacao::class)->latest('id');
    }

    /**
     * Imagem genérica do jogo (public/images/placeholders), usada quando a
     * carta não tem imagem e de reserva quando a url informada não carrega.
     */
    public function getImagemPadraoAttribute(): string
    {
        $arquivo = in_array($this->jogo, self::JOGOS, true) ? "carta-{$this->jogo}.svg" : 'carta-generica.svg';

        return asset('images/placeholders/'.$arquivo);
    }

    /**
     * Etiquetas curtas mostradas embaixo da carta (estado, raridade, idioma,
     * quantidade e foil).
     *
     * @return array<int, string>
     */
    public function getTagsAttribute(): array
    {
        return array_values(array_filter([
            mb_strtolower($this->estado),
            $this->raridade ? mb_strtolower($this->raridade) : null,
            mb_strtolower($this->idioma),
            'qtd '.$this->quantidade,
            $this->foil ? 'foil' : null,
        ]));
    }

    /**
     * Dados usados pelo pop-up de edição (preenche o formulário via JS).
     *
     * @return array<string, mixed>
     */
    public function dadosFormulario(): array
    {
        return $this->only(['id', 'nome', 'jogo', 'colecao', 'raridade', 'estado', 'idioma', 'quantidade'])
            + ['foil' => $this->foil, 'preco' => (float) $this->preco]
            + $this->dadosDeImagemDoFormulario();
    }
}
