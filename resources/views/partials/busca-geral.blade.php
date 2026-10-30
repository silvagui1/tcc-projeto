{{-- Busca geral: abre com Ctrl+K (⌘K no Mac), com "/" fora de campos de
     texto em páginas sem busca própria, ou por qualquer botão
     [data-abrir-busca]. Resultados vêm de /busca (BuscaController). Setas
     navegam, Enter abre, Esc fecha (ver resources/js/busca-geral.js). --}}
<dialog class="busca-geral" data-busca-geral data-url="{{ route('busca') }}" aria-label="Busca geral">
    <form class="busca-geral__campo" role="search" data-busca-geral-form>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" placeholder="Buscar cliente, produto, carta, venda (#12) ou campeonato"
               autocomplete="off" spellcheck="false" aria-label="O que você procura?"
               aria-controls="busca-geral-resultados" data-busca-geral-input>
        <kbd>Esc</kbd>
    </form>

    <div class="busca-geral__resultados" id="busca-geral-resultados" role="listbox" data-busca-geral-resultados>
        <p class="busca-geral__dica">Digite pelo menos 2 letras, ou o número de uma venda.</p>
    </div>

    <p class="busca-geral__rodape" aria-hidden="true">
        <span><kbd>↑</kbd><kbd>↓</kbd> navegar</span>
        <span><kbd>Enter</kbd> abrir</span>
        <span><kbd>Ctrl</kbd><kbd>K</kbd> buscar de qualquer tela</span>
    </p>
</dialog>
