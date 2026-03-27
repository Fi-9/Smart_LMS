<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $book->title }} - Smart Library</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 p-6 text-slate-900">
    <main class="mx-auto max-w-3xl rounded-xl bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold">{{ $book->title }}</h1>
        <p class="mt-1 text-slate-600">Author: {{ $book->author }}</p>
        <dl class="mt-6 space-y-3">
            <div>
                <dt class="text-sm text-slate-500">ISBN</dt>
                <dd class="font-medium">{{ $book->isbn ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Category</dt>
                <dd class="font-medium">{{ $book->category->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Rack</dt>
                <dd class="font-medium">{{ $book->rack->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Position</dt>
                <dd class="font-medium">{{ $book->position_code }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Status</dt>
                <dd class="font-medium uppercase">{{ $book->status->value }}</dd>
            </div>
        </dl>
    </main>
</body>
</html>

