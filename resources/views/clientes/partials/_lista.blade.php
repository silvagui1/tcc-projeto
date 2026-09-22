<ul class="clientes-lista" data-lista>
    @forelse ($clientes as $cliente)
        @include('clientes.partials._linha', ['cliente' => $cliente])
    @empty
        <li class="clientes-vazio">Nenhum cliente encontrado.</li>
    @endforelse
</ul>
