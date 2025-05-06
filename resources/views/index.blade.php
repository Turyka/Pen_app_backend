<!DOCTYPE html>
<html>
<head>
    <title>Hírek</title>
</head>
<body>
    <h1>Hírek</h1>

    @foreach ($hirek as $hir)
        <div style="margin-bottom: 20px;">
            <h2>{{ $hir->title }}</h2>
            <p><a href="{{ $hir->link }}" target="_blank">Link</a></p>
            @if ($hir->image)
                <img src="{{ $hir->image }}" style="max-width: 300px;">
            @endif
        </div>
    @endforeach
</body>
</html>