<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TCC Projeto') — ArtPlay</title>

    {{-- Ícones (Bootstrap Icons): usados no lugar dos ícones do Figma, que não
         podem ser exportados a partir deste ambiente. Trocar depois pelos SVGs
         exportados do Figma se quiser fidelidade 100% ao design. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        {{-- Botão que abre o menu no desktop. No mobile ele fica escondido,
             porque lá quem faz a navegação é o menu inferior em arco. --}}
        <button type="button" class="menu-toggle" data-menu-toggle
                aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>

        {{-- Camada escura por trás do menu. Fica sempre no HTML: quem decide
             se ela aparece é o CSS, reagindo à classe .menu-aberto no <body>. --}}
        <div class="menu-backdrop" data-menu-backdrop hidden></div>

        {{-- Menu lateral: agora abre por cima do conteúdo (drawer), não ocupa
             mais espaço fixo na tela --}}
        @include('partials.sidebar')

        <main class="app-content">
            @yield('content')
        </main>

        {{-- Menu inferior: fixo, só aparece no mobile --}}
        @include('partials.bottom-nav')
    </div>
</body>
</html>
