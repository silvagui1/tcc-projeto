{{-- Pop-up "Deseja apagar?" do estoque (mesmo visual do de campeonatos).
     Abre ao clicar em qualquer botão com data-delete-open="<url>" — o app.js
     coloca essa url como action do form e troca o título pelo
     data-delete-title do botão. --}}
<dialog class="confirm-popup" data-delete-dialog>
    <form method="POST" action="" data-delete-form>
        @csrf
        @method('DELETE')

        <p class="confirm-popup__title" data-delete-title>Deseja apagar este item?</p>

        <div class="confirm-popup__actions">
            <button type="submit" class="confirm-popup__yes">Sim, apagar.</button>
            <button type="button" class="confirm-popup__no" data-delete-cancel>Não, manter.</button>
        </div>

        <p class="confirm-popup__hint">caso o item seja apagado não terá como desfazer essa ação.</p>
    </form>
</dialog>
