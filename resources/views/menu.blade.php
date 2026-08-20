<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Treino de hoje | Mais Q Fitness</title>
  <meta name="description" content="Painel do aluno: veja o treino do dia, tempo estimado e acompanhe a evolução dos alunos da Mais Q Fitness." />
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
  <header class="border-b border-neutral-800 bg-[#1a1a1a]">
    <div class="mx-auto flex max-w-6xl items-center gap-6 px-6 py-4">
      <div class="flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-black">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        <span class="text-[11px] leading-tight text-muted-text">Logo/Logotipo da<br />Academia</span>
      </div>
      <nav class="ml-8 hidden items-center gap-7 text-sm sm:flex">
        <a href="#blog" class="hover:text-accent">Blog</a>
        <a href="#sobre" class="hover:text-accent">Sobre</a>
        <a href="#treinos" class="hover:text-accent">Treinos</a>
      </nav>
      <button id="menuBtn" aria-label="Abrir menu" class="ml-auto flex flex-col gap-1.5 p-2">
        <span class="block h-0.5 w-7 bg-foreground"></span>
        <span class="block h-0.5 w-7 bg-foreground"></span>
        <span class="block h-0.5 w-7 bg-foreground"></span>
      </button>
    </div>
  </header>

  <main>
    <!-- Treino de hoje -->
    <section id="treinos" class="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1fr_auto]">
      <div>
        <h1 class="text-4xl font-extrabold uppercase leading-tight sm:text-5xl">
          Treino de hoje:<br />
          <span class="text-neutral-500">Upper body</span>
        </h1>
        <div class="mt-6 space-y-1 text-sm text-muted-text">
          <p>Quantidade de exercícios: 12</p>
          <p>Tempo estimado para conclusão: 1h 20 min</p>
          <p>Status: A Começar</p>
        </div>
        <a href="#comecar" class="mt-7 inline-flex items-center gap-3 rounded-md bg-muted px-6 py-3 text-sm font-medium transition-colors hover:bg-secondary">
          Começar a treinar
          <svg class="h-5 w-5 rounded-full border border-current p-[3px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="flex aspect-square items-center justify-center rounded-lg bg-white p-4 sm:row-span-2 sm:aspect-auto sm:h-[380px] sm:w-[260px]">
          <svg class="h-24 w-24 text-neutral-800" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10" stroke-linecap="round"/></svg>
        </div>
        <div class="flex h-[180px] w-full items-center justify-center rounded-lg bg-white sm:w-[200px]">
          <svg class="h-16 w-16 text-neutral-800" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 3v6m0 0-4 5m4-5 4 5M8 21h8" stroke-linecap="round"/></svg>
        </div>
        <div class="flex h-[180px] w-full items-center justify-center rounded-lg bg-white sm:w-[200px]">
          <svg class="h-16 w-16 text-neutral-800" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 6h16M8 6v6a4 4 0 0 0 8 0V6M12 16v4" stroke-linecap="round"/></svg>
        </div>
      </div>
    </section>

    <!-- Evolução dos alunos -->
    <section id="sobre" class="border-t border-neutral-800">
      <div class="mx-auto grid max-w-6xl items-center gap-10 px-6 py-16 md:grid-cols-2">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full border border-neutral-600 px-3 py-1 text-xs">
            <svg class="h-3 w-3 text-accent" fill="currentColor" viewBox="0 0 24 24"><path d="m12 2 2.2 5.8L20 10l-5.8 2.2L12 18l-2.2-5.8L4 10l5.8-2.2z"/></svg>
            Resultado dos Alunos
          </span>
          <h2 class="mt-6 text-3xl font-bold leading-tight sm:text-4xl">
            Acompanhe a <span class="text-neutral-500">evolução</span> de nossos alunos
          </h2>
          <p class="mt-5 max-w-md text-sm leading-relaxed text-muted-text">
            Um espaço pensado para quem busca evolução de verdade. Na Mais Q Fitness, cada detalhe foi
            criado para unir performance, bem-estar e constância, transformando treino em estilo de vida.
          </p>
        </div>
        <div>
          <div class="grid grid-cols-2 gap-4">
            <figure class="relative overflow-hidden rounded-lg">
              <img src="/aluno-1.jpg" alt="Pedro Silva treinando na academia" width="640" height="800" loading="lazy" class="h-72 w-full object-cover" />
              <figcaption class="absolute left-3 top-3 rounded-full bg-surface-light px-3 py-1 text-xs font-semibold text-surface-light-text">Pedro Silva, 33 anos</figcaption>
            </figure>
            <figure class="relative overflow-hidden rounded-lg">
              <img src="/aluno-2.jpg" alt="Aline Carvalho treinando na academia" width="640" height="800" loading="lazy" class="h-72 w-full object-cover" />
              <figcaption class="absolute left-3 top-3 rounded-full bg-surface-light px-3 py-1 text-xs font-semibold text-surface-light-text">Aline Carvalho, 31 anos</figcaption>
            </figure>
          </div>
          <div class="mt-5 flex justify-center gap-4">
            <button aria-label="Anterior" class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-600 hover:bg-secondary">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button aria-label="Próximo" class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-600 hover:bg-secondary">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Menu lateral -->
  <div id="overlay" class="fixed inset-0 z-40 hidden bg-black/60"></div>
  <aside id="drawer" class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md translate-x-full flex-col bg-[#1f1f1f] p-8 transition-transform duration-300">
    <button id="closeBtn" aria-label="Fechar menu" class="self-end p-2 text-muted-text hover:text-foreground">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
    </button>
    <nav class="mt-4 space-y-6 text-sm">
      <a href="#config" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2 2 2 0 1 1-4 0 1.7 1.7 0 0 0-2.9-1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15a2 2 0 1 1 0-4 1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4a2 2 0 1 1 4 0 1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 11a2 2 0 1 1 0 4Z"/></svg>
        Configurações
      </a>
      <a href="#treinos" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10" stroke-linecap="round"/></svg>
        Treinos
      </a>
      <a href="#blog" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6.5C10.5 5 8 4.5 4 5v13c4-.5 6.5 0 8 1.5 1.5-1.5 4-2 8-1.5V5c-4-.5-6.5 0-8 1.5Zm0 0V20"/></svg>
        Blog
      </a>
      <a href="#perfil" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
        Perfil
      </a>
      <a href="#desenvolvimento" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 3s6 5.5 6 10a6 6 0 0 1-12 0c0-4.5 6-10 6-10Z"/></svg>
        Desenvolvimento
      </a>
      <a href="#sobre" class="flex items-center gap-4 hover:text-accent">
        <svg class="h-5 w-5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/></svg>
        Sobre nós
      </a>
    </nav>
    <a href="/login.html" class="mt-auto self-end rounded-md bg-neutral-400 px-8 py-2 text-sm font-semibold text-neutral-900 hover:bg-neutral-300">Sair</a>
  </aside>

  <script>
    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('overlay');
    const open = () => { drawer.classList.remove('translate-x-full'); overlay.classList.remove('hidden'); };
    const close = () => { drawer.classList.add('translate-x-full'); overlay.classList.add('hidden'); };
    document.getElementById('menuBtn').addEventListener('click', open);
    document.getElementById('closeBtn').addEventListener('click', close);
    overlay.addEventListener('click', close);
  </script>
</body>
</html>
