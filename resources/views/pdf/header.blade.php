@if (($layout ?? 'classic') === 'sidebar')
    {{-- Cover letter in the sidebar layout: the name on the left, the contact details on the right. --}}
    <table class="header header-split"><tr>
        <td class="header-name"><h1>{{ $name }}</h1></td>
        <td class="header-contact contact">
            @foreach ($contact as $part)<div>{{ $part }}</div>@endforeach
            @foreach ($links as $link)<div><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></div>@endforeach
        </td>
    </tr></table>
    <div class="rule"></div>
@else
<div class="header">
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
</div>
<div class="rule"></div>
@endif
