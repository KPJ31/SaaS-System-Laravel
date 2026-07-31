<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function landing(): View
    {
        return view('public.landing');
    }
}
