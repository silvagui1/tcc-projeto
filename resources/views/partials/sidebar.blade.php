{{-- Menu lateral fixo — aparece somente no desktop (ver .sidebar no app.css) --}}
<aside class="sidebar">
    <div class="sidebar__avatar" style="background-image: url('https://www.figma.com/api/mcp/asset/d739a5e1-997e-473c-a82c-69ad0385d9c4.png');"></div>
    <p class="sidebar__name">Usuário X</p>

    <nav class="sidebar__nav">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-house-door-fill"></i></span>
            Pagina inicial
        </a>
        <a href="{{ route('estoque.index') }}" class="{{ request()->routeIs('estoque.*') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-box-seam-fill"></i></span>
            Estoque
        </a>
        <a href="{{ route('campeonatos.index') }}" class="{{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-trophy-fill"></i></span>
            Campeonatos
        </a>
        <a href="{{ route('clientes') }}" class="{{ request()->routeIs('clientes') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-people-fill"></i></span>
            Clientes
        </a>
        {{-- "Produtos" ainda não tem uma tela própria no protótipo, então aponta
             para o estoque (onde os produtos aparecem listados) --}}
        <a href="{{ route('estoque.index') }}" class="{{ request()->routeIs('estoque.index') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-bag-fill"></i></span>
            Produtos
        </a>
        <a href="{{ route('config') }}" class="{{ request()->routeIs('config') ? 'is-current' : '' }}">
            <span class="icon"><i class="bi bi-gear-fill"></i></span>
            Configurações
        </a>
    </nav>
</aside>
