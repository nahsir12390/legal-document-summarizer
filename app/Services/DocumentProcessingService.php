<?php

namespace App\Services;

use App\Models\Document;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class DocumentProcessingService
{
    private const GEMINI_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Process a document: extract text and generate summary
     */
    public function processDocument(Document $document): Document
    {
        // Allow longer processing time for hosted AI summarization
        @ini_set('max_execution_time', '180');
        @set_time_limit(180);

        try {
            Log::info('Starting document processing', ['document_id' => $document->id]);

            // Get the full file path
            $filePath = Storage::disk('local')->path($document->file_path);

            // Extract text based on file type
            $extractedText = $this->extractText($filePath, $document->mime_type);

            // Generate summary using a hosted Gemini free-tier model.
            if (trim($extractedText) === '') {
                throw new Exception('No readable text found. Scanned PDFs need OCR before upload.');
            }
            if (mb_strlen($extractedText) > 200000) {
                throw new Exception('Document is too long. Split it into smaller documents.');
            }

            $summary = $this->generateSummaryWithGemini($extractedText, $document->summary_length);

            // Update document with summary and key points
            $document->update([
                'summary' => $summary['summary'],
                'key_points' => $summary['key_points'],
                'status' => 'completed',
                'processing_error' => null,
            ]);

            Log::info('Document processed successfully', ['document_id' => $document->id]);

            return $document;

        } catch (Exception $e) {
            Log::error('Document processing failed', ['exception_type' => get_class($e)]);
            throw new Exception('Document processing failed: '.$e->getMessage());
        }
    }

    /**
     * Extract text from different file types
     */
    private function extractText(string $filePath, string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => $this->extractFromPdf($filePath),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractFromDocx($filePath),
            'text/plain' => $this->extractFromTxt($filePath),
            default => throw new Exception('Unsupported file type: '.$mimeType)
        };
    }

    /**
     * Extract text from PDF
     */
    private function extractFromPdf(string $filePath): string
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    /**
     * Extract text from DOCX
     */
    private function extractFromDocx(string $filePath): string
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';

        $collect = function ($element) use (&$collect): string {
            if (method_exists($element, 'getRows')) {
                return implode("\n", array_map($collect, $element->getRows()));
            }
            if (method_exists($element, 'getCells')) {
                return implode("\n", array_map($collect, $element->getCells()));
            }
            if (method_exists($element, 'getElements')) {
                return implode("\n", array_map($collect, $element->getElements()));
            }

            return method_exists($element, 'getText') ? (string) $element->getText() : '';
        };
        foreach ($phpWord->getSections() as $section) {
            $text .= $collect($section)."\n";
        }

        return $text;
    }

    /**
     * Extract text from TXT
     */
    private function extractFromTxt(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    /**
     * Generate summary using Google Gemini's hosted free-tier API.
     */
    private function generateSummaryWithGemini(string $text, string $length): array
    {
        // Define length instructions
        $lengthInstructions = [
            'short' => 'a very concise summary in 2-3 sentences',
            'medium' => 'a balanced summary in about 5-7 sentences',
            'detailed' => 'a comprehensive detailed summary covering all important aspects',
        ];

        // Condense text if too long (avoid timeouts)
        // Summarize the complete text; never silently truncate legal clauses.

        // Prepare the prompt
        $prompt = "You are a legal document summarizer specializing in Nigerian legal and government documents.
        
Document text: {$text}

Please provide {$lengthInstructions[$length]} in simple language, and then list 5-7 key bullet points highlighting the most important aspects.

Important requirements:
- Use simple, clear language understandable for non-lawyers
- Focus on the main legal points and implications
- Consider Nigerian legal context
- Ensure key points are actionable and clear

Format your response as a JSON object with two keys: 
- 'summary' (string)
- 'key_points' (array of strings)

Example format:
{
    \"summary\": \"Your summary here...\",
    \"key_points\": [\"Point 1\", \"Point 2\", \"Point 3\"]
}

Respond ONLY with the JSON object, no other text.";

        try {
            Log::info('Calling Gemini for summarization');

            $numPredict = match ($length) {
                'short' => 2048,
                'medium' => 4096,
                'detailed' => 8192,
                default => 700
            };

            $content = $this->callGemini($prompt, $numPredict, 120, true);

            if ($content !== null) {
                Log::info('Gemini call successful', ['response_length' => strlen($content)]);

                // Try to extract JSON from the response
                $result = $this->parseSummaryJson($content);

                if ($result !== null) {
                    return $result;
                }

                throw new Exception('AI returned an invalid summary. Please retry.');
            }
            throw new Exception('AI processing failed. Check the Gemini API key, model, and quota, then retry.');
        } catch (Exception $e) {
            throw new Exception('AI processing failed. Check the Gemini API key, model, and quota, then retry.');
        }
    }

    private function parseSummaryJson(string $content): ?array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        preg_match('/\{.*\}/s', $content, $matches);

        if (! isset($matches[0])) {
            return null;
        }

        try {
            $result = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);

            if (is_string($result['summary'] ?? null) && trim($result['summary']) !== ''
                && is_array($result['key_points'] ?? null) && array_is_list($result['key_points'])
                && count($result['key_points']) > 0
                && count(array_filter($result['key_points'], fn ($point) => is_string($point) && trim($point) !== '')) === count($result['key_points'])) {
                return [
                    'summary' => $result['summary'],
                    'key_points' => $result['key_points'],
                ];
            }
        } catch (\JsonException $e) {
            Log::error('Summary JSON parse failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function testAiConnection(): ?string
    {
        return $this->callGemini('Say "Gemini is working!" in one sentence.', 60, 30);
    }

    /**
     * Call Gemini and return response text or null on failure.
     */
    private function callGemini(string $prompt, int $maxOutputTokens, int $timeoutSeconds, bool $jsonMode = false): ?string
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            Log::error('Gemini API key is not configured');

            return null;
        }

        $model = config('services.gemini.model', 'gemini-3.5-flash-lite');
        $url = self::GEMINI_BASE_URL.'/'.$model.':generateContent';

        $generationConfig = [
            'temperature' => 0.3,
            'maxOutputTokens' => $maxOutputTokens,
        ];

        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $response = Http::connectTimeout(10)->timeout($timeoutSeconds)->withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ]);

        Log::info('Gemini response status', ['status' => $response->status()]);

        if ($response->successful()) {
            $parts = $response->json('candidates.0.content.parts', []);
            $text = '';

            foreach ($parts as $part) {
                $text .= $part['text'] ?? '';
            }

            return trim($text) ?: null;
        }

        Log::error('Gemini API error', [
            'status' => $response->status(),
            // Do not log provider responses containing document content.
        ]);

        return null;
    }
}
