<?php

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Services\DocumentProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Http::preventStrayRequests();
    config(['services.gemini.key' => 'test-key']);
});

function reviewDocument(array $attributes = []): Document
{
    Storage::disk('local')->put('documents/test.txt', 'The tenant must pay rent each month.');

    return Document::create(array_merge([
        'file_name' => 'test.txt', 'file_path' => 'documents/test.txt',
        'mime_type' => 'text/plain', 'summary_length' => 'medium', 'status' => 'queued',
    ], $attributes));
}

test('uploads privately and queues processing without calling AI', function () {
    Queue::fake();
    $this->post(route('documents.store'), [
        'document' => UploadedFile::fake()->createWithContent('contract.txt', 'Tenant must pay rent.'),
        'summary_length' => 'medium',
    ])->assertRedirect();
    $document = Document::firstOrFail();
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
    Queue::assertPushed(ProcessDocument::class);
    Http::assertNothingSent();
});

test('missing credentials never produce a sample summary and failure can be retried', function () {
    config(['services.gemini.key' => null]);
    $document = reviewDocument();
    $job = new ProcessDocument($document->id);
    try {
        $job->handle(app(DocumentProcessingService::class));
        $this->fail('Processing must fail without credentials');
    } catch (Exception $e) {
        $job->failed($e);
    }
    expect($document->fresh()->summary)->toBeNull();
    expect($document->fresh()->status)->toBe('failed');
    $this->get(route('documents.show', $document))->assertOk()->assertSee('Retry processing');
    Queue::fake();
    $this->post(route('documents.retry', $document))->assertRedirect();
    $this->post(route('documents.retry', $document))->assertRedirect();
    Queue::assertPushed(ProcessDocument::class, 1);
});

test('successful processing saves validated AI output', function () {
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
        'summary' => 'Rent is due monthly.', 'key_points' => ['Pay rent each month.'],
    ])]]]]]])]);
    $document = reviewDocument();
    (new ProcessDocument($document->id))->handle(app(DocumentProcessingService::class));
    expect($document->fresh()->status)->toBe('completed');
    expect($document->fresh()->summary)->toBe('Rent is due monthly.');
});

test('viewing a queued document does not process it', function () {
    $document = reviewDocument();
    $this->get(route('documents.show', $document))->assertOk();
    Http::assertNothingSent();
});

test('DOCX extraction preserves table cell text', function () {
    $word = new PhpWord;
    $section = $word->addSection();
    $section->addText('Contract heading');
    $table = $section->addTable();
    $table->addRow();
    $table->addCell()->addText('Payment obligation inside table');
    $path = tempnam(sys_get_temp_dir(), 'docx-test-');
    try {
        IOFactory::createWriter($word, 'Word2007')->save($path);
        $method = new ReflectionMethod(DocumentProcessingService::class, 'extractFromDocx');
        $text = $method->invoke(app(DocumentProcessingService::class), $path);
        expect($text)->toContain('Contract heading', 'Payment obligation inside table');
    } finally {
        unlink($path);
    }
});

test('existing public uploads can be moved privately', function () {
    $document = reviewDocument();
    Storage::disk('local')->delete($document->file_path);
    Storage::disk('public')->put($document->file_path, 'Existing contract');
    $this->artisan('documents:privatize')->assertSuccessful();
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
    $this->get(route('documents.download', $document))->assertDownload('test.txt');
});

test('invalid AI output is rejected rather than saved', function () {
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"summary":"Summary","key_points":[{"invalid":"object"}]}']]]]]])]);
    $document = reviewDocument();
    expect(fn () => app(DocumentProcessingService::class)->processDocument($document))->toThrow(Exception::class);
    expect($document->fresh()->summary)->toBeNull();
});

test('empty extracted text is rejected without an AI call', function () {
    $document = reviewDocument();
    Storage::disk('local')->put($document->file_path, '   ');
    expect(fn () => app(DocumentProcessingService::class)->processDocument($document))->toThrow(Exception::class);
    Http::assertNothingSent();
});
