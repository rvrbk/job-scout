@include('pdf.partials.document-start')
<body class="style-{{ $style }} layout-compact">
    @include('pdf.header')

    @if ($summary)
        <p class="summary">{{ $summary }}</p>
    @endif

    @include('pdf.partials.skills')
    @include('pdf.partials.experience')
    @include('pdf.partials.credentials')
</body>
</html>
