<?php

namespace Pitbphp\Security\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('security::home');
    }
}
