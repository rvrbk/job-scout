<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $page = config("landing.pages.{$slug}");

        abort_if($page === null, 404);

        return Inertia::render('PublicPage', ['page' => ['slug' => $slug, ...$page]]);
    }
}
