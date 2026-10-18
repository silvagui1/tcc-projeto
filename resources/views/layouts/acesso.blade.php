{{-- Layout das telas de acesso (login e "crie a sua senha"): sem navbar,
     com o painel da marca deixando claro que é o sistema interno da loja. --}}
@php
    $nomeLoja = \App\Services\Configuracoes::valor('loja.nome');
    $logoLoja = \App\Services\Configuracoes::logoUrl();
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- sistema interno: buscadores não devem listar estas páginas --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — {{ $nomeLoja }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    {{-- mesmo tema escolhido em Configurações > Aparência (ver layouts/app) --}}
    <script>
        (function () {
            var tema = 'claro';
            try { tema = localStorage.getItem('tema') || 'claro'; } catch (e) {}
            var escuro = tema === 'escuro'
                || (tema === 'sistema' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (escuro) document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/js/login.js'])
</head>
<body class="login-body">
    <div class="login">
        {{-- Painel da marca: deixa claro que é o sistema interno da loja,
             não uma área de clientes. --}}
        <aside class="login__marca">
            <div class="login__marca-topo">
                <span class="login__logo {{ $logoLoja ? 'login__logo--personalizada' : '' }}">
                    <img src="{{ $logoLoja ?? asset('images/logo-navbar.svg') }}" alt="">
                </span>
                <div>
                    <strong class="login__loja">{{ $nomeLoja }}</strong>
                    <span class="login__sistema">Sistema interno de gestão</span>
                </div>
            </div>

            <div class="login__marca-texto">
                <span class="login__selo">
                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                    Área restrita
                </span>
                <h1>Uso exclusivo da equipe da loja</h1>
                <p>Vendas, estoque, clientes, mesas e campeonatos em um só lugar — acessível só para funcionários com conta.</p>
            </div>

            <ul class="login__areas" aria-hidden="true">
                <li><i class="bi bi-credit-card-fill"></i></li>
                <li><i class="bi bi-box-seam-fill"></i></li>
                <li><i class="bi bi-people-fill"></i></li>
                <li><i class="bi bi-trophy-fill"></i></li>
            </ul>
        </aside>

        <main class="login__conteudo">
            <div class="login__cartao">
                @yield('conteudo')
            </div>

            <p class="login__rodape">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                Acesso restrito a funcionários · as entradas no sistema são registradas
            </p>
        </main>
    </div>
</body>
</html>
