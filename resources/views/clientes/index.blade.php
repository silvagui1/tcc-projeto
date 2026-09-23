@extends('layouts.app')

@section('title', 'Clientes')

{{-- no desktop a lista vira um formato mais denso, tipo tabela, e aproveita
     uma área mais larga (ver .app-content--wide e .clientes-lista no CSS) --}}
@section('main_class', 'app-content--wide')

@section('content')
<div class="clientes-page" data-clientes-page>

    <div class="page-topbar">
        <p class="page-topbar__title">Clientes</p>
        <span class="clientes-contador" data-contador>{{ $clientes->total() }} cadastrados</span>
    </div>

    {{-- No mobile, busca e ações ficam empilhadas (uma embaixo da outra). No
         desktop (.clientes-toolbar a partir de 992px) ficam lado a lado numa
         barra só, como a busca+abas de estoque em mobilenav_atualizado. --}}
    <div class="clientes-toolbar">
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
                <i class="bi bi-search"></i>
            </button>
        </form>

        <div class="acoes-rapidas">
            <button type="button" class="acao-rapida acao-rapida--principal" data-abrir-criar>
                <span class="acao-rapida__icone" aria-hidden="true">
                    <i class="bi bi-person-plus-fill"></i>
                </span>
                <span class="acao-rapida__label">adicionar clientes</span>
            </button>

            <button type="button" class="acao-rapida acao-rapida--perigo" data-alternar-selecao>
                <span class="acao-rapida__icone" aria-hidden="true">
                    <i class="bi bi-trash-fill"></i>
                </span>
                <span class="acao-rapida__label" data-label-selecao>apagar clientes</span>
            </button>
        </div>
    </div>

    <div class="mensagem-flutuante" data-mensagem hidden role="status"></div>

    <div class="clientes-lista-wrapper" data-lista-wrapper>
        @include('clientes.partials._lista', ['clientes' => $clientes])
    </div>

    <div class="barra-selecao" data-barra-selecao hidden>
        <span data-selecao-contagem>0 selecionados</span>
        <div class="barra-selecao__acoes">
            <button type="button" class="botao-texto" data-cancelar-selecao>Cancelar</button>
            <button type="button" class="botao botao--perigo botao--pill" data-confirmar-exclusao>Excluir selecionados</button>
        </div>
    </div>
</div>

@include('clientes.partials._modal_criar')
@include('clientes.partials._modal_editar')
@endsection
