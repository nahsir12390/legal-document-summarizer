<?php

namespace App\Services;

use App\Models\Document;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentProcessingService
{
    private const GEMINI_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Process a document: extract text and generate summary
     */
    public function processDocument(Document $document): Document
    {
        // Allow longer processing time for hosted AI summarization
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        try {
            Log::info('Starting document processing', ['document_id' => $document->id]);

            // Get the full file path
            $filePath = Storage::disk('public')->path($document->file_path);

            // Extract text based on file type
            $extractedText = $this->extractText($filePath, $document->mime_type);

            // Generate summary using a hosted Gemini free-tier model.
            $summary = $this->generateSummaryWithGemini($extractedText, $document->summary_length);

            // Update document with summary and key points
            $document->update([
                'summary' => $summary['summary'],
                'key_points' => $summary['key_points']
            ]);

            Log::info('Document processed successfully', ['document_id' => $document->id]);

            return $document;

        } catch (Exception $e) {
            Log::error('Document processing failed: ' . $e->getMessage());
            throw new Exception('Document processing failed: ' . $e->getMessage());
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
            default => throw new Exception('Unsupported file type: ' . $mimeType)
        };
    }

    /**
     * Extract text from PDF
     */
    private function extractFromPdf(string $filePath): string
    {
        $parser = new Parser();
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

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText() . "\n";
                        }
                    }
                }
            }
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
            'detailed' => 'a comprehensive detailed summary covering all important aspects'
        ];

        // Condense text if too long (avoid timeouts)
        $text = $this->condenseTextIfNeeded($text, $length);

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
                'short' => 400,
                'medium' => 700,
                'detailed' => 1200,
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

                Log::error('Failed to parse Gemini response as JSON', [
                    'response' => substr($content, 0, 500)
                ]);

                return [
                    'summary' => $content,
                    'key_points' => ['Summary generated by Gemini']
                ];
            }

            Log::error('Gemini API returned no content');
            return $this->getFallbackSummary($text, $length);

        } catch (Exception $e) {
            Log::error('Gemini exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getFallbackSummary($text, $length);
        }
    }

    private function parseSummaryJson(string $content): ?array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        preg_match('/\{.*\}/s', $content, $matches);

        if (!isset($matches[0])) {
            return null;
        }

        try {
            $result = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);

            if (isset($result['summary']) && isset($result['key_points']) && is_array($result['key_points'])) {
                return [
                    'summary' => $result['summary'],
                    'key_points' => $result['key_points']
                ];
            }
        } catch (\JsonException $e) {
            Log::error('Summary JSON parse failed', [
                'error' => $e->getMessage()
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

        if (!$apiKey) {
            Log::error('Gemini API key is not configured');
            return null;
        }

        $model = config('services.gemini.model', 'gemini-3.5-flash-lite');
        $url = self::GEMINI_BASE_URL . '/' . $model . ':generateContent';

        $generationConfig = [
            'temperature' => 0.3,
            'maxOutputTokens' => $maxOutputTokens,
        ];

        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $response = Http::timeout($timeoutSeconds)->withQueryParameters([
            'key' => $apiKey,
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
            'body' => $response->body()
        ]);

        return null;
    }

    /**
     * Condense long input with fast chunk summaries to avoid timeouts
     */
    private function condenseTextIfNeeded(string $text, string $length): string
    {
        $maxLength = match ($length) {
            'short' => 6000,
            'medium' => 8000,
            'detailed' => 10000,
            default => 8000
        };

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $chunkSize = 3000;
        $chunks = str_split($text, $chunkSize);
        $chunkSummaries = [];

        foreach ($chunks as $index => $chunk) {
            $chunkPrompt = "Summarize this Nigerian legal document section in 2-3 sentences, plain text only.\n\nSection: {$chunk}";
            $chunkSummary = $this->callGemini($chunkPrompt, 250, 60);

            if ($chunkSummary === null) {
                Log::warning('Chunk summarization failed, falling back to truncation', ['chunk' => $index]);
                return substr($text, 0, $maxLength) . "... [Text truncated due to length]";
            }

            $chunkSummaries[] = $chunkSummary;
        }

        return implode("\n", $chunkSummaries);
    }

    /**
     * Fallback summary if Gemini is not configured or the API call fails.
     */
    private function getFallbackSummary(string $text, string $length): array
    {
        Log::info('Using fallback summary', ['text_length' => strlen($text), 'summary_type' => $length]);

        $wordCount = str_word_count($text);
        $summaryLength = match ($length) {
            'short' => min(50, intval($wordCount * 0.1)),
            'medium' => min(150, intval($wordCount * 0.2)),
            'detailed' => min(300, intval($wordCount * 0.3))
        };

        $words = str_word_count($text, 1);
        $summary = implode(' ', array_slice($words, 0, $summaryLength));

        return [
            'summary' => "This is a sample {$length} summary. Original text has {$wordCount} words. {$summary}...",
            'key_points' => [
                'Gemini API is not configured or did not respond',
                'Add a valid GEMINI_API_KEY to your environment',
                'The document text was extracted successfully'
            ]
        ];
    }
}
