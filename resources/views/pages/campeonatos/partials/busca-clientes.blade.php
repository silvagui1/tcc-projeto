{{-- Busca de clientes: digitar filtra a lista logo abaixo; o "+" marca o
     cliente como participante (checkbox escondido, name="participantes[]").
     $clientesSugeridos vem de Cliente::dadosParticipante(). --}}
<div class="champ-search" data-participant-search>
    <div class="champ-search__field">
        <input type="search" placeholder="{{ $placeholder ?? 'Pesquisar' }}" aria-label="Pesquisar clientes" data-participant-search-input>
        <img src="{{ asset('images/campeonatos/search.svg') }}" alt="" width="14.6409" height="14.6409">
    </div>

    <div class="champ-search__results">
        @foreach ($clientesSugeridos as $cliente)
            <label class="participant-row participant-row--pick" data-participant-name="{{ $cliente['nome'] }}">
                <img class="participant-row__avatar" src="{{ $cliente['avatar'] }}" alt="">
                <span class="participant-row__info">
                    <strong>{{ $cliente['nome'] }}</strong>
                    <span>{{ $cliente['nascimento'] }}</span>
                </span>
                <input type="checkbox" name="participantes[]" value="{{ $cliente['id'] }}"
                       {{ in_array($cliente['id'], old('participantes', [])) ? 'checked' : '' }}>
                <img class="participant-row__add" src="{{ asset('images/campeonatos/plus-circle.png') }}" alt="Adicionar" width="24" height="24">
            </label>
        @endforeach
    </div>
</div>
