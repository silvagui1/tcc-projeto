<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sua Academia</title>
  <meta name="description" content="Academia com musculação de qualidade, acompanhamento profissional e planos a partir de R$15 por dia. Sua melhor versão começa aqui." />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            background: '#212121',
            foreground: '#FAFAFA',
            card: '#303030',
            accent: '#F7C346',
            muted: '#303030',
            'muted-text': '#A3A3A3',
            'surface-light': '#F5F5F5',
            'surface-light-text': '#262626',
            secondary: '#2E2E2E',
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
        },
      },
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
  </style>
</head>
<body class="bg-background text-foreground antialiased">
  <header class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-6 py-6">
    <div class="flex items-center gap-3">
      <span class="flex h-9 w-9 items-center justify-center rounded-full bg-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 4h4M14 4h4M8 4v16M16 4v16M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="text-xs leading-tight text-muted-text">
        Logo/Logotipo da<br />Academia
      </span>
    </div>
    <nav class="mx-auto flex items-center gap-7 text-sm">
      <a href="#blog" class="transition-colors hover:text-accent">Blog</a>
      <a href="#sobre" class="transition-colors hover:text-accent">Sobre</a>
      <a href="#planos" class="transition-colors hover:text-accent">Planos</a>
    </nav>
    <div class="flex items-center gap-4 text-sm">
      <a href="#login" class="transition-colors hover:text-accent">Login</a>
      <a href="#planos" class="rounded-md bg-secondary px-4 py-2 transition-colors hover:bg-muted">Cadastre-se</a>
    </div>
  </header>

  <main>
    <section class="relative overflow-hidden">
      <div class="mx-auto grid max-w-6xl items-center gap-10 px-6 pb-10 pt-6 md:grid-cols-2">
        <div>
          <h1 class="text-4xl font-extrabold uppercase leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
            Sua melhor versão aqui
          </h1>
          <p class="mt-6 max-w-md text-sm leading-relaxed text-muted-text">
            Um espaço pensado para quem busca evolução de verdade. Aqui, cada detalhe foi criado para unir performance, bem-estar e constância, transformando treino em estilo de vida.
          </p>
          <a href="#planos" class="mt-8 inline-flex items-center gap-3 rounded-md bg-muted px-6 py-3 text-sm font-medium transition-colors hover:bg-secondary">
            Começar
            <svg class="h-4 w-4 rounded-full border border-current p-[2px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </div>
        <div class="relative">
          <div class="absolute inset-8 rotate-12 rounded-3xl bg-foreground/10 blur-2xl"></div>
          <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=600&q=80" alt="Atleta treinando bíceps com halter na academia" class="relative mx-auto w-full max-w-md object-contain" />
        </div>
      </div>

      <div class="mx-auto grid max-w-6xl gap-4 px-6 pb-20 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex items-center gap-2 rounded-md bg-card px-4 py-3 text-xs font-medium">
          <svg class="h-4 w-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4"/></svg>
          Atendimento Personalizado
        </div>
        <div class="flex items-center gap-2 rounded-md bg-card px-4 py-3 text-xs font-medium">
          <svg class="h-4 w-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Resultado dos Alunos
        </div>
        <div class="flex items-center gap-2 rounded-md bg-card px-4 py-3 text-xs font-medium">
          <svg class="h-4 w-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 4h4M14 4h4M8 4v16M16 4v16M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Musculação de Qualidade
        </div>
        <div class="flex items-center gap-2 rounded-md bg-card px-4 py-3 text-xs font-medium">
          <svg class="h-4 w-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Ajuda de Profissionais
        </div>
      </div>
    </section>

    <section id="sobre" class="border-b-2 border-accent pb-20">
      <div class="mx-auto grid max-w-6xl items-center gap-10 px-6 md:grid-cols-2">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full border border-white/10 px-3 py-1 text-xs">
            <svg class="h-3 w-3 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Resultado dos Alunos
          </span>
          <h2 class="mt-6 text-3xl font-bold leading-tight sm:text-4xl">
            Acompanhe a <span class="text-muted-text">evolução</span> de nossos alunos
          </h2>
          <p class="mt-5 max-w-md text-sm leading-relaxed text-muted-text">
            Um espaço pensado para quem busca evolução de verdade. Aqui, cada detalhe foi criado para unir performance, bem-estar e constância, transformando treino em estilo de vida.
          </p>
        </div>

        <div>
          <div class="grid grid-cols-2 gap-4">
            <figure class="relative overflow-hidden rounded-lg">
              <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=400&q=80" alt="Aluno Pedro Silva treinando" class="h-72 w-full object-cover" />
              <figcaption class="absolute left-3 top-3 rounded-full bg-surface-light px-3 py-1 text-xs font-semibold text-surface-light-text">Pedro Silva, 33 anos</figcaption>
            </figure>
            <figure class="relative overflow-hidden rounded-lg opacity-70">
              <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=400&q=80" alt="Aluna Aline Carvalho treinando" class="h-72 w-full object-cover" />
              <figcaption class="absolute left-3 top-3 rounded-full bg-surface-light px-3 py-1 text-xs font-semibold text-surface-light-text">Aline Carvalho, 31 anos</figcaption>
            </figure>
          </div>
          <div class="mt-5 flex justify-center gap-4">
            <button type="button" aria-label="Anterior" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 transition-colors hover:bg-secondary">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" aria-label="Próximo" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 transition-colors hover:bg-secondary">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </section>

    <section id="planos" class="bg-surface-light py-20 text-surface-light-text">
      <div class="mx-auto max-w-6xl px-6">
        <span class="inline-block rounded-full bg-background px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-foreground">Nossos preços</span>
        <h2 class="mt-5 text-3xl font-bold sm:text-4xl">Os melhores preços são</h2>
        <p class="text-3xl font-bold text-surface-light-text/30 sm:text-4xl">conosco</p>
        <p class="mt-3 text-sm text-surface-light-text/70">Sem taxa de matrícula em todos os planos. Cancele quando quiser.</p>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
          <div class="relative">
            <span class="absolute -top-4 left-6 z-10 inline-flex items-center gap-2 rounded-full bg-background px-4 py-2 text-xs font-semibold text-foreground">
              Melhor Custo
            </span>
            <div class="h-full rounded-2xl bg-surface-light-text/20 p-6 text-surface-light-text">
              <p class="text-sm opacity-70">plano</p>
              <p class="text-2xl font-bold">Anual</p>
              <p class="mt-3 flex items-baseline gap-1">
                <span class="text-sm align-super">R$</span>
                <span class="text-4xl font-bold">100</span>
                <span class="text-xs opacity-70">/ mês</span>
              </p>
              <hr class="my-5 border-current opacity-20" />
              <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>acesso à musculação top</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>site de treinos premium</li>
              </ul>
              <a href="#cadastro" class="mt-8 flex items-center justify-center gap-2 rounded-md bg-background px-5 py-3 text-sm font-semibold text-foreground transition-opacity hover:opacity-90">
                Matricular-se
                <svg class="h-4 w-4 rounded-full border border-current p-[2px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </a>
            </div>
          </div>

          <div class="relative">
            <div class="h-full rounded-2xl bg-background p-6 text-foreground">
              <p class="text-sm opacity-70">plano</p>
              <p class="text-2xl font-bold">Mensal</p>
              <p class="mt-3 flex items-baseline gap-1">
                <span class="text-sm align-super">R$</span>
                <span class="text-4xl font-bold">115</span>
                <span class="text-xs opacity-70">p/ mês</span>
              </p>
              <hr class="my-5 border-current opacity-20" />
              <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>acesso à musculação</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>site de treinos</li>
              </ul>
              <a href="#cadastro" class="mt-8 flex items-center justify-center gap-2 rounded-md bg-surface-light-text/40 px-5 py-3 text-sm font-semibold text-surface-light transition-opacity hover:opacity-90">
                Matricular-se
                <svg class="h-4 w-4 rounded-full border border-current p-[2px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </a>
            </div>
          </div>

          <div class="relative">
            <div class="h-full rounded-2xl bg-background p-6 text-foreground">
              <p class="text-sm opacity-70">plano</p>
              <p class="text-2xl font-bold">Diaria</p>
              <p class="mt-3 flex items-baseline gap-1">
                <span class="text-sm align-super">R$</span>
                <span class="text-4xl font-bold">15</span>
                <span class="text-xs opacity-70">p/ dia</span>
              </p>
              <hr class="my-5 border-current opacity-20" />
              <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>acesso á equipamentos</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>Acesso ao blog de treinos</li>
              </ul>
              <a href="#cadastro" class="mt-8 flex items-center justify-center gap-2 rounded-md bg-surface-light-text/40 px-5 py-3 text-sm font-semibold text-surface-light transition-opacity hover:opacity-90">
                Matricular-se
                <svg class="h-4 w-4 rounded-full border border-current p-[2px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="border-b-2 border-accent py-24 text-center">
      <div class="mx-auto max-w-3xl px-6">
        <h2 class="text-3xl font-extrabold uppercase leading-tight sm:text-4xl">
          Chega de espera, comece sua <span class="text-muted-text">transformação!</span>
        </h2>
        <a href="#planos" class="mt-8 inline-flex items-center gap-3 rounded-md bg-muted px-6 py-3 text-sm font-medium transition-colors hover:bg-secondary">
          Começar Agora
          <svg class="h-4 w-4 rounded-full border border-current p-[2px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <p class="mt-16 flex items-center justify-center gap-2 text-xs text-muted-text">
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <a href="tel:+551111111111" class="underline">11 11111-1111</a>
        </p>
      </div>
    </section>
  </main>

  <footer class="bg-surface-light py-14 text-surface-light-text">
    <div class="mx-auto max-w-3xl px-6 text-center">
      <nav class="flex flex-wrap justify-center gap-6 text-sm">
        <a href="#endereco">Endereço</a>
        <a href="#blog">Blog</a>
        <a href="#redes">Redes Sociais</a>
        <a href="#suporte">Suporte</a>
        <a href="#contato">Contato</a>
      </nav>
      <hr class="mt-4 border-surface-light-text/30" />
      <p class="mt-8 text-sm">Academia © 2026</p>
    </div>
  </footer>
</body>
</html>