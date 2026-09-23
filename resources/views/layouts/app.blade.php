<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TCC Projeto') — ArtPlay</title>

    {{-- logo sobre fundo azul-marinho, para aparecer tanto em abas claras quanto escuras --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    {{-- Fonte Inter (a mesma do protótipo no Figma; já é a primeira do font-family no app.css) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">

    {{-- Ícones (Bootstrap Icons): usados no lugar dos ícones do Figma, que não
         podem ser exportados a partir deste ambiente. Trocar depois pelos SVGs
         exportados do Figma se quiser fidelidade 100% ao design. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
