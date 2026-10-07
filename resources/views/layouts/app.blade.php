<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Exigido pelo clientes.js (csrfToken()) para as chamadas AJAX de
         criar/editar/excluir cliente. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TCC Projeto') — {{ \App\Services\Configuracoes::valor('loja.nome') }}</title>

    {{-- Config compartilhada com resources/js/clientes.js — evita manter a
         paleta de cores do avatar (App\Models\Cliente::CORES_AVATAR) e o DDI
         do WhatsApp duplicados "de cabeça" em PHP e em JS. --}}
    <script id="app-config" type="application/json">{!! json_encode([
        'avatarCores' => \App\Models\Cliente::coresAvatar(),
        'whatsappDdi' => \App\Services\Configuracoes::valor('clientes.whatsapp_ddi'),
    ]) !!}</script>

    {{-- Favicons: favicon.ico (logo sobre fundo azul-marinho) é a reserva para Safari e navegadores antigos;
         favicon.svg é a logo da navbar com fundo transparente, que troca de cor conforme o tema claro/escuro;
         apple-touch-icon.png (180x180, fundo sólido) é o ícone usado ao salvar o site na tela inicial do celular. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Fonte Inter: vem do @fontsource importado no app.css (sem chamadas externas). --}}

    {{-- Ícones (Bootstrap Icons): usados no lugar dos ícones do Figma, que não
         podem ser exportados a partir deste ambiente. Trocar depois pelos SVGs
         exportados do Figma se quiser fidelidade 100% ao design. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    {{-- Tema escuro: aplicado aqui, antes do CSS, para a página não piscar
         branca ao carregar. A escolha fica salva no navegador ("tema":
         claro | escuro | sistema) e é trocada em Configurações > Aparência. --}}
    <script>
        (function () {
            var tema = 'claro';
            try { tema = localStorage.getItem('tema') || 'claro'; } catch (e) {}
            var escuro = tema === 'escuro'
                || (tema === 'sistema' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (escuro) document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/css/clientes.css', 'resources/css/vendas.css', 'resources/css/configuracoes.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        {{-- Navbar de largura total, só no desktop. No mobile ela fica
             escondida, porque lá quem faz a navegação é o menu inferior em arco. --}}
        @include('partials.topbar')

        {{-- páginas podem pedir uma área mais larga no desktop com
             @section('main_class', 'app-content--wide') --}}
        <main class="app-content @yield('main_class')">
            @yield('content')
        </main>

        {{-- Menu inferior: fixo, só aparece no mobile --}}
        @include('partials.bottom-nav')
    </div>
</body>
</html>
