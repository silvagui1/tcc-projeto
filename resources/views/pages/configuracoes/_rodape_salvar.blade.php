{{-- Rodapé dos formulários de configuração: diz se há alterações não salvas
     e só libera "Salvar" quando há (ver configuracoes.js). --}}
<footer class="config-card__rodape">
    <span class="config-card__status" data-status>Tudo salvo</span>
    <button type="button" class="botao-texto" data-descartar hidden>Descartar</button>
    <button type="submit" class="botao botao--principal" data-salvar disabled>
        <i class="bi bi-check-lg" aria-hidden="true"></i>
        Salvar
    </button>
</footer>
