{{-- Participantes no pop-up de CRIAR: digitar mostra os clientes com o texto
     no nome; clicar marca/desmarca (checkbox, name="participantes[]"). Com o
     campo vazio aparecem só os já marcados. Quem não é cliente entra como
     provisório (provisorios[n][nome|whatsapp], ver campeonatos.js).
     $clientesSugeridos vem de Cliente::dadosParticipante(). --}}
<div class="champ-search" data-participant-search>
    <div class="busca">
        <i class="bi bi-search busca__icone" aria-hidden="true"></i>
        <input type="search" class="busca__campo" placeholder="Buscar cliente para inscrever" autocomplete="off"
               aria-label="Buscar cliente para inscrever" data-participant-search-input>
    </div>
    <span class="champ-search__contador" data-participant-count aria-live="polite"></span>

    <div class="champ-search__resultados champ-search__resultados--escolha">
        @foreach ($clientesSugeridos as $cliente)
            <label class="champ-escolha" data-participant-name="{{ $cliente['nome'] }}">
                <input type="checkbox" name="participantes[]" value="{{ $cliente['id'] }}" aria-label="Inscrever {{ $cliente['nome'] }}">
                <img class="champ-avatar" src="{{ $cliente['avatar'] }}" alt="">
                <span class="champ-escolha__nome">{{ $cliente['nome'] }}</span>
                <span class="champ-escolha__marca" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
            </label>
        @endforeach
    </div>

    {{-- aparece quando ninguém tem o nome digitado --}}
    <button type="button" class="champ-provisorio-sugestao" data-provisorio-sugerir hidden>
        <i class="bi bi-person-plus" aria-hidden="true"></i>
        <span>Ninguém com esse nome. Inscrever <strong data-provisorio-sugerir-nome></strong> como provisório</span>
    </button>

    {{-- provisórios adicionados neste formulário (preenchido pelo JS) --}}
    <div class="champ-provisorios" data-provisorios-lista></div>

    <template data-provisorio-modelo>
        <div class="champ-provisorio-item" data-provisorio-item>
            <span class="champ-avatar champ-avatar--provisorio" aria-hidden="true"><i class="bi bi-person-dash"></i></span>
            <span class="champ-provisorio-item__campos">
                <input type="text" maxlength="255" placeholder="Nome" aria-label="Nome do provisório" data-provisorio-campo="nome">
                <input type="tel" inputmode="tel" maxlength="16" placeholder="WhatsApp (opcional)" aria-label="WhatsApp do provisório" data-provisorio-campo="whatsapp">
            </span>
            <button type="button" class="champ-icone-botao" data-provisorio-remover aria-label="Tirar provisório">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    </template>

    <button type="button" class="champ-link-acao" data-provisorio-adicionar>
        <i class="bi bi-person-plus" aria-hidden="true"></i>
        Adicionar provisório (quem ainda não é cliente)
    </button>
</div>
