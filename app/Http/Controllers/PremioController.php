<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PremioController extends Controller
{
    function index(){
        return view('premiacao.index');
    }
}
