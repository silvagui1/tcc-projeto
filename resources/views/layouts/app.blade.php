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
        {{-- Menu lateral: fixo, só aparece no desktop --}}
        @include('partials.sidebar')

        <main class="app-content">
            @yield('content')
        </main>

        {{-- Menu inferior: fixo, só aparece no mobile --}}
        @include('partials.bottom-nav')
    </div>
</body>
</html>
