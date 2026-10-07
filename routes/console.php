<?php

use App\Models\Document;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('documents:privatize', function () {
    foreach (Document::cursor() as $document) {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        if ($public->exists($document->file_path)) {
            if ($private->exists($document->file_path)) {
                $this->error("Destination already exists for document {$document->id}; resolve this conflict before retrying.");

                return 1;
            }
            $stream = $public->readStream($document->file_path);
            try {
                if (! is_resource($stream) || ! $private->put($document->file_path, $stream)) {
                    throw new RuntimeException('Unable to copy file');
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            if (! $public->delete($document->file_path)) {
                throw new RuntimeException('Unable to remove public copy');
            }
        }
    }
    $this->info('Existing document files moved to private storage.');
})->purpose('Move existing uploaded documents from public to private storage');
