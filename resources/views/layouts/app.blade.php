<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Exigido pelo clientes.js (csrfToken()) para as chamadas AJAX de
         criar/editar/excluir cliente. Não existe em mobilenav_atualizado
         hoje porque aquela branch ainda não tem nenhuma tela com AJAX. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clientes') — ArtPlay</title>

    {{-- logo sobre fundo azul-marinho, para aparecer tanto em abas claras quanto escuras --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    {{-- Ícones (Bootstrap Icons), mesma fonte de ícones usada no resto do
         app em mobilenav_atualizado, no lugar dos SVGs desenhados à mão. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        {{-- Navbar de largura total, só no desktop. No mobile quem navega é
             o menu inferior em arco. --}}
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
