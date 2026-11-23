@php
    $fallbackSlug = request()->routeIs('pages.*') ? request()->route('slug') : null;
    $fallbackPage = $fallbackSlug ? config("landing.pages.{$fallbackSlug}") : null;
@endphp
@if (request()->routeIs('home') || $fallbackPage)
<noscript>
    <main>
@if ($fallbackPage)
        <h1>{{ $fallbackPage['heading'] }}</h1>
        <p>{{ $fallbackPage['intro'] }}</p>
@foreach ($fallbackPage['sections'] as $section)
        <h2>{{ $section['heading'] }}</h2>
        <p>{{ $section['body'] }}</p>
@endforeach
@else
        <h1>Applications that only say what you can back up</h1>
        <p>Find jobs that match your real experience, then draft a CV and cover letter where every claim traces back to a fact you confirmed.</p>
        <h2>How it works</h2>
@foreach (config('landing.steps') as $step)
        <h3>{{ $step['title'] }}</h3>
        <p>{{ $step['description'] }}</p>
@endforeach
        <h2>Questions</h2>
@foreach (config('landing.faqs') as $faq)
        <h3>{{ $faq['question'] }}</h3>
        <p>{{ $faq['answer'] }}</p>
@endforeach
@endif
        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('pages.how-it-works') }}">How it works</a>
            <a href="{{ route('pages.features') }}">Features</a>
            <a href="{{ route('pages.faq') }}">FAQ</a>
        </nav>
    </main>
</noscript>
@endif
