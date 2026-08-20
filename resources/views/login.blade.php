<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Mais Q Fitness</title>
  <meta name="description" content="Acesse sua conta na Mais Q Fitness e continue sua evolução nos treinos." />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          background: '#212121', foreground: '#FAFAFA', card: '#303030', accent: '#F7C346',
          muted: '#303030', 'muted-text': '#A3A3A3', 'surface-light': '#D9D9D9',
          'surface-light-text': '#262626', secondary: '#2E2E2E',
        },
        fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
      } },
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-background text-foreground antialiased">
  <main class="grid min-h-screen lg:grid-cols-2">
    <!-- Coluna do formulário -->
    <section class="flex flex-col px-8 py-8 sm:px-16">
      <div class="flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-black">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        <span class="text-[11px] leading-tight text-muted-text">Logo/Logotipo da<br />Academia</span>
      </div>

      <div class="my-auto w-full max-w-sm py-14">
        <p class="text-sm text-muted-text">Faça seu login</p>
        <h1 class="mt-1 text-2xl font-bold uppercase tracking-tight sm:text-3xl">Bem vindo(a) de volta</h1>

        <form class="mt-8 space-y-6" onsubmit="event.preventDefault(); window.location.href='/adm.html';">
          <label class="relative block rounded-lg border border-neutral-600 px-4 pb-3 pt-4">
            <span class="absolute -top-2 left-3 bg-background px-1 text-xs text-muted-text">E-mail</span>
            <input type="email" required placeholder="exemplo@gmail.com"
              class="w-full bg-transparent pr-8 text-sm outline-none placeholder:text-neutral-400" />
            <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
          </label>

          <label class="relative block rounded-lg border border-neutral-600 px-4 pb-3 pt-4">
            <span class="absolute -top-2 left-3 bg-background px-1 text-xs text-muted-text">Senha</span>
            <input id="senha" type="password" required placeholder="•••••••"
              class="w-full bg-transparent pr-8 text-sm outline-none placeholder:text-neutral-400" />
            <button type="button" onclick="toggle('senha')" aria-label="Mostrar senha"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-text hover:text-foreground">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </label>

          <label class="relative block rounded-lg border border-neutral-600 px-4 pb-3 pt-4">
            <span class="absolute -top-2 left-3 bg-background px-1 text-xs text-muted-text">Confirmar senha</span>
            <input id="senha2" type="password" required placeholder="•••••••"
              class="w-full bg-transparent pr-8 text-sm outline-none placeholder:text-neutral-400" />
            <button type="button" onclick="toggle('senha2')" aria-label="Mostrar senha"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-text hover:text-foreground">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </label>

          <button type="submit"
            class="w-full rounded-lg bg-neutral-400 py-3 text-sm font-semibold text-neutral-900 transition-colors hover:bg-neutral-300">
            Entrar
          </button>

          <p class="text-center text-xs text-muted-text">
            Não tem uma conta? <a href="/cadastro.html" class="text-foreground underline">Criar Conta</a>
          </p>
        </form>
      </div>
    </section>

    <!-- Coluna da imagem -->
    <aside class="hidden items-center justify-center bg-surface-light lg:flex">
      <svg class="h-40 w-40 text-neutral-400" fill="currentColor" viewBox="0 0 24 24">
        <path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm6 3a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-4 11h14l-5-7-3.5 4.5L10 14l-5 5Z"/>
      </svg>
    </aside>
  </main>

  <script>
    function toggle(id) {
      const el = document.getElementById(id);
      el.type = el.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>