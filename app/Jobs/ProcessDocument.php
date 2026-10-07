<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(public int $documentId) {}

    public function backoff(): array
    {
        return [30];
    }

    public function handle(DocumentProcessingService $service): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->status === 'completed') {
            return;
        }
        $document->update(['status' => 'processing', 'processing_error' => null]);
        $service->processDocument($document);
    }

    public function failed(?Throwable $exception): void
    {
        Document::whereKey($this->documentId)->update([
            'status' => 'failed',
            'processing_error' => 'Processing failed. Check your API configuration and quota. For scanned PDFs, upload a version containing selectable text.',
        ]);
    }
}
