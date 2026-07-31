<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    protected $processingService;

    public function __construct(DocumentProcessingService $processingService)
    {
        $this->processingService = $processingService;
    }

    /**
     * Show the upload form
     */
    public function create()
    {
        return view('documents.upload');
    }

    /**
     * Handle document upload and processing
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx,txt|max:10240',
            'summary_length' => 'required|in:short,medium,detailed'
        ]);

        try {
            $file = $request->file('document');
            
            // Store the file
            $filePath = $file->store('documents', 'public');

            // Create document record
            $document = Document::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'summary_length' => $request->summary_length,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'summary' => null,
                'key_points' => null
            ]);

            // Process the document immediately
            try {
                $processedDocument = $this->processingService->processDocument($document);
                
                if ($processedDocument->summary) {
                    return redirect()->route('documents.show', $document->id)
                        ->with('success', 'Document uploaded and processed successfully!');
                } else {
                    return redirect()->route('documents.show', $document->id)
                        ->with('warning', 'Document uploaded but processing is taking longer than expected. Please refresh the page.');
                }
                
            } catch (\Exception $e) {
                Log::error('Document processing failed: ' . $e->getMessage(), [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return redirect()->route('documents.show', $document->id)
                    ->with('error', 'Document uploaded but processing failed. Our team has been notified.');
            }

        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show document summary
     */
    public function show($id)
    {
        $document = Document::findOrFail($id);
        
        // If document doesn't have summary yet, try to process it again
        if (!$document->summary) {
            try {
                $this->processingService->processDocument($document);
                $document->refresh(); // Reload from database
            } catch (\Exception $e) {
                Log::error('Background processing failed: ' . $e->getMessage());
            }
        }
        
        return view('documents.show', compact('document'));
    }

    /**
     * List all documents
     */
    public function index()
    {
        $documents = Document::latest()->paginate(15);
        return view('documents.index', compact('documents'));
    }

    /**
     * Download original document
     */
    public function download($id)
    {
        $document = Document::findOrFail($id);
        
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['error' => 'File not found.']);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * Delete document
     */
    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        
        // Delete file
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        // Delete record
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }
    
    /**
     * Manual refresh endpoint
     */
    public function refresh($id)
    {
        $document = Document::findOrFail($id);
        
        if (!$document->summary) {
            try {
                $this->processingService->processDocument($document);
                $document->refresh();
                $message = 'Document processed successfully!';
                $type = 'success';
            } catch (\Exception $e) {
                $message = 'Processing failed: ' . $e->getMessage();
                $type = 'error';
            }
        } else {
            $message = 'Document already processed.';
            $type = 'info';
        }
        
        if (request()->wantsJson()) {
            return response()->json([
                'status' => $type,
                'message' => $message,
                'document' => $document
            ]);
        }
        
        return redirect()->route('documents.show', $id)
            ->with($type, $message);
    }
}
