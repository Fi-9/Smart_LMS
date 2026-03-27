@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Import Books</h1>
        <p class="mt-1 text-sm text-slate-500">Upload CSV, review invalid rows, lalu confirm import</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('books.import.preview') }}" enctype="multipart/form-data" class="space-y-3" data-loading-form>
            @csrf
            <input type="file" name="file" class="block w-full rounded-md border border-gray-300 p-2 text-sm" required>
            <progress class="hidden h-2 w-full overflow-hidden rounded bg-slate-100 [&::-webkit-progress-bar]:bg-slate-100 [&::-webkit-progress-value]:bg-slate-700 [&::-moz-progress-bar]:bg-slate-700" max="100"></progress>
            <x-button type="submit">Upload &amp; Preview</x-button>
        </form>
    </x-card>

    @if($import_summary)
        <div class="mt-6 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            Imported: {{ $import_summary['imported'] }} | Skipped: {{ $import_summary['skipped'] }}
        </div>
    @endif

    @if($preview)
        <x-card class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold">Preview</h2>
                <p class="text-xs text-slate-500">
                    Total: {{ $preview['summary']['total_rows'] }} |
                    Valid: {{ $preview['summary']['valid_rows'] }} |
                    Invalid: {{ $preview['summary']['invalid_rows'] }}
                </p>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="p-2 text-left">Row</th>
                            <th class="p-2 text-left">Title</th>
                            <th class="p-2 text-left">Author</th>
                            <th class="p-2 text-left">Status</th>
                            <th class="p-2 text-left">Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['analyzed_rows'] as $row)
                            <tr class="border-t border-slate-100 {{ $row['is_valid'] ? 'bg-white' : 'bg-red-50' }}">
                                <td class="p-2">{{ $row['row'] }}</td>
                                <td class="p-2">{{ $row['data']['title'] }}</td>
                                <td class="p-2">{{ $row['data']['author'] }}</td>
                                <td class="p-2">
                                    @if($row['is_valid'])
                                        <span class="text-green-600">Valid</span>
                                    @else
                                        <span class="text-red-600">Error</span>
                                    @endif
                                </td>
                                <td class="p-2 text-red-700">{{ $row['errors'] ? implode('; ', $row['errors']) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('books.import.commit') }}" class="mt-4">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $preview['preview_token'] }}">
                <x-button type="submit" variant="success" :disabled="$preview['summary']['valid_rows'] === 0">Confirm Import</x-button>
            </form>
        </x-card>
    @endif
@endsection
