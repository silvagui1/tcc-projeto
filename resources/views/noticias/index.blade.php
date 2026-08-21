<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog da Academia — Fique por dentro das novidades</title>
  <meta name="description" content="Artigos sobre alimentação, saúde e musculação para turbinar seu treino." />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header class="header">
    <div class="container header-inner">
      <a class="logo" href="index.html">MAISQFITNESS</a>
      <nav class="nav">
        <a href="index.html" class="active">Blog</a>
        <a href="index.html">Planos</a>
        <a href="index.html">Sobre</a>
        <a href="index.html">Contato</a>
      </nav>
      <button class="btn-header">Matricule-se</button>
    </div>
  </header>

  <section class="hero">
    <img src="../img/hero-gym.jpg" alt="Interior de academia" />
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <p>Fique por dentro das</p>
      <h1>Novidades</h1>
    </div>
  </section>

  <section class="container">
    <div class="filters" id="filtros">
      <button class="chip active" data-cat="Todos">Todos</button>
      <button class="chip" data-cat="Alimentação">Alimentação</button>
      <button class="chip" data-cat="Saúde">Saúde</button>
      <button class="chip" data-cat="Musculação">Musculação</button>
    </div>

    <div class="search-row">
      <div class="search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" /></svg>
        <input type="search" id="busca" placeholder="Buscar artigos..." />
      </div>
      <button class="btn-primary">Veja mais</button>
    </div>
  </section>

  <main class="container">
    <div class="grid grid-2">
        <a class="card" href="alimentacao-e-academia.html" data-categoria="Alimentação" data-titulo="alimentação e academia aprenda a se organizar e ter uma rotina mais saudável.">
          <div class="card-img"><img src="../img/alimentacao.jpg" alt="Alimentação e Academia" /></div>
          <div class="card-body">
            <h3>Alimentação e Academia</h3>
            <p>Aprenda a se organizar e ter uma rotina mais saudável.</p>
            <div class="card-meta">
              <span>Publicação: 13/03/2026</span>
              <span class="tag alimentacao">Alimentação</span>
            </div>
          </div>
        </a>
        <a class="card" href="musculacao-iniciantes.html" data-categoria="Musculação" data-titulo="musculação para iniciantes como começar com segurança e evitar lesões no primeiro mês.">
          <div class="card-img"><img src="../img/musculacao.jpg" alt="Musculação para Iniciantes" /></div>
          <div class="card-body">
            <h3>Musculação para Iniciantes</h3>
            <p>Como começar com segurança e evitar lesões no primeiro mês.</p>
            <div class="card-meta">
              <span>Publicação: 17/02/2026</span>
              <span class="tag musculacao">Musculação</span>
            </div>
          </div>
        </a>
    </div>
    <div class="grid grid-3">
        <a class="card" href="saude-mental-exercicio.html" data-categoria="Saúde" data-titulo="exercício e saúde mental a relação entre treino e bem-estar psicológico comprovada.">
          <div class="card-img"><img src="../img/saude.jpg" alt="Exercício e Saúde Mental" /></div>
          <div class="card-body">
            <h3>Exercício e Saúde Mental</h3>
            <p>A relação entre treino e bem-estar psicológico comprovada.</p>
            <div class="card-meta">
              <span>Publicação: 05/02/2026</span>
              <span class="tag saude">Saúde</span>
            </div>
          </div>
        </a>
        <a class="card" href="pre-treino-necessario.html" data-categoria="Musculação" data-titulo="pré-treino é necessário? entenda quando suplementos realmente ajudam o desempenho.">
          <div class="card-img"><img src="../img/musculacao.jpg" alt="Pré-treino é necessário?" /></div>
          <div class="card-body">
            <h3>Pré-treino é necessário?</h3>
            <p>Entenda quando suplementos realmente ajudam o desempenho.</p>
            <div class="card-meta">
              <span>Publicação: 28/01/2026</span>
              <span class="tag musculacao">Musculação</span>
            </div>
          </div>
        </a>
        <a class="card" href="cardio-ou-musculacao.html" data-categoria="Musculação" data-titulo="cardio ou musculação? qual dos dois traz mais resultado para seu objetivo.">
          <div class="card-img"><img src="../img/musculacao.jpg" alt="Cardio ou Musculação?" /></div>
          <div class="card-body">
            <h3>Cardio ou Musculação?</h3>
            <p>Qual dos dois traz mais resultado para seu objetivo.</p>
            <div class="card-meta">
              <span>Publicação: 12/01/2026</span>
              <span class="tag musculacao">Musculação</span>
            </div>
          </div>
        </a>
        <a class="card" href="hidratacao-treino.html" data-categoria="Saúde" data-titulo="hidratação no treino quanta água beber durante e depois da atividade física.">
          <div class="card-img"><img src="../img/saude.jpg" alt="Hidratação no Treino" /></div>
          <div class="card-body">
            <h3>Hidratação no Treino</h3>
            <p>Quanta água beber durante e depois da atividade física.</p>
            <div class="card-meta">
              <span>Publicação: 03/01/2026</span>
              <span class="tag saude">Saúde</span>
            </div>
          </div>
        </a>
    </div>
    <p class="empty" id="vazio" hidden>Nenhum artigo encontrado para sua busca.</p>
  </main>

  <footer class="footer">
    <div class="container">© 2026 Logotipo da Academia. Todos os direitos reservados.</div>
  </footer>

  <script>
    const chips = document.querySelectorAll(".chip");
    const busca = document.getElementById("busca");
    const cards = document.querySelectorAll(".card");
    const vazio = document.getElementById("vazio");
    let categoria = "Todos";

    function filtrar() {
      const termo = busca.value.trim().toLowerCase();
      let visiveis = 0;
      cards.forEach((c) => {
        const okCat = categoria === "Todos" || c.dataset.categoria === categoria;
        const okBusca = termo === "" || c.dataset.titulo.includes(termo);
        const mostrar = okCat && okBusca;
        c.style.display = mostrar ? "" : "none";
        if (mostrar) visiveis++;
      });
      vazio.hidden = visiveis > 0;
    }

    chips.forEach((chip) => chip.addEventListener("click", () => {
      chips.forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      categoria = chip.dataset.cat;
      filtrar();
    }));
    busca.addEventListener("input", filtrar);
  </script>
</body>
</html>
