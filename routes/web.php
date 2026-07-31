<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

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

// Fallback route for any undefined routes
Route::fallback(function () {
    return redirect()->route('documents.upload');
});

Route::get('/ollama-test', function() {
    try {
        $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
            'model' => 'llama3.2:3b',
            'prompt' => 'Say "Ollama is working!" in one sentence',
            'stream' => false
        ]);
        
        if ($response->successful()) {
            return '✅ Ollama is working! Response: ' . $response->json('response');
        } else {
            return '❌ Ollama error: ' . $response->body();
        }
    } catch (Exception $e) {
        return '❌ Connection error: ' . $e->getMessage();
    }
});