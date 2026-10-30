<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $name }}</title>
    <style>@include('pdf.styles')</style>
</head>
<body>
    <h1>{{ $name }}</h1>
    @if (count($contact) || count($links))
        <div class="contact">
            @if (count($contact))
                <div>{!! implode('<span class="sep">|</span>', array_map(fn ($part) => e($part), $contact)) !!}</div>
            @endif
            @if (count($links))
                <div>@foreach ($links as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@if (! $loop->last)<span class="sep">|</span>@endif @endforeach</div>
            @endif
        </div>
    @endif
    <div class="rule"></div>

    @if ($summary)
        <p class="summary">{{ $summary }}</p>
    @endif

    @if (count($experience))
        <h2>{{ __('Experience') }}</h2>
        @foreach ($experience as $entry)
            <div class="entry">
                <div class="entry-head">
                    @if ($entry['period'])<span class="period">{{ $entry['period'] }}</span>@endif
                    <span class="entry-title">@if ($entry['url'])<a href="{{ $entry['url'] }}">{{ $entry['title'] }}</a>@else{{ $entry['title'] }}@endif</span>@if ($entry['organization']) <span class="muted">&middot;</span> <span class="entry-org">{{ $entry['organization'] }}</span>@endif
                </div>
                <ul>
                    @foreach ($entry['bullets'] as $bullet)
                        <li>{{ $bullet['text'] }}@if ($bullet['url']) <a href="{{ $bullet['url'] }}">{{ __('source') }}</a>@endif</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @endif

    @if (count($skills))
        <h2>{{ __('Skills') }}</h2>
        <div>
            @foreach ($skills as $skill)<span class="chip">{{ $skill }}</span> @endforeach
        </div>
    @endif

    @if (count($credentials))
        <h2>{{ __('Education & certifications') }}</h2>
        @foreach ($credentials as $credential)
            <div class="credential">
                @if ($credential['period'])<span class="period">{{ $credential['period'] }}</span>@endif
                <strong>@if ($credential['url'])<a href="{{ $credential['url'] }}">{{ $credential['title'] }}</a>@else{{ $credential['title'] }}@endif</strong>@if ($credential['organization']) <span class="muted">&middot; {{ $credential['organization'] }}</span>@endif
            </div>
        @endforeach
    @endif
</body>
</html>
