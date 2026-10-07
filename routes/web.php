<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Traditional Controller Routes (No Livewire)
Route::get('/', [DocumentController::class, 'create'])->name('documents.upload');
Route::post('/documents', [DocumentController::class, 'store'])->middleware('throttle:10,1')->name('documents.store');
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

Route::post('/documents/{id}/retry', [DocumentController::class, 'retry'])->middleware('throttle:5,1')->name('documents.retry');

// Fallback route for any undefined routes
Route::fallback(function () {
    return redirect()->route('documents.upload');
});
