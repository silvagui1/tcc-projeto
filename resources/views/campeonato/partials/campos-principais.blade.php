{{-- "Informações Principais" + "Status" — mesmos campos em criar e editar.
     Com $campeonato = null os campos vêm vazios (tela de criar). Os nomes dos
     campos aparecem como placeholder dentro do input, igual ao Figma.
     old() devolve o que foi digitado quando a validação falha. --}}
<h3>Informações Principais</h3>

<input class="champ-input" type="text" name="nome" placeholder="nome do campeonato" aria-label="nome do campeonato"
       value="{{ old('nome', $campeonato?->nome) }}" required>

@php
    $data = old('data', $campeonato?->data?->format('Y-m-d'));
    $horario = old('horario', $campeonato?->horario_curto);
    $valor = old('valor_inscricao', $campeonato ? 'R$' . number_format($campeonato->valor_inscricao, 2, ',', '.') : '');
    $status = old('status', $campeonato?->status ?? 'ativo');
@endphp

<div class="champ-form__row">
    {{-- type="text" até receber foco, para o placeholder "data" aparecer
         (input date não mostra placeholder) --}}
    <label class="champ-input champ-input--icon">
        <input type="{{ $data ? 'date' : 'text' }}" name="data" placeholder="data" aria-label="data"
               value="{{ $data }}"
               onfocus="this.type = 'date'" onblur="if (!this.value) this.type = 'text'" required>
        <img src="{{ asset('images/campeonatos/calendar-days.svg') }}" alt="" width="20" height="20">
    </label>
    <label class="champ-input champ-input--icon">
        <input type="{{ $horario ? 'time' : 'text' }}" name="horario" placeholder="Horário" aria-label="Horário"
               value="{{ $horario }}"
               onfocus="this.type = 'time'" onblur="if (!this.value) this.type = 'text'">
        <img src="{{ asset('images/campeonatos/alarm.svg') }}" alt="" width="14.9999" height="16.04">
    </label>
</div>

<div class="champ-form__row">
    <input class="champ-input" type="text" name="deck" placeholder="deck" aria-label="deck"
           value="{{ old('deck', $campeonato?->deck) }}" required>
    <input class="champ-input" type="text" name="valor_inscricao" placeholder="valor da inscrição" aria-label="valor da inscrição" inputmode="decimal"
           value="{{ $valor }}" required>
</div>

<textarea class="champ-input champ-input--area" name="descricao" placeholder="descrição" aria-label="descrição" maxlength="350">{{ old('descricao', $campeonato?->descricao) }}</textarea>

<input class="champ-input" type="url" name="imagem" aria-label="url da imagem"
       placeholder="{{ $campeonato ? 'Url da imagem' : 'imagem' }}"
       value="{{ old('imagem', $campeonato?->imagem) }}">

<h3>Status</h3>

<div class="champ-status-chips">
    <input type="radio" id="status-ativo" name="status" value="ativo" {{ $status === 'ativo' ? 'checked' : '' }}>
    <label for="status-ativo">
        <img src="{{ asset('images/campeonatos/dot-green.svg') }}" alt="" width="12" height="12">
        Ativo
    </label>

    <input type="radio" id="status-finalizado" name="status" value="finalizado" {{ $status === 'finalizado' ? 'checked' : '' }}>
    <label for="status-finalizado">
        <img src="{{ asset('images/campeonatos/dot-red-2.svg') }}" alt="" width="12" height="12">
        Finalizado
    </label>
</div>
