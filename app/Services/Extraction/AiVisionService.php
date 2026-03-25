<?php

namespace App\Services\Extraction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\AiBudgetManager;
use App\Models\AiUsageLog;
use App\Support\AiExtractionConfig;
use Exception;

class AiVisionService
{
    public function extractFromImage(string $optimizedImagePath): array
    {
        // Tier 5: Budget Guardrail Pre-check
        if (!AiBudgetManager::canSpend(AiExtractionConfig::minimumPrecheckCost())) { // Ensure at least 1 cent remains
            Log::warning("AiVisionService: Request blocked. AI Budget threshold reached.");
            throw new Exception("Monthly AI budget exceeded. Please contact administrator.");
        }

        $imageHash = md5_file($optimizedImagePath);
        $cacheKey = AiExtractionConfig::visionCacheKey($imageHash);

        // Tier 1: Cache Hit
        if (config('services.ai.cache_enabled', true) && Cache::has($cacheKey)) {
            Log::info("AiVisionService: Cache hit for image hash [{$imageHash}]");
            return Cache::get($cacheKey);
        }

        $imageData = base64_encode(file_get_contents($optimizedImagePath));
        $mimeType  = mime_content_type($optimizedImagePath) ?: 'image/jpeg';

        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt();

        $apiKey = config('services.gemini.key');
        $model  = config('services.gemini.model', 'gemini-1.5-flash');
        $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $userPrompt],
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]],
                ]
            ]],
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'control_no' => ['type' => 'string', 'nullable' => true],
                        'name' => ['type' => 'string', 'nullable' => true],
                        'position' => ['type' => 'string', 'nullable' => true],
                        'office_unit' => ['type' => 'string', 'nullable' => true],
                        'contact_no' => ['type' => 'string', 'nullable' => true],
                        'date_of_request' => ['type' => 'string', 'nullable' => true],
                        'requested_completion_date' => ['type' => 'string', 'nullable' => true],
                        'request_type' => ['type' => 'string', 'nullable' => true],
                        'location_venue' => ['type' => 'string', 'nullable' => true],
                        'request_description' => ['type' => 'string', 'nullable' => true],
                        'received_by' => ['type' => 'string', 'nullable' => true],
                        'receive_date_time' => ['type' => 'string', 'nullable' => true],
                        'action_taken' => ['type' => 'string', 'nullable' => true],
                        'recommendation_conclusion' => ['type' => 'string', 'nullable' => true],
                        'status' => ['type' => 'string', 'nullable' => true],
                        'client_feedback_no' => ['type' => 'string', 'nullable' => true],
                        'date_time_started' => ['type' => 'string', 'nullable' => true],
                        'date_time_completed' => ['type' => 'string', 'nullable' => true],
                        'conducted_by' => ['type' => 'string', 'nullable' => true],
                        'noted_by' => ['type' => 'string', 'nullable' => true],
                    ]
                ],
                'temperature' => 0.1,
                'topP' => 0.1
            ]
        ];

        Log::info("AiVisionService: Calling Gemini API for image extraction.");

        $startTime = hrtime(true);
        try {
            $response = Http::timeout(60)->post($url, $payload);
            $endTime = hrtime(true);
            $durationMs = ($endTime - $startTime) / 1e6;

            if ($response->successful()) {
                return $this->processResponse($response, $model, $optimizedImagePath, $durationMs, $cacheKey);
            }

            Log::warning('AiVisionService primary API failed, trying OpenRouter fallback', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);

            return $this->callOpenRouter($userPrompt, $systemPrompt, $mimeType, $imageData, $optimizedImagePath, $cacheKey);

        } catch (Exception $e) {
            Log::warning('AiVisionService primary API exception, trying OpenRouter fallback', [
                'message' => $e->getMessage()
            ]);

            return $this->callOpenRouter($userPrompt, $systemPrompt, $mimeType, $imageData, $optimizedImagePath, $cacheKey);
        }
    }

    protected function processResponse($response, $model, $optimizedImagePath, $durationMs, $cacheKey)
    {
        $resJson = $response->json();
        
        // Handle Gemini format vs OpenRouter format
        if (isset($resJson['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $resJson['candidates'][0]['content']['parts'][0]['text'];
        } else if (isset($resJson['choices'][0]['message']['content'])) {
            $content = $resJson['choices'][0]['message']['content'];
        } else {
            $content = '';
        }

        // Strip markdown if present
        $content = preg_replace('/^```json\s*|\s*```$/i', '', trim($content));
        $data = json_decode($content, true);

        if (!$data) {
            throw new Exception("AI could not parse the response into valid JSON.");
        }

        // Log usage if metadata exists
        $usage = $resJson['usageMetadata'] ?? ($resJson['usage'] ?? null);
        if ($usage) {
            $pTokens = $usage['promptTokenCount'] ?? ($usage['prompt_tokens'] ?? 0);
            $cTokens = $usage['candidatesTokenCount'] ?? ($usage['completion_tokens'] ?? 0);
            $tTokens = $usage['totalTokenCount'] ?? ($usage['total_tokens'] ?? $pTokens + $cTokens);
            
            $estimatedCost = AiExtractionConfig::estimateCost($pTokens, $cTokens);
           
               $extractionMethod = strpos($model, 'openrouter') !== false ? 'openrouter_vision' : 'gemini_vision';
            
            AiUsageLog::create([
                'service' => 'gemini_vision',
                'model' => $model,
                'user_id' => auth()->id(),
                    'extraction_method' => $extractionMethod,
                'source_file_type' => 'jpg',
                'prompt_tokens' => $pTokens,
                'completion_tokens' => $cTokens,
                'total_tokens' => $tTokens,
                'estimated_cost' => $estimatedCost,
                'metadata' => [
                    'image_path' => $optimizedImagePath,
                    'status' => 'success',
                    'duration_ms' => $durationMs
                ]
            ]);

                Log::info("AiVisionService: Extraction completed", [
                    'extraction_method' => $extractionMethod,
                    'model' => $model,
                    'tokens_used' => $tTokens,
                    'cost' => $estimatedCost,
                    'duration_ms' => $durationMs
                ]);

            AiBudgetManager::incrementMonthlyCost($estimatedCost);
        }

        if (config('services.ai.cache_enabled', true)) {
            Cache::put($cacheKey, $data, now()->addHours(24));
        }

        return $data;
    }

    protected function callOpenRouter($userPrompt, $systemPrompt, $mimeType, $imageData, $optimizedImagePath, $cacheKey)
    {
        $apiKey = config('services.openrouter.key');
        $model  = config('services.openrouter.model', 'google/gemini-2.0-flash-001:free');
        $url    = "https://openrouter.ai/api/v1/chat/completions";

        if (!$apiKey) {
            throw new Exception("OpenRouter API key missing. Primary extraction failed and no fallback available.");
        }

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $userPrompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}"
                            ]
                        ]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1
        ];

        $startTime = hrtime(true);
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->timeout(90)->post($url, $payload);
        $endTime = hrtime(true);
        $durationMs = ($endTime - $startTime) / 1e6;

        if (!$response->successful()) {
            Log::error('AiVisionService OpenRouter fallback failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new Exception("Both primary and fallback AI extraction failed.");
        }

        return $this->processResponse($response, "openrouter/{$model}", $optimizedImagePath, $durationMs, $cacheKey);
    }

    protected function getSystemPrompt(): string
    {
        return "Strict literal OCR extractor for ICT Request Forms. Transcribe exactly as written. Blank fields MUST be null. Dates in YYYY-MM-DD. Request types: 'Technical Support', 'Network/Internet', 'Hardware Repair', 'Software Install', 'User Account Management', 'System Development', 'Others'. Statuses: 'Open', 'In Progress', 'Resolved'.";
    }

    protected function getUserPrompt(): string
    {
        return "Extract data following the defined schema. DO NOT HALLUCINATE.";
    }

    protected function tryLocalOcr(string $imagePath): array
    {
        // requires tesseract installed on server
        $escaped = escapeshellarg($imagePath);
        $cmd = "tesseract {$escaped} stdout --oem 1 --psm 6 2>NUL";
        $output = shell_exec($cmd);

        $text = trim((string) $output);
        if ($text === '') {
            return ['ok' => false, 'text' => '', 'confidence' => 0];
        }

        // simple confidence estimate by length and field-like patterns
        $score = 0;
        if (strlen($text) > 200) $score += 40;
        if (preg_match('/name|office|request|date|status/i', $text)) $score += 40;
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $text)) $score += 20;

        return ['ok' => true, 'text' => $text, 'confidence' => min(100, $score)];
    }
}
