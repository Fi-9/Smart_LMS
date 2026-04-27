<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use App\Services\BookService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class QrStickerPageController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    public function index(Request $request): View
    {
        $rackId = $request->integer('rack_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        $books = Book::query()
            ->with(['rack:id,name', 'category:id,name'])
            ->when($rackId, fn ($query) => $query->where('rack_id', $rackId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->where(function ($q) {
                $q->whereNotNull('qr_code_path')->orWhereNotNull('qr_code');
            })
            ->orderBy('title')
            ->paginate(24)
            ->withQueryString();

        $missingCount = Book::query()
            ->when($rackId, fn ($query) => $query->where('rack_id', $rackId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->whereNull('qr_code_path')
            ->whereNull('qr_code')
            ->count();

        return view('qr.index', [
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'books' => $books,
            'preview_books' => Book::query()
                ->with(['rack:id,name'])
                ->where(function ($q) {
                    $q->whereNotNull('qr_code_path')->orWhereNotNull('qr_code');
                })
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(['id', 'title', 'rack_id', 'position_code', 'qr_code_path', 'qr_code']),
            'missing_count' => $missingCount,
            'selected_rack_id' => $rackId,
            'selected_category_id' => $categoryId,
        ]);
    }

    public function print(Request $request): View
    {
        $rackId = $request->integer('rack_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $selectedIds = collect($request->input('selected_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $books = Book::query()
            ->with(['rack:id,name', 'category:id,name'])
            ->when($rackId, fn ($query) => $query->where('rack_id', $rackId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($selectedIds !== [], fn ($query) => $query->whereIn('id', $selectedIds))
            ->where(function ($q) {
                $q->whereNotNull('qr_code_path')->orWhereNotNull('qr_code');
            })
            ->orderBy('title')
            ->get();

        $layout = $request->input('layout', 'default');
        $viewName = $layout === 'tj103' ? 'qr.print-tj103' : 'qr.print';

        return view($viewName, [
            'books' => $books,
        ]);
    }

    public function generateSingle(Book $book): JsonResponse
    {
        try {
            $base64 = $this->bookService->generateQrCode($book);

            return response()->json([
                'success' => true,
                'message' => "QR Code successfully generated for '{$book->title}'.",
                'qr_code' => $base64,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'QR generation failed on server.',
            ], 500);
        }
    }

    public function generateMissing(Request $request)
    {
        $rackId = $request->integer('rack_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $selectedIds = collect($request->input('selected_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $queued = $this->bookService->queueMissingQrCodes($rackId, $categoryId, $selectedIds);

        return redirect()
            ->route('qr.index', [
                'rack_id' => $rackId,
                'category_id' => $categoryId,
            ])
            ->with('toast', [
                'type' => $queued > 0 ? 'success' : 'info',
                'message' => $queued > 0
                    ? "Queued QR generation for {$queued} book(s)."
                    : 'No missing QR found for current scope.',
            ]);
    }
}
