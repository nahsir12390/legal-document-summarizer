<?php

use App\Http\Controllers\DocumentController;
use App\Services\DocumentProcessingService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Traditional Controller Routes (No Livewire)
Route::get('/', [DocumentController::class, 'create'])->name('documents.upload');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

Route::get('/ai-test', function (DocumentProcessingService $processingService) {
    try {
        $response = $processingService->testAiConnection();

        if ($response) {
            return 'Gemini is working. Response: ' . $response;
        }

        return 'Gemini did not return a response. Check GEMINI_API_KEY and GEMINI_MODEL in your .env file.';
    } catch (Exception $e) {
        return 'Connection error: ' . $e->getMessage();
    }
});

// Fallback route for any undefined routes
Route::fallback(function () {
    return redirect()->route('documents.upload');
});
