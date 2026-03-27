<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Rack;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class QrStickerPageController extends Controller
{
    public function index(Request $request): View
    {
        $rackId = $request->integer('rack_id') ?: null;

        $books = Book::query()
            ->with(['rack:id,name'])
            ->when($rackId, fn ($query) => $query->where('rack_id', $rackId))
            ->whereNotNull('qr_code_path')
            ->orderBy('title')
            ->paginate(24)
            ->withQueryString();

        return view('qr.index', [
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
            'books' => $books,
            'selected_rack_id' => $rackId,
        ]);
    }

    public function print(Request $request): View
    {
        $rackId = $request->integer('rack_id') ?: null;

        $books = Book::query()
            ->with(['rack:id,name'])
            ->when($rackId, fn ($query) => $query->where('rack_id', $rackId))
            ->whereNotNull('qr_code_path')
            ->orderBy('title')
            ->get();

        return view('qr.print', [
            'books' => $books,
        ]);
    }
}

