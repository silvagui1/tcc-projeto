{{-- Navbar superior (somente desktop) — ocupa a largura inteira da tela,
     com a logo centralizada e as páginas divididas nos dois lados. Substitui
     o antigo botão de hambúrguer + menu lateral em drawer: a navegação fica
     sempre visível, sem precisar abrir nada. No mobile quem navega é o menu
     inferior em arco (partials/bottom-nav.blade.php). --}}
<header class="topbar">
    <nav class="topbar__nav topbar__nav--left">
        {{-- cada link tem a sua cor de destaque (a mesma dos ícones da página
             inicial), usada na linha embaixo da página atual --}}
        <a href="{{ route('estoque.index') }}" class="topbar__link--estoque {{ request()->routeIs('estoque.*') ? 'is-current' : '' }}">Estoque</a>
        <a href="{{ route('campeonatos.index') }}" class="topbar__link--campeonatos {{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}">Campeonatos</a>
    </nav>

    {{-- Logo (images/logo-navbar.svg, versão de logofinal.svg com fundo transparente e traços brancos, preparada para
         o fundo escuro da navbar). Clicar na logo leva para a página inicial. --}}
    <a href="{{ route('home') }}" class="topbar__logo" title="Página inicial">
        <img src="{{ asset('images/logo-navbar.svg') }}" alt="ArtPlay">
    </a>

    <nav class="topbar__nav topbar__nav--right">
        <a href="{{ route('clientes') }}" class="topbar__link--clientes {{ request()->routeIs('clientes') ? 'is-current' : '' }}">Clientes</a>
        <a href="{{ route('vendas') }}" class="topbar__link--vendas {{ request()->routeIs('vendas') ? 'is-current' : '' }}">Vendas</a>
    </nav>

    {{-- Ícone de engrenagem fixado na ponta direita da navbar, fora do grid
         de 3 colunas, para não desalinhar a logo do centro. --}}
    <a href="{{ route('config') }}" class="topbar__settings {{ request()->routeIs('config') ? 'is-current' : '' }}" aria-label="Configurações" title="Configurações">
        <i class="bi bi-gear-fill"></i>
    </a>
</header>
