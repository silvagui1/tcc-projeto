<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Principal extends Controller
{
    function principal(){
        return view('principal.index');
    }

    function menu(){
        return view('menu');
    }
    
}