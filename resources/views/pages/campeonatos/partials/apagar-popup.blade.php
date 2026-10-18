{{-- Pop-up "Apagar campeonato". Abre ao clicar em qualquer botão com
     data-champ-apagar="<url>" (ver campeonatos.js). Campeonato semanal: sem
     marcar "parar de repetir", o da semana seguinte fica pré-cadastrado. --}}
<dialog class="champ-dialog" data-champ-apagar-dialog aria-labelledby="apagar-campeonato-titulo">
    <form method="POST" action="">
        @csrf
        @method('DELETE')

        <h2 class="champ-dialog__titulo" id="apagar-campeonato-titulo" data-champ-apagar-titulo>Apagar este campeonato?</h2>
        <p class="champ-dialog__texto">
            As inscrições e a premiação dele saem do sistema e não dá para desfazer.
            Créditos já ganhos e inscrições pagas continuam com os clientes e em Vendas.
        </p>

        <label class="champ-check" data-champ-apagar-semanal hidden>
            <input type="checkbox" name="parar_repeticao" value="1">
            <span>
                <strong>Parar de repetir toda semana</strong>
                <small>Sem marcar, só esta semana é pulada: a próxima continua pré-cadastrada.</small>
            </span>
        </label>

        <div class="champ-dialog__acoes">
            <button type="button" class="botao botao--neutro" data-dialog-fechar>Manter</button>
            <button type="submit" class="botao botao--perigo">Apagar</button>
        </div>
    </form>
</dialog>
