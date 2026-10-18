{{-- Avisos depois de salvar (session success/error). Erros de validação de
     campos que aparecem na tela ($campos, aceita curingas como
     "premiacoes.*") ficam embaixo de cada campo; os demais aparecem aqui. --}}
@php
    $campos = $campos ?? [];
    $outrosErros = collect($errors->getMessages())
        ->reject(fn ($mensagens, $chave) => \Illuminate\Support\Str::is($campos, $chave))
        ->flatten();
@endphp

@if (session('success'))
    <p class="champ-aviso" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        {{ session('success') }}
    </p>
@endif

@if (session('error'))
    <p class="champ-aviso champ-aviso--erro" role="alert">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        {{ session('error') }}
    </p>
@endif

@foreach ($outrosErros as $erro)
    <p class="champ-aviso champ-aviso--erro" role="alert">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        {{ $erro }}
    </p>
@endforeach
