<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Print Layout</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-white p-6">
    <h1 class="mb-6 text-xl font-bold">QR Print Layout</h1>

    <div class="grid grid-cols-3 gap-4">
        @foreach($books as $book)
            <div class="rounded border border-slate-300 p-3 text-center">
                <img src="{{ $book->qr_code_path }}" alt="QR {{ $book->title }}" class="mx-auto mb-2 h-24 w-24 object-contain">
                <p class="text-xs font-semibold">{{ $book->title }}</p>
                <p class="text-xs text-slate-500">{{ $book->rack->name }} - {{ $book->position_code }}</p>
            </div>
        @endforeach
    </div>
</body>
</html>

