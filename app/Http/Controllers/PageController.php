<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The hand-written static pages.
 */
class PageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('Pages/About');
    }

    public function terms(): Response
    {
        return Inertia::render('Pages/Terms');
    }

    public function cookiePolicy(): Response
    {
        return Inertia::render('Pages/CookiePolicy');
    }
}
