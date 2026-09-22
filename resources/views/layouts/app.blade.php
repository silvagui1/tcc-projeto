<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Sistema') · SIGA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="app-nav">
            <div class="app-nav__perfil" title="Usuário logado">
                <span class="avatar avatar--perfil" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2.25c-4.14 0-7.5 2.35-7.5 5.25v.75a.75.75 0 0 0 .75.75h13.5a.75.75 0 0 0 .75-.75v-.75c0-2.9-3.36-5.25-7.5-5.25Z" fill="currentColor"/>
                    </svg>
                </span>
            </div>

            <nav class="app-nav__menu" aria-label="Menu principal">
                <a href="{{ route('clientes.index') }}" class="app-nav__item app-nav__item--ativo">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="app-nav__item-label">Clientes</span>
                </a>
            </nav>
        </aside>

        <main class="app-content">
            @yield('conteudo')
        </main>
    </div>
</body>
</html>
