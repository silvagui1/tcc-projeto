{{-- Busca de cliente com resultado em lista (usada na venda e no aluguel).
     Com $permitirAvulso, a lista também oferece usar o nome digitado sem
     cadastro (aluguel de quem ainda não é cliente). Comportamento em
     vendas.js (criarSeletorCliente). --}}
<div class="seletor-cliente" data-seletor-cliente="{{ $nome }}" data-permitir-avulso="{{ $permitirAvulso ? '1' : '0' }}">
    <div class="seletor-cliente__escolhido" data-cliente-escolhido hidden>
        <span class="avatar" data-cliente-avatar aria-hidden="true"></span>
        <span class="seletor-cliente__info">
            <strong data-cliente-nome></strong>
            <small data-cliente-detalhe></small>
        </span>
        <button type="button" class="botao-texto" data-cliente-remover>Trocar</button>
    </div>

    <div class="seletor-cliente__busca" data-cliente-busca-bloco>
        <div class="busca">
            <i class="bi bi-person busca__icone" aria-hidden="true"></i>
            <input
                type="search"
                class="busca__campo"
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                aria-label="{{ $placeholder }}"
                aria-autocomplete="list"
                data-cliente-busca
            >
        </div>
        <ul class="sugestoes" data-cliente-resultados role="listbox" hidden></ul>
    </div>
</div>
