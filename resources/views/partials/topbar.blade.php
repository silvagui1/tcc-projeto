{{-- Navbar superior (somente desktop) — ocupa a largura inteira da tela,
     com a logo centralizada e as páginas divididas nos dois lados. Substitui
     o antigo botão de hambúrguer + menu lateral em drawer: a navegação fica
     sempre visível, sem precisar abrir nada. No mobile quem navega é o menu
     inferior em arco (partials/bottom-nav.blade.php). --}}
<header class="topbar">
    <nav class="topbar__nav topbar__nav--left">
        <a href="{{ route('estoque.index') }}" class="{{ request()->routeIs('estoque.*') ? 'is-current' : '' }}">Estoque</a>
        <a href="{{ route('campeonatos.index') }}" class="{{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}">Campeonatos</a>
    </nav>

    {{-- Espaço reservado para a logo. Por enquanto é só o nome em texto —
         trocar por <img src="..." class="topbar__logo"> quando tivermos o
         arquivo exportado do Figma. Clicar na logo leva para a página inicial. --}}
    <a href="{{ route('home') }}" class="topbar__logo" title="Página inicial">ArtPlay</a>

    <nav class="topbar__nav topbar__nav--right">
        <a href="{{ route('clientes') }}" class="{{ request()->routeIs('clientes') ? 'is-current' : '' }}">Clientes</a>
        <a href="{{ route('vendas') }}" class="{{ request()->routeIs('vendas') ? 'is-current' : '' }}">Vendas</a>
    </nav>

    {{-- Ícone de engrenagem fixado na ponta direita da navbar, fora do grid
         de 3 colunas, para não desalinhar a logo do centro. --}}
    <a href="{{ route('config') }}" class="topbar__settings {{ request()->routeIs('config') ? 'is-current' : '' }}" aria-label="Configurações" title="Configurações">
        <i class="bi bi-gear-fill"></i>
    </a>
</header>
