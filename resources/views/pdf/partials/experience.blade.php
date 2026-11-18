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
