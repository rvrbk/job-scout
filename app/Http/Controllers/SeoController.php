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
            'Allow: /how-it-works$',
            'Allow: /features$',
            'Allow: /faq$',
            'Allow: /llms.txt',
            'Allow: /build/',
            'Allow: /og-image.png',
            'Allow: /favicon.ico',
            'Allow: /favicon.svg',
            'Allow: /apple-touch-icon.png',
            'Disallow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('seo.sitemap', ['urls' => [
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('pages.how-it-works'), 'priority' => '0.8'],
                ['loc' => route('pages.features'), 'priority' => '0.8'],
                ['loc' => route('pages.faq'), 'priority' => '0.7'],
            ], 'lastmod' => date('Y-m-d', (int) filemtime(base_path('config/landing.php')))])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function llms(): Response
    {
        return response()
            ->view('seo.llms', ['url' => route('home'), 'name' => config('app.name'), 'pages' => config('landing.pages')])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
