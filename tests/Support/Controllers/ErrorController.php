<?php

namespace Tests\Support\Controllers;

use Illuminate\Routing\Controller;

class ErrorController extends Controller
{
    function index()
    {
        $something->doesntExists();
        return 'ok';
    }
}
