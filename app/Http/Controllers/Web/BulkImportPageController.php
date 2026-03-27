<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImportCommitRequest;
use App\Http\Requests\BulkImportPreviewRequest;
use App\Services\BulkImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BulkImportPageController extends Controller
{
    public function __construct(
        private readonly BulkImportService $bulkImportService
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
}
