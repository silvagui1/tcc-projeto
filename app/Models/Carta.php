<?php

namespace App\Models;

use App\Models\Concerns\TemImagemDeEstoque;
use App\Models\Concerns\TemMovimentacoes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Carta extends Model
{
    // SoftDeletes: "apagar" arquiva (ver migration add_arquivamento_ao_estoque)
    use SoftDeletes, TemImagemDeEstoque, TemMovimentacoes;

    // Jogos, estados e idiomas aceitos são configuráveis (Configurações >
    // Estoque): ver JogoCarta e as chaves estoque.* de App\Services\Configuracoes.

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
     * Jogos cadastrados depois (sem arte própria) usam a genérica.
     */
    public function getImagemPadraoAttribute(): string
    {
        $arquivo = "carta-{$this->jogo}.svg";

        if (! preg_match('/^[a-z0-9-]+$/', (string) $this->jogo) || ! is_file(public_path('images/placeholders/'.$arquivo))) {
            $arquivo = 'carta-generica.svg';
        }

        return asset('images/placeholders/'.$arquivo);
    }

    /**
     * Etiquetas curtas mostradas embaixo da carta (estado, raridade, idioma
     * e foil).
     *
     * @return array<int, string>
     */
    public function getTagsAttribute(): array
    {
        // só a 1ª letra maiúscula (mantém siglas como "SAR"); quantidade e
        // preço aparecem fora das etiquetas
        return array_values(array_map(Str::ucfirst(...), array_filter([
            $this->estado,
            $this->raridade,
            $this->idioma,
            $this->foil ? 'Foil' : null,
        ])));
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
