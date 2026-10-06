<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Campo de imagem dos pop-ups de produto e de carta. O formulário pode
 * mandar um arquivo (imagem_arquivo — escolhido do aparelho, colado com
 * Ctrl+V ou arrastado), um link (imagem) ou pedir para tirar a imagem
 * (remover_imagem).
 */
trait TrataImagemDoEstoque
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function regrasDeImagem(): array
    {
        return [
            'imagem' => ['nullable', 'url', 'max:500'],
            'imagem_arquivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remover_imagem' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function mensagensDeImagem(): array
    {
        return [
            'imagem_arquivo.image' => 'O arquivo enviado precisa ser uma imagem.',
            'imagem_arquivo.mimes' => 'A imagem deve estar em formato JPG, PNG, WEBP ou GIF.',
            'imagem_arquivo.max' => 'A imagem deve ter no máximo 4MB.',
            'imagem_arquivo.uploaded' => 'Não foi possível enviar a imagem. Tente uma imagem menor.',
        ];
    }

    /**
     * Dados validados prontos para create()/update(), com a coluna `imagem`
     * já resolvida. Ordem de prioridade: arquivo novo > pedido de remoção >
     * link digitado > (na edição) a imagem que o item já tinha. Quando a
     * imagem antiga era um arquivo e foi trocada, o arquivo é apagado.
     *
     * @param  string  $pasta  pasta dentro do disco "public" (ex.: "cartas")
     * @param  Model|null  $atual  item sendo editado (null ao cadastrar)
     * @return array<string, mixed>
     */
    public function dadosComImagem(string $pasta, ?Model $atual = null): array
    {
        $dados = collect($this->validated())->except(['imagem_arquivo', 'remover_imagem'])->all();

        if ($this->hasFile('imagem_arquivo')) {
            $dados['imagem'] = $this->file('imagem_arquivo')->store($pasta, 'public');
        } elseif ($this->boolean('remover_imagem')) {
            $dados['imagem'] = null;
        } elseif (empty($dados['imagem'])) {
            // campo de link vazio na edição = manter o arquivo que já existia
            $dados['imagem'] = $atual?->temArquivoDeImagem() ? $atual->imagem : null;
        }

        if ($atual && $atual->imagem !== $dados['imagem']) {
            $atual->apagarArquivoDeImagem();
        }

        return $dados;
    }
}
