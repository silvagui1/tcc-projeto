<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Imagem de produto / carta. A coluna `imagem` guarda uma de duas coisas:
 * um link externo (https://...) colado no formulário, ou o caminho de um
 * arquivo enviado (colado, arrastado ou escolhido do aparelho) dentro do
 * disco "public" — ex.: "cartas/abc123.png".
 */
trait TemImagemDeEstoque
{
    public static function ehLinkExterno(?string $imagem): bool
    {
        return (bool) preg_match('#^https?://#i', (string) $imagem);
    }

    /**
     * true quando a imagem é um arquivo guardado no servidor (e não um link).
     */
    public function temArquivoDeImagem(): bool
    {
        return $this->imagem && ! self::ehLinkExterno($this->imagem);
    }

    /**
     * Apaga do disco o arquivo de imagem, se houver (links externos não são
     * nossos, então não há o que apagar).
     */
    public function apagarArquivoDeImagem(): void
    {
        if ($this->temArquivoDeImagem()) {
            Storage::disk('public')->delete($this->imagem);
        }
    }

    /**
     * URL pública da imagem, ou a imagem genérica quando não há nenhuma.
     * Usa asset() pelo mesmo motivo de Cliente::getFotoUrlAttribute().
     */
    public function getImagemUrlAttribute(): string
    {
        if (! $this->imagem) {
            return $this->imagem_padrao;
        }

        return self::ehLinkExterno($this->imagem) ? $this->imagem : asset('storage/'.$this->imagem);
    }

    /**
     * Campos de imagem para o pop-up de edição: o campo "URL" só mostra
     * links externos (um caminho interno não faria sentido ali) e a
     * pré-visualização mostra a imagem própria do item, se houver.
     *
     * @return array{imagem: string, imagem_preview: string}
     */
    protected function dadosDeImagemDoFormulario(): array
    {
        return [
            'imagem' => self::ehLinkExterno($this->imagem) ? $this->imagem : '',
            'imagem_preview' => $this->imagem ? $this->imagem_url : '',
        ];
    }
}
