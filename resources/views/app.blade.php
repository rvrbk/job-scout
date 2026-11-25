<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        @php($gaId = config('services.google_analytics.measurement_id'))
        {{-- Page views are sent from the Inertia navigate event in app.ts. --}}
        {{-- Uses the app's own design tokens, so it follows light and dark mode. --}}
        @include('partials.cookie-consent', [
            'gaId' => $gaId,
            'autoPageView' => false,
            'theme' => [
                '--cc-bg' => 'var(--card)',
                '--cc-fg' => 'var(--card-foreground)',
                '--cc-border' => 'var(--border)',
                '--cc-radius' => 'var(--radius)',
                '--cc-btn-radius' => 'calc(var(--radius) - 2px)',
                '--cc-font' => "var(--font-sans, 'Ubuntu', ui-sans-serif, system-ui, sans-serif)",
                '--cc-accent' => 'var(--primary)',
                '--cc-accent-fg' => 'var(--primary-foreground)',
                '--cc-border-strong' => 'var(--border)',
                '--cc-shadow' => '0 10px 30px hsl(0 0% 0% / .14)',
            ],
        ])
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: hsl(180 20% 99%);
            }

            html.dark {
                background-color: hsl(200 12% 7%);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @include('partials.seo')

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Job Scout') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @include('partials.seo-fallback')
    </body>
</html>
