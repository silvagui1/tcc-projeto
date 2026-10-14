{{-- Aviso verde depois de adicionar / editar / apagar algo no estoque. --}}
@if (session('estoqueMensagem'))
    <p class="card-added-alert" role="status">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('estoqueMensagem') }}
    </p>
@endif
