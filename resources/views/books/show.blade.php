@extends('layouts.app')

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('books.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to Books</a>
        <a href="{{ route('books.public.show', $book->id) }}" target="_blank" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Open Public View</a>
    </div>

    @include('books.partials.detail_panel', ['book' => $book, 'rack_mini_map' => $rack_mini_map ?? null])
@endsection
