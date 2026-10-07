<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentController extends Controller
{
    public function create()
    {
        return view('documents.upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx,txt|max:10240',
            'summary_length' => 'required|in:short,medium,detailed',
        ]);
        $path = null;
        $document = null;
        try {
            $file = $request->file('document');
            $path = $file->store('documents', 'local');
            if (! $path) {
                throw new \RuntimeException('Unable to store document');
            }
            $document = Document::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'summary_length' => $request->summary_length,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'queued',
            ]);
            ProcessDocument::dispatch($document->id);

            return redirect()->route('documents.show', $document->id);
        } catch (Throwable $e) {
            if ($document) {
                $document->delete();
            }
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            Log::error('Document upload failed', ['exception_type' => get_class($e)]);

            return back()->withInput()->withErrors(['document' => 'Upload failed. Please try again.']);
        }
    }

    public function show($id)
    {
        $document = Document::findOrFail($id);

        return view('documents.show', compact('document'));
    }

    public function index()
    {
        $documents = Document::latest()->paginate(15);

        return view('documents.index', compact('documents'));
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        if (! Storage::disk('local')->delete($document->file_path)) {
            return back()->withErrors(['document' => 'Unable to delete the file. Please retry.']);
        }
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }

    public function retry($id)
    {
        $document = Document::findOrFail($id);
        $claimed = Document::whereKey($id)->where('status', 'failed')->update([
            'status' => 'queued', 'processing_error' => null, 'summary' => null, 'key_points' => null,
        ]);
        if ($claimed) {
            try {
                ProcessDocument::dispatch($document->id);
            } catch (Throwable $e) {
                $document->update(['status' => 'failed', 'processing_error' => 'Could not queue processing. Please retry.']);
            }
        }

        return redirect()->route('documents.show', $id);
    }
}
