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

    <div class="letter">
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</body>
</html>
