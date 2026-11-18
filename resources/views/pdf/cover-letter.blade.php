@include('pdf.partials.document-start')
<body class="style-{{ $style }} layout-{{ $layout }}">
    @include('pdf.header')

    <div class="letter">
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</body>
</html>
