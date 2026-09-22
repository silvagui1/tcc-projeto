{{-- Navbar superior (somente desktop), portada de mobilenav_atualizado —
     ocupa a largura inteira da tela, com a logo centralizada e as páginas
     divididas nos dois lados. No mobile quem navega é o menu inferior em
     arco (partials/bottom-nav.blade.php).

     Único ajuste em relação ao original: route('clientes') -> route('clientes.index'),
     já que esta branch usa o nome de rota RESTful (clientes.index) em vez do
     nome único 'clientes' do placeholder de mobilenav_atualizado. As demais
     rotas (estoque/campeonatos/vendas/config/home) ainda não existem nesta
     branch — ver os placeholders temporários em routes/web.php. --}}
<header class="topbar">
    <nav class="topbar__nav topbar__nav--left">
        <a href="{{ route('estoque.index') }}" class="{{ request()->routeIs('estoque.*') ? 'is-current' : '' }}">Estoque</a>
        <a href="{{ route('campeonatos.index') }}" class="{{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}">Campeonatos</a>
    </nav>

    <a href="{{ route('home') }}" class="topbar__logo" title="Página inicial">ArtPlay</a>

    <nav class="topbar__nav topbar__nav--right">
        <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'is-current' : '' }}">Clientes</a>
        <a href="{{ route('vendas') }}" class="{{ request()->routeIs('vendas') ? 'is-current' : '' }}">Vendas</a>
    </nav>

    <a href="{{ route('config') }}" class="topbar__settings {{ request()->routeIs('config') ? 'is-current' : '' }}" aria-label="Configurações" title="Configurações">
        <i class="bi bi-gear-fill"></i>
    </a>
</header>
