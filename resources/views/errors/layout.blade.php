{{-- Tela padrão de erro do sistema (404, 403, 500...). Feita para funcionar
     mesmo quando o próprio erro é o banco fora do ar ou o build faltando:
     o CSS é todo daqui (sem @vite), nome/logo da loja têm reserva e nada
     depende de sessão ou de login. Cada código só preenche as seções. --}}
@php
    try {
        $nomeLoja = \App\Services\Configuracoes::valor('loja.nome');
        $logoLoja = \App\Services\Configuracoes::logoUrl();
    } catch (\Throwable $e) {
        $nomeLoja = config('app.name') === 'Laravel' ? 'Sistema da loja' : config('app.name');
        $logoLoja = null;
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo') — {{ $nomeLoja }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <script>
        (function () {
            var tema = 'claro';
            try { tema = localStorage.getItem('tema') || 'claro'; } catch (e) {}
            var escuro = tema === 'escuro'
                || (tema === 'sistema' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (escuro) document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>
    <style>
        :root {
            --bg: #dfdede; --cartao: #ffffff; --borda: #d5d5d5; --texto: #1a1a1a;
            --texto-suave: #6b6b6b; --navy: #2e3045; --navy-glow: #4c4f72;
            --acento: #2e4762; --selo: #ffdc9b; --selo-texto: #5e4217;
            color-scheme: light;
        }
        :root[data-theme="dark"] {
            --bg: #15161e; --cartao: #282a3c; --borda: #3a3d52; --texto: #ecebf3;
            --texto-suave: #9a98ab; --navy: #2b2d44; --acento: #9ec5f0;
            color-scheme: dark;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); color: var(--texto);
            font-family: "Inter", "Segoe UI", Arial, sans-serif; -webkit-font-smoothing: antialiased;
        }
        .erro__topo {
            display: flex; align-items: center; gap: 12px; padding: 16px 20px; color: #fff;
            background: linear-gradient(160deg, var(--navy-glow), var(--navy) 60%);
        }
        .erro__logo {
            width: 40px; height: 40px; display: grid; place-items: center; padding: 5px; border-radius: 10px;
            background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14);
        }
        .erro__logo--personalizada { background: #fff; }
        .erro__logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .erro__loja { display: block; font-weight: 700; font-size: 15px; }
        .erro__sistema { display: block; font-size: 12px; opacity: .7; }
        .erro__conteudo { flex: 1; display: grid; place-items: center; padding: 32px 16px; }
        .erro__cartao {
            width: 100%; max-width: 440px; text-align: center; background: var(--cartao); border: 1px solid var(--borda);
            border-radius: 24px; padding: 32px 24px 28px; box-shadow: 0 12px 32px rgba(0, 0, 0, .08);
        }
        .erro__icone {
            width: 64px; height: 64px; margin: 0 auto 16px; display: grid; place-items: center; border-radius: 18px;
            background: var(--bg); color: var(--acento); font-size: 28px;
        }
        .erro__codigo {
            display: inline-block; margin-bottom: 10px; padding: 3px 10px; border-radius: 999px; background: var(--selo);
            color: var(--selo-texto); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        }
        .erro__cartao h1 { margin: 0 0 8px; font-size: 22px; font-weight: 800; }
        .erro__cartao p { margin: 0 0 24px; color: var(--texto-suave); font-size: 14px; line-height: 1.55; }
        .erro__acoes { display: flex; flex-direction: column; gap: 10px; }
        .erro__botao {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;
            border-radius: 10px; font: inherit; font-size: 15px; font-weight: 600; text-decoration: none; cursor: pointer;
            border: 1px solid transparent; background: var(--navy); color: #fff;
        }
        :root[data-theme="dark"] .erro__botao { background: var(--navy-glow); }
        .erro__botao--neutro, :root[data-theme="dark"] .erro__botao--neutro {
            background: transparent; color: var(--texto); border-color: var(--borda);
        }
        .erro__botao:focus-visible { outline: 2px solid #8bbaed; outline-offset: 2px; }
        .erro__rodape { margin: 0; padding: 0 16px 20px; text-align: center; color: var(--texto-suave); font-size: 11px; }
    </style>
</head>
<body>
    <header class="erro__topo">
        <span class="erro__logo {{ $logoLoja ? 'erro__logo--personalizada' : '' }}">
            <img src="{{ $logoLoja ?? asset('images/logo-navbar.svg') }}" alt="">
        </span>
        <div>
            <strong class="erro__loja">{{ $nomeLoja }}</strong>
            <span class="erro__sistema">Sistema interno de gestão</span>
        </div>
    </header>

    <main class="erro__conteudo">
        <div class="erro__cartao" role="alert">
            <div class="erro__icone" aria-hidden="true"><i class="bi @yield('icone', 'bi-exclamation-circle')"></i></div>
            <span class="erro__codigo">@yield('codigo')</span>
            <h1>@yield('titulo')</h1>
            <p>@yield('mensagem')</p>

            <div class="erro__acoes">
                @hasSection('acoes')
                    @yield('acoes')
                @else
                    <a href="{{ url('/inicio') }}" class="erro__botao">
                        <i class="bi bi-house-door" aria-hidden="true"></i> Ir para o início
                    </a>
                @endif
                <button type="button" class="erro__botao erro__botao--neutro" data-voltar hidden>
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar à página anterior
                </button>
            </div>
        </div>
    </main>

    <p class="erro__rodape">Se o problema continuar, avise o administrador da loja.</p>

    <script>
        (function () {
            var voltar = document.querySelector('[data-voltar]');
            if (voltar && window.history.length > 1) {
                voltar.hidden = false;
                voltar.addEventListener('click', function () { window.history.back(); });
            }
        })();
    </script>
</body>
</html>
