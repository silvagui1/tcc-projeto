{{-- Avisos depois de salvar (session success/error) e erros de validação.
     Mesmo aviso verde e mesma caixa de erros do estoque. --}}
@if (session('success'))
    <p class="card-added-alert" role="status">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </p>
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
