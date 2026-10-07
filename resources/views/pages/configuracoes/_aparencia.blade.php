{{-- Tema: a única configuração por aparelho (fica no navegador, ver app.js).
     Salva na hora, sem botão. --}}
<section class="config-secao" id="aparencia" aria-labelledby="titulo-aparencia">
    <header class="config-secao__cabecalho">
        <span class="config-secao__icone" aria-hidden="true"><i class="bi bi-circle-half"></i></span>
        <div>
            <h2 id="titulo-aparencia">Aparência</h2>
            <p>Vale só para este aparelho — cada computador ou celular escolhe o seu.</p>
        </div>
    </header>

    <div class="config-card">
        <div class="config-linha">
            <div class="config-linha__texto">
                <strong>Tema</strong>
                <span>Claro, escuro ou igual ao do aparelho.</span>
            </div>
            <div class="theme-switch" role="radiogroup" aria-label="Tema do site" data-theme-switch>
                <label>
                    <input type="radio" name="tema" value="claro">
                    <i class="bi bi-sun-fill" aria-hidden="true"></i> Claro
                </label>
                <label>
                    <input type="radio" name="tema" value="escuro">
                    <i class="bi bi-moon-fill" aria-hidden="true"></i> Escuro
                </label>
                <label>
                    <input type="radio" name="tema" value="sistema">
                    <i class="bi bi-circle-half" aria-hidden="true"></i> Automático
                </label>
            </div>
        </div>
    </div>
</section>
