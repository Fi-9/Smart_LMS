<?php

use App\Http\Controllers\BookPublicController;
use App\Http\Controllers\QrScannerController;
use App\Http\Controllers\Web\BookAssignmentController;
use App\Http\Controllers\Web\BookPageController;
use App\Http\Controllers\Web\BulkImportPageController;
use App\Http\Controllers\Web\CategoryPageController;
use App\Http\Controllers\Web\DashboardPageController;
use App\Http\Controllers\Web\QrStickerPageController;
use App\Http\Controllers\Web\RackPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardPageController::class, 'view'])->name('dashboard');
Route::get('/dashboard', [DashboardPageController::class, 'view']);
Route::get('/books', [BookPageController::class, 'indexView'])->name('books.index');
Route::post('/books/assign', [BookAssignmentController::class, 'store'])->name('books.assign');
Route::post('/books/auto-assign', [BookAssignmentController::class, 'autoAssign'])->name('books.auto-assign');
Route::get('/books/import', [BulkImportPageController::class, 'view'])->name('books.import');
Route::post('/books/import/preview', [BulkImportPageController::class, 'preview'])->name('books.import.preview');
Route::post('/books/import/commit', [BulkImportPageController::class, 'commit'])->name('books.import.commit');
Route::get('/categories', [CategoryPageController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryPageController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [CategoryPageController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryPageController::class, 'destroy'])->name('categories.destroy');
Route::get('/racks', [RackPageController::class, 'index'])->name('racks.index');
Route::post('/racks', [RackPageController::class, 'store'])->name('racks.store');
Route::put('/racks/{rack}', [RackPageController::class, 'update'])->name('racks.update');
Route::delete('/racks/{rack}', [RackPageController::class, 'destroy'])->name('racks.destroy');
Route::get('/racks/{rack}', [RackPageController::class, 'show'])->name('racks.show');
Route::post('/racks/{rack}/assign', [RackPageController::class, 'assign'])->name('racks.assign');
Route::get('/qr', [QrStickerPageController::class, 'index'])->name('qr.index');
Route::get('/qr/generate', [QrStickerPageController::class, 'index'])->name('qr.generate');
Route::get('/qr/print', [QrStickerPageController::class, 'print'])->name('qr.print');

Route::get('/scan', QrScannerController::class)->name('scanner');
Route::get('/book/{bookId}', [BookPublicController::class, 'show'])->name('books.public.show');
