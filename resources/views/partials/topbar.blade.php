{{-- Navbar superior (somente desktop) — ocupa a largura inteira da tela,
     com a logo centralizada e as páginas divididas nos dois lados. Substitui
     o antigo botão de hambúrguer + menu lateral em drawer: a navegação fica
     sempre visível, sem precisar abrir nada. No mobile quem navega é o menu
     inferior em arco (partials/bottom-nav.blade.php). --}}
<header class="topbar">
    <nav class="topbar__nav topbar__nav--left">
        {{-- cada link tem a sua cor de destaque (a mesma dos ícones da página
             inicial), usada na linha embaixo da página atual. O ícone é o
             mesmo do menu do celular (bottom-nav): aparece transparente atrás
             do nome da página selecionada (animação em .topbar__icone). --}}
        <a href="{{ route('estoque.index') }}" class="topbar__link--estoque {{ request()->routeIs('estoque.*') ? 'is-current' : '' }}">
            <i class="bi bi-box-seam-fill topbar__icone" aria-hidden="true"></i><span>Estoque</span>
        </a>
        <a href="{{ route('campeonatos.index') }}" class="topbar__link--campeonatos {{ request()->routeIs('campeonatos.*') ? 'is-current' : '' }}">
            <i class="bi bi-trophy-fill topbar__icone" aria-hidden="true"></i><span>Campeonatos</span>
        </a>
    </nav>

    {{-- Logo (images/logo-navbar.svg, versão de logofinal.svg com fundo transparente e traços brancos, preparada para
         o fundo escuro da navbar). Clicar na logo leva para a página inicial. --}}
    {{-- Logo enviada em Configurações > Loja substitui a padrão. --}}
    @php $logoLoja = \App\Services\Configuracoes::logoUrl(); @endphp
    <a href="{{ route('home') }}" class="topbar__logo {{ $logoLoja ? 'topbar__logo--personalizada' : '' }}" title="Página inicial">
        <img src="{{ $logoLoja ?? asset('images/logo-navbar.svg') }}" alt="{{ \App\Services\Configuracoes::valor('loja.nome') }}">
    </a>

    <nav class="topbar__nav topbar__nav--right">
        <a href="{{ route('clientes.index') }}" class="topbar__link--clientes {{ request()->routeIs('clientes.*') ? 'is-current' : '' }}">
            <i class="bi bi-people-fill topbar__icone" aria-hidden="true"></i><span>Clientes</span>
        </a>
        <a href="{{ route('vendas.index') }}" class="topbar__link--vendas {{ request()->routeIs('vendas.*') ? 'is-current' : '' }}">
            <i class="bi bi-credit-card-fill topbar__icone" aria-hidden="true"></i><span>Vendas</span>
        </a>
    </nav>

    {{-- Quem está conectado + sair, e a engrenagem, fixados na ponta direita
         da navbar, fora do grid de 3 colunas, para não desalinhar a logo do centro. --}}
    <div class="topbar__acoes">
        @auth
            <form method="POST" action="{{ route('logout') }}" class="topbar__conta">
                @csrf
                <span class="topbar__usuario" title="{{ auth()->user()->nome }} · {{ auth()->user()->papelRotulo() }}">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    {{ auth()->user()->primeiroNome() }}
                </span>
                <button type="submit" class="topbar__sair" aria-label="Sair do sistema" title="Sair do sistema">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        @endauth
        <a href="{{ route('config') }}" class="topbar__settings {{ request()->routeIs('config') ? 'is-current' : '' }}" aria-label="Configurações" title="Configurações">
            <i class="bi bi-gear-fill"></i>
        </a>
    </div>
</header>
