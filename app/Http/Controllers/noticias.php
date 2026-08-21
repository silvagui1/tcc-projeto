<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Blog extends Controller
{
    function blog(){
        return view('blog.index');
    }

    
    
}