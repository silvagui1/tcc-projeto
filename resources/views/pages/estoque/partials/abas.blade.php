{{-- Abas do estoque: a de produtos é pages/estoque/index; a de cartas, o
     catálogo (pages/estoque/cartas). $aba = 'produtos' | 'cartas'. --}}
<nav class="tabs" aria-label="Seções do estoque">
    <a href="{{ route('estoque.index') }}" class="{{ $aba === 'produtos' ? 'is-active' : '' }}" @if ($aba === 'produtos') aria-current="page" @endif>
        <i class="bi bi-box-seam" aria-hidden="true"></i> Produtos
    </a>
    <a href="{{ route('estoque.cartas') }}" class="{{ $aba === 'cartas' ? 'is-active' : '' }}" @if ($aba === 'cartas') aria-current="page" @endif>
        <i class="bi bi-stack" aria-hidden="true"></i> Cartas avulsas
    </a>
</nav>
