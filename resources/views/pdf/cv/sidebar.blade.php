@include('pdf.partials.document-start')
<body class="style-{{ $style }} layout-sidebar">
    <div class="side-bg"></div>

    <div class="side">
        <h1>{{ $name }}</h1>

        @if (count($contact) || count($links))
            <h2>{{ __('Contact') }}</h2>
            <div class="contact">
                @foreach ($contact as $part)<div>{{ $part }}</div>@endforeach
                @foreach ($links as $link)<div><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></div>@endforeach
            </div>
        @endif

        @include('pdf.partials.skills')
        @include('pdf.partials.credentials')
    </div>

    <div class="main">
        @if ($summary)
            <p class="summary">{{ $summary }}</p>
        @endif

        @include('pdf.partials.experience')
    </div>
</body>
</html>
