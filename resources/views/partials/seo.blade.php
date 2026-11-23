@php
    $siteName = config('app.name', 'Job Scout');
    $isHome = request()->routeIs('home');
    $slug = request()->routeIs('pages.*') ? request()->route('slug') : null;
    $publicPage = $slug ? config("landing.pages.{$slug}") : null;
    $indexable = $isHome || $publicPage !== null;
    $title = $publicPage['title'] ?? 'Applications that only say what you can back up';
    $description = $publicPage['description'] ?? 'Find jobs that match your real experience, or paste or link any vacancy, then draft a CV and cover letter in your own tone where every claim traces back to a fact you confirmed.';
    $canonical = $isHome ? route('home') : url()->current();
    $image = url('og-image.png');
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => route('home').'#organization',
                'name' => 'verbeek.ug',
                'url' => 'https://verbeek.ug/',
            ],
            [
                '@type' => 'WebSite',
                '@id' => route('home').'#website',
                'url' => route('home'),
                'name' => $siteName,
                'description' => $description,
                'inLanguage' => 'en',
                'publisher' => ['@id' => route('home').'#organization'],
            ],
            [
                '@type' => 'WebApplication',
                '@id' => route('home').'#app',
                'name' => $siteName,
                'url' => route('home'),
                'description' => $description,
                'applicationCategory' => 'BusinessApplication',
                'applicationSubCategory' => 'Job search and CV writing',
                'isAccessibleForFree' => false,
                'screenshot' => $image,
                'operatingSystem' => 'Any',
                'browserRequirements' => 'Requires JavaScript',
                'image' => $image,
                'publisher' => ['@id' => route('home').'#organization'],
                'featureList' => [
                    'Fact ledger of confirmed experience',
                    'Job search scored against your ledger',
                    'Add a vacancy by pasting its text or linking to it',
                    'CV and cover letter drafts with every claim checked against confirmed facts',
                    'General CV and cover letter drafted from the ledger without a job',
                    'Choice of PDF colour style, font and tone, or matching your own writing sample',
                    'Review and approval gate before export as PDF or Markdown',
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => route('home').'#faq',
                'url' => route('home'),
                'inLanguage' => 'en',
                'mainEntity' => collect(config('landing.faqs'))->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ])->all(),
            ],
            [
                '@type' => 'HowTo',
                '@id' => route('home').'#how-it-works',
                'name' => 'How to write a job application with Job Scout',
                'description' => 'Build a ledger of confirmed facts, find a job, and draft a CV or cover letter that only says what you can back up.',
                'inLanguage' => 'en',
                'step' => collect(config('landing.steps'))->values()->map(fn ($step, $i) => [
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'name' => $step['title'],
                    'text' => $step['description'],
                    'url' => route('home').'#how-it-works',
                ])->all(),
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_values(array_filter([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $siteName, 'item' => route('home')],
                    $publicPage ? ['@type' => 'ListItem', 'position' => 2, 'name' => $publicPage['title'], 'item' => $canonical] : null,
                ])),
            ],
        ],
    ];

    if ($publicPage) {
        $homeId = route('home');
        $pageNode = [
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $publicPage['title'],
            'description' => $description,
            'inLanguage' => 'en',
            'isPartOf' => ['@id' => $homeId.'#website'],
        ];
        $nodes = collect($schema['@graph']);
        $keep = $nodes->filter(fn ($node) => in_array($node['@type'], ['Organization', 'WebSite', 'BreadcrumbList'], true));
        $extra = match ($slug) {
            'faq' => $nodes->where('@type', 'FAQPage')->map(fn ($node) => [...$node, '@id' => $canonical.'#faq', 'url' => $canonical]),
            'how-it-works' => $nodes->where('@type', 'HowTo')->map(fn ($node) => [...$node, '@id' => $canonical.'#how-it-works']),
            default => $nodes->where('@type', 'WebApplication'),
        };
        $schema['@graph'] = $keep->push($pageNode)->merge($extra)->values()->all();
    }
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">
<meta name="theme-color" content="#0f766e">
<link rel="canonical" href="{{ $canonical }}">
<link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
<link rel="alternate" type="text/plain" href="{{ route('llms') }}" title="llms.txt">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="{{ $siteName }} - {{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $siteName }}: {{ $title }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $siteName }} - {{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $siteName }}: {{ $title }}">

@if ($indexable)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
