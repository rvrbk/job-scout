<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /$',
            'Allow: /llms.txt',
            'Allow: /build/',
            'Allow: /og-image.png',
            'Disallow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('seo.sitemap', ['urls' => [route('home')]])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function llms(): Response
    {
        return response()
            ->view('seo.llms', ['url' => route('home'), 'name' => config('app.name')])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
