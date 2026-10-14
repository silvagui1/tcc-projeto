<?php

namespace App\Http\Controllers;

/**
 * Controller da página inicial.
 *
 * A rota "/" em routes/web.php aponta para cá. O arquivo tinha sumido
 * da branch main (as rotas foram commitadas, os controllers não), e é
 * por isso que aparecia o erro:
 *
 *     Target class [App\Http\Controllers\Principal] does not exist.
 *
 * O nome do arquivo (Principal.php), o namespace (App\Http\Controllers)
 * e o nome da classe (Principal) precisam bater exatamente com o que
 * a rota pede — é assim que o autoload PSR-4 encontra a classe.
 */
class Principal extends Controller
{
    public function principal()
    {
        // welcome é a única view que existe hoje na main.
        // Quando as telas do TCC forem integradas aqui, troque por
        // view('pages.home') ou redirecione para a rota home.
        return view('welcome');
    }
}
