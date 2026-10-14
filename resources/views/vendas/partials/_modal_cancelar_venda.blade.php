{{-- Confirmação de cancelamento de venda, com o motivo — opcional ou
     obrigatório conforme Configurações > Vendas. Mesmo visual do modal de
     confirmação de Clientes. Texto preenchido pelo vendas.js. --}}
@php $motivoObrigatorio = (bool) \App\Services\Configuracoes::valor('vendas.exigir_motivo_cancelamento'); @endphp
<div class="modal-overlay modal-overlay--confirmar" data-modal="cancelar-venda" hidden>
    <div class="modal-confirmar" role="alertdialog" aria-modal="true" aria-labelledby="cancelar-venda-titulo" aria-describedby="cancelar-venda-texto">
        <i class="bi bi-arrow-counterclockwise modal-confirmar__icone" aria-hidden="true"></i>
        <h2 id="cancelar-venda-titulo" data-cancelar-venda-titulo>Cancelar venda?</h2>
        <p id="cancelar-venda-texto" data-cancelar-venda-texto></p>

        <div class="campo campo--textarea cancelar-venda__motivo">
            <label for="cancelar-venda-motivo">
                Motivo
                @unless ($motivoObrigatorio)<span class="campo__opcional">(opcional)</span>@endunless
            </label>
            <textarea id="cancelar-venda-motivo" rows="2" maxlength="255" placeholder="Ex.: cliente desistiu, item com defeito…" @if ($motivoObrigatorio) required @endif data-cancelar-venda-motivo></textarea>
            <span class="campo__erro" data-cancelar-venda-erro hidden></span>
        </div>

        <div class="modal-confirmar__acoes">
            <button type="button" class="botao botao--neutro botao--full" data-fechar-modal>Voltar</button>
            <button type="button" class="botao botao--perigo botao--full" data-cancelar-venda-confirmar>Cancelar venda</button>
        </div>
    </div>
</div>
