<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookPublicController;
use App\Http\Controllers\QrScannerController;
use App\Http\Controllers\Web\BookAssignmentController;
use App\Http\Controllers\Web\BookPageController;
use App\Http\Controllers\Web\BorrowingController;
use App\Http\Controllers\Web\BulkImportPageController;
use App\Http\Controllers\Web\CategoryPageController;
use App\Http\Controllers\Web\DashboardPageController;
use App\Http\Controllers\Web\MemberPageController;
use App\Http\Controllers\Web\QrStickerPageController;
use App\Http\Controllers\Web\RackPageController;
use App\Http\Controllers\Web\RoomPageController;
use App\Http\Controllers\Web\MobileScanController;
use App\Http\Controllers\Web\SettingsPageController;
use App\Http\Controllers\Web\AiObservabilityController;
use Illuminate\Support\Facades\Route;

// ── Guest — login page ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// ── Shared: scanner dashboard (admin + staff) + logout ──
Route::middleware(['auth'])->group(function () {
    Route::get('/book-scanner', [MobileScanController::class, 'index'])->name('book-scanner.index');
    Route::post('/book-scanner/start', [MobileScanController::class, 'startSession'])->name('book-scanner.start');
    Route::post('/book-scanner/end', [MobileScanController::class, 'endSession'])->name('book-scanner.end');
    Route::post('/book-scanner/isbn', [MobileScanController::class, 'lookupIsbn'])->name('book-scanner.isbn');
    Route::post('/book-scanner/lookup-title', [MobileScanController::class, 'lookupTitle'])->name('book-scanner.lookup-title');
    Route::post('/book-scanner/cover', [MobileScanController::class, 'scanCover'])->name('book-scanner.cover');
    Route::post('/book-scanner/enqueue', [MobileScanController::class, 'enqueueScan'])->name('book-scanner.enqueue');
    Route::get('/book-scanner/queue-status', [MobileScanController::class, 'queueStatus'])->name('book-scanner.queue-status');
    Route::post('/book-scanner/retry/{job}', [MobileScanController::class, 'retryJob'])->name('book-scanner.retry');
    Route::post('/book-scanner/save-inbox', [MobileScanController::class, 'saveToInbox'])->name('book-scanner.save-inbox');
    Route::delete('/book-scanner/inbox/{inbox}', [MobileScanController::class, 'deleteInbox'])->name('book-scanner.inbox.destroy');
    Route::get('/book-scanner/stats', [MobileScanController::class, 'todayStats'])->name('book-scanner.stats');
    Route::get('/book-scanner/logs', function() {
        $logs = [];
        $paths = [
            'queue' => '/var/www/html/storage/logs/queue-worker.log',
            'scheduler' => '/var/www/html/storage/logs/scheduler.log',
            'laravel' => '/var/www/html/storage/logs/laravel.log',
        ];
        
        // Inline helper function
        $tail_file = function($filepath, $lines = 100) {
            if (!file_exists($filepath)) return "";
            $f = fopen($filepath, "r");
            if (!$f) return "";
            $buffer = [];
            while (($line = fgets($f)) !== false) {
                $buffer[] = $line;
                if (count($buffer) > $lines) {
                    array_shift($buffer);
                }
            }
            fclose($f);
            return implode("", $buffer);
        };

        foreach ($paths as $name => $path) {
            if (file_exists($path)) {
                $logs[$name] = [
                    'exists' => true,
                    'size' => filesize($path),
                    'content' => $tail_file($path, 100),
                ];
            } else {
                $logs[$name] = [
                    'exists' => false,
                ];
            }
        }
        
        $processes = [];
        exec('ps aux', $processes);
        
        return response()->json([
            'logs' => $logs,
            'processes' => $processes,
        ]);
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// ── Admin-only: staff auto-redirected to scanner ──
Route::middleware(['auth', 'role:admin,staff', 'staff.scanner'])->group(function () {
    Route::get('/', [DashboardPageController::class, 'view'])->name('dashboard');
    Route::get('/dashboard', [DashboardPageController::class, 'view']);
    Route::get('/books', [BookPageController::class, 'indexView'])->name('books.index');
    Route::post('/books/assign', [BookAssignmentController::class, 'store'])->name('books.assign');
    Route::post('/books/auto-assign', [BookAssignmentController::class, 'autoAssign'])->name('books.auto-assign');
    Route::get('/books/import', [BulkImportPageController::class, 'view'])->name('books.import');
    Route::post('/books/import/route-books', [BulkImportPageController::class, 'routeBooks'])->name('books.import.route-books');
    Route::post('/books/import/preview', [BulkImportPageController::class, 'preview'])->name('books.import.preview');
    Route::post('/books/import/commit', [BulkImportPageController::class, 'commit'])->name('books.import.commit');
    Route::post('/books/import/manual', [BulkImportPageController::class, 'storeManual'])->name('books.import.manual');
    Route::post('/books/import/isbn-lookup', [BulkImportPageController::class, 'lookupIsbn'])->name('books.import.isbn-lookup');
    Route::post('/books/import/ai-scan', [BulkImportPageController::class, 'scanWithAi'])->name('books.import.ai-scan');
    Route::post('/books/import/enrich', [BulkImportPageController::class, 'enrichMetadata'])->name('books.import.enrich');
    Route::post('/books/import/ai-batch-scan', [BulkImportPageController::class, 'scanBatchWithAi'])->name('books.import.ai-batch-scan');
    Route::get('/books/import/ai-batch-status/{token}', [BulkImportPageController::class, 'batchScanStatus'])->name('books.import.ai-batch-status');
    Route::post('/books/import/ai-batch-cancel/{token}', [BulkImportPageController::class, 'cancelBatchScan'])->name('books.import.ai-batch-cancel');
    Route::post('/books/import/ai-review-commit', [BulkImportPageController::class, 'commitScannedBooks'])->name('books.import.ai-review-commit');
    Route::get('/books/{book}/panel', [BookPageController::class, 'panel'])->name('books.web.panel');
    Route::get('/books/{book}', [BookPageController::class, 'show'])->name('books.web.show');
    Route::delete('/books/{book}', [BookPageController::class, 'destroy'])->name('books.destroy');
    Route::get('/categories', [CategoryPageController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryPageController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryPageController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryPageController::class, 'destroy'])->name('categories.destroy');
    Route::get('/racks', [RoomPageController::class, 'index'])->name('racks.index');
    Route::post('/rooms', [RoomPageController::class, 'store'])->name('rooms.store');
    Route::put('/rooms/{room}', [RoomPageController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomPageController::class, 'destroy'])->name('rooms.destroy');
    Route::get('/rooms/suggest-slot', [RoomPageController::class, 'suggestSlot'])->name('rooms.suggest-slot');
    Route::get('/rooms/{room}', [RoomPageController::class, 'show'])->name('rooms.show');
    Route::post('/racks', [RackPageController::class, 'store'])->name('racks.store');
    Route::put('/racks/{rack}', [RackPageController::class, 'update'])->name('racks.update');
    Route::delete('/racks/{rack}', [RackPageController::class, 'destroy'])->name('racks.destroy');
    Route::get('/racks/{rack}', [RackPageController::class, 'show'])->name('racks.show');
    Route::post('/racks/{rack}/assign', [RackPageController::class, 'assign'])->name('racks.assign');
    Route::put('/racks/{rack}/column-category', [RackPageController::class, 'setColumnCategory'])->name('racks.set-column-category');
    Route::post('/racks/{rack}/column-category', [RackPageController::class, 'setColumnCategory']);
    Route::get('/qr', [QrStickerPageController::class, 'index'])->name('qr.index');
    Route::get('/qr/print', [QrStickerPageController::class, 'print'])->name('qr.print');
    Route::post('/qr/generate-missing', [QrStickerPageController::class, 'generateMissing'])->name('qr.generate-missing');
    Route::post('/qr/generate/{book}', [QrStickerPageController::class, 'generateSingle'])->name('qr.generate-single');
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::post('/borrowings/{borrowing}/return', [BorrowingController::class, 'returnBook'])->name('borrowings.return');
    Route::get('/scan', QrScannerController::class)->name('scanner');
    Route::get('/members', [MemberPageController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberPageController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberPageController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberPageController::class, 'destroy'])->name('members.destroy');
    Route::get('/members/search', [MemberPageController::class, 'search'])->name('members.search');
    Route::get('/members/{member}', [MemberPageController::class, 'show'])->name('members.show');
    Route::get('/settings', [SettingsPageController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsPageController::class, 'update'])->name('settings.update');
});

// ── Admin Observability ──
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/observability', [AiObservabilityController::class, 'index'])->name('admin.observability.index');
    Route::get('/admin/observability/stats', [AiObservabilityController::class, 'stats'])->name('admin.observability.stats');
    Route::get('/admin/observability/providers', [AiObservabilityController::class, 'providers'])->name('admin.observability.providers');
});

// ── Public ──
Route::get('/book/{bookId}', [BookPublicController::class, 'show'])->name('books.public.show');
