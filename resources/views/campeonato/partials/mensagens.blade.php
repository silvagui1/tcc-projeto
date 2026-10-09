{{-- Avisos depois de salvar (session success/error) e erros de validação --}}
@if (session('success'))
    <p class="champ-flash">{{ session('success') }}</p>
@endif

@if (session('error'))
    <p class="champ-locked">{{ session('error') }}</p>
@endif

@if ($errors->any())
    <div class="card-form__errors">
        Confira os campos:
        <ul>
            @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif
