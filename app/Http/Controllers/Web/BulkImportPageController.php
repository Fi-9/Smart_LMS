<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImportCommitRequest;
use App\Http\Requests\BulkImportPreviewRequest;
use App\Http\Requests\LookupIsbnRequest;
use App\Http\Requests\StoreManualBookRequest;
use App\Models\Category;
use App\Models\Rack;
use App\Services\BookService;
use App\Services\BulkImportService;
use App\Services\IsbnLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkImportPageController extends Controller
{
    public function __construct(
        private readonly BulkImportService $bulkImportService,
        private readonly BookService $bookService,
        private readonly IsbnLookupService $isbnLookupService
    ) {
    }

    public function index(Request $request): View
    {
        return $this->view($request);
    }

    public function view(Request $request): View
    {
        return view('books.import', [
            'preview' => $request->session()->get('bulk_import_preview'),
            'import_summary' => $request->session()->get('bulk_import_summary'),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function preview(BulkImportPreviewRequest $request)
    {
        $result = $this->bulkImportService->preview($request->file('file'));

        $request->session()->flash('bulk_import_preview', $result);
        $request->session()->forget('bulk_import_summary');

        return redirect()->route('books.import')->with(
            'toast',
            ['type' => 'info', 'message' => 'Preview generated. Review rows before import.']
        );
    }

    public function commit(BulkImportCommitRequest $request)
    {
        $result = $this->bulkImportService->commit($request->validated('preview_token'));

        if ($result['message'] === 'Preview token is invalid or expired.') {
            return redirect()->route('books.import')->with(
                'toast',
                ['type' => 'error', 'message' => $result['message']]
            );
        }

        $request->session()->flash('bulk_import_summary', $result);
        $request->session()->forget('bulk_import_preview');

        return redirect()->route('books.import')->with(
            'toast',
            ['type' => 'success', 'message' => 'Import finished successfully.']
        );
    }

    public function storeManual(StoreManualBookRequest $request)
    {
        $attributes = $request->validated();
        
        $category = Category::firstOrCreate(['name' => $attributes['category_name']]);
        $attributes['category_id'] = $category->id;
        unset($attributes['category_name']);

        $this->bookService->createManual($attributes);

        return redirect()->route('books.import')->with(
            'toast',
            ['type' => 'success', 'message' => 'Manual book input saved.']
        );
    }

    public function lookupIsbn(LookupIsbnRequest $request): JsonResponse
    {
        $result = $this->isbnLookupService->lookup($request->validated('isbn'));

        if (! $result) {
            return response()->json([
                'message' => 'No book metadata found for the given ISBN.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json($result);
    }
}
