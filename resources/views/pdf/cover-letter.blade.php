<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $name }}</title>
    <style>@include('pdf.styles')</style>
</head>
<body class="style-{{ $style }}">
    @include('pdf.header')

    <div class="letter">
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</body>
</html>
