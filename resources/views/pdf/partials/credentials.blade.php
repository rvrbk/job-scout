@if (count($credentials))
    <h2>{{ __('Education & certifications') }}</h2>
    @foreach ($credentials as $credential)
        <div class="credential">
            @if ($credential['period'])<span class="period">{{ $credential['period'] }}</span>@endif
            <strong>@if ($credential['url'])<a href="{{ $credential['url'] }}">{{ $credential['title'] }}</a>@else{{ $credential['title'] }}@endif</strong>@if ($credential['organization']) <span class="muted">&middot; {{ $credential['organization'] }}</span>@endif
        </div>
    @endforeach
@endif
