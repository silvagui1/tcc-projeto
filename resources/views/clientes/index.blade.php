@extends('layouts.app')

@section('titulo', 'Clientes')

@section('conteudo')
<div class="clientes-page" data-clientes-page>

    <header class="clientes-cabecalho">
        <form class="busca" data-busca-form role="search">
            <input
                type="search"
                name="nome"
                class="busca__campo"
                placeholder="buscar cliente por nome"
                autocomplete="off"
                data-busca-campo
            >
            <button type="submit" class="busca__botao" aria-label="Buscar cliente">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"/>
                    <path d="m20 20-3.2-3.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>

        <div class="acoes-rapidas">
            <button type="button" class="acao-rapida acao-rapida--azul" data-abrir-criar>
                <span class="acao-rapida__icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M3.5 19c0-2.9 2.46-5.25 5.5-5.25s5.5 2.35 5.5 5.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M18 8.5v5M15.5 11h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="acao-rapida__label">adicionar clientes</span>
            </button>

            <button type="button" class="acao-rapida acao-rapida--vermelho" data-alternar-selecao>
                <span class="acao-rapida__icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7h14M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M7 7l1 12.5A1.5 1.5 0 0 0 9.5 21h5a1.5 1.5 0 0 0 1.5-1.5L17 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="acao-rapida__label" data-label-selecao>apagar clientes</span>
            </button>
        </div>

        <div class="clientes-titulo">
            <h1>Clientes</h1>
            <span class="clientes-contador" data-contador>{{ $clientes->count() }} cadastrados</span>
        </div>
    </header>

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>

    <div class="clientes-lista-wrapper" data-lista-wrapper>
        @include('clientes.partials._lista', ['clientes' => $clientes])
    </div>

    <div class="barra-selecao" data-barra-selecao hidden>
        <span data-selecao-contagem>0 selecionados</span>
        <div class="barra-selecao__acoes">
            <button type="button" class="botao-texto" data-cancelar-selecao>Cancelar</button>
            <button type="button" class="botao botao--vermelho botao--pill" data-confirmar-exclusao>Excluir selecionados</button>
        </div>
    </div>
</div>

@include('clientes.partials._modal')
@endsection
