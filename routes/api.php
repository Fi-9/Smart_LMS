<?php

use App\Http\Controllers\Api\AiBookScanController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BulkImportController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IsbnLookupController;
use App\Http\Controllers\Api\RackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::post('/isbn/lookup', IsbnLookupController::class);
    Route::post('/ai/books/scan', AiBookScanController::class);
    Route::get('/dashboard/stats', DashboardController::class);

    Route::post('/books/import/preview', [BulkImportController::class, 'preview']);
    Route::post('/books/import/commit', [BulkImportController::class, 'commit']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('racks', RackController::class);
    Route::apiResource('books', BookController::class);
});
