{{-- Menu inferior fixo — aparece em todas as páginas no mobile (ver .bottom-nav
     no app.css). No desktop ele é escondido e o menu lateral assume o lugar dele. --}}
<nav class="bottom-nav" data-bottom-nav>
    <div class="bottom-nav__arc">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-current' : '' }}" title="Página inicial">
            <i class="bi bi-house-door-fill"></i>
        </a>
        <a href="{{ route('clientes') }}" class="{{ request()->routeIs('clientes') ? 'is-current' : '' }}" title="Clientes">
            <i class="bi bi-people-fill"></i>
        </a>
        <a href="{{ route('campeonatos.index') }}" class="{{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}" title="Campeonatos">
            <i class="bi bi-trophy-fill"></i>
        </a>
        <a href="{{ route('estoque.index') }}" class="{{ request()->routeIs('estoque.*') ? 'is-current' : '' }}" title="Estoque">
            <i class="bi bi-box-seam-fill"></i>
        </a>
        <a href="{{ route('vendas') }}" class="{{ request()->routeIs('vendas') ? 'is-current' : '' }}" title="Vendas">
            <i class="bi bi-credit-card-fill"></i>
        </a>
        <a href="{{ route('config') }}" class="{{ request()->routeIs('config') ? 'is-current' : '' }}" title="Configurações">
            <i class="bi bi-gear-fill"></i>
        </a>
    </div>

    <button type="button" class="bottom-nav__toggle" data-bottom-nav-toggle aria-label="Abrir menu">
        <i class="bi bi-list"></i>
    </button>
</nav>
