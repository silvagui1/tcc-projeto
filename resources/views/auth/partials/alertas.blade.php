@if (session('aviso'))
    <div class="login__alerta login__alerta--info" role="status">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <span>{{ session('aviso') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="login__alerta login__alerta--erro" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif
