@php
    $siteName = config('app.name', 'Job Scout');
    $indexable = request()->routeIs('home');
    $title = 'Applications that only say what you can back up';
    $description = 'Find jobs that match your real experience, or paste or link any vacancy, then draft a CV and cover letter in your own tone where every claim traces back to a fact you confirmed.';
    $canonical = $indexable ? route('home') : url()->current();
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
                    'Choice of PDF style and tone, or matching your own writing sample',
                    'Review and approval gate before export as PDF or Markdown',
                ],
            ],
        ],
    ];
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
