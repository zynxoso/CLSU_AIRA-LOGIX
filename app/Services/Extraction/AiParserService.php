<?php

namespace App\Services\Extraction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\AiBudgetManager;
use App\Models\AiUsageLog;
use App\Support\AiExtractionConfig;
use Exception;

class AiParserService
{
    private const MAX_RETRIES = 3;

    public function parse(string $text, ?string $preSelectedRequestType): array
    {
        // Tier 5: Budget Guardrail Pre-check
        if (!AiBudgetManager::canSpend(AiExtractionConfig::minimumPrecheckCost())) { // Ensure at least 1 cent remains
            Log::warning("AiParserService: Request blocked. AI Budget threshold reached.");
            throw new Exception("Monthly AI budget exceeded. Please contact administrator.");
        }

        $text = $this->cleanInputText($text);
        $promptVersion = (string) config('services.ai.prompt_version', 'v1');
        $inputHash = md5($promptVersion . '|' . $text . '|' . ($preSelectedRequestType ?? ''));
        $cacheKey = AiExtractionConfig::parserCacheKey($inputHash);

        // Tier 1: Cache Hit
        if (config('services.ai.cache_enabled', true) && Cache::has($cacheKey)) {
            Log::info("AiParserService: Cache hit for input hash [{$inputHash}]");
            return Cache::get($cacheKey);
        }

        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $systemPrompt = $this->getInstructions();

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "FORM TEXT TO ANALYZE:\n\n" . $text]
                    ]
                ]
            ],
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
                'topP' => 0.1,
                'maxOutputTokens' => (int) config('services.ai.max_output_tokens', 600)
            ]
        ];

        $retryCount = 0;
        $response = null;
        $durationMs = 0;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $startTime = hrtime(true);
                Log::info("AiParserService: Calling Gemini API for text extraction. Attempt " . ($retryCount + 1));
                
                $response = Http::timeout(60)->post($url, $payload);
                
                $endTime = hrtime(true);
                $durationMs = ($endTime - $startTime) / 1e6;

                // Stop retrying on success or non-retryable errors
                if ($response->successful()) {
                    return $this->processResponse($response, $model, $text, $durationMs, $retryCount, $cacheKey, $preSelectedRequestType);
                }

                if (!$this->shouldRetryStatus($response->status())) {
                    break;
                }

            } catch (Exception $e) {
                if ($retryCount >= self::MAX_RETRIES - 1) {
                    Log::warning('AiParserService exception, trying OpenRouter fallback: ' . $e->getMessage());
                    break;
                }
            }
            
            $retryCount++;
            Log::warning("AiParserService: Transient error encountered. Retrying {$retryCount}/" . self::MAX_RETRIES);
            sleep(pow(2, $retryCount)); // Exponential backoff: 2s, 4s, 8s
        }

        return $this->callOpenRouter($text, $systemPrompt, $cacheKey, $preSelectedRequestType);
    }

    protected function processResponse($response, $model, $text, $durationMs, $retries, $cacheKey, $preSelectedRequestType)
    {
        $resJson = $response->json();
        
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
             Log::error('AiParserService: Invalid JSON from AI', ['raw_response' => $content]);
             throw new Exception("AI could not read the data format. Please check if the file is a standard ICT Form.");
        }

        // Tier 11: Usage Logging
        $usage = $resJson['usageMetadata'] ?? ($resJson['usage'] ?? null);
        if ($usage) {
            $pTokens = $usage['promptTokenCount'] ?? ($usage['prompt_tokens'] ?? 0);
            $cTokens = $usage['candidatesTokenCount'] ?? ($usage['completion_tokens'] ?? 0);
            $tTokens = $usage['totalTokenCount'] ?? ($usage['total_tokens'] ?? $pTokens + $cTokens);

            $estimatedCost = AiExtractionConfig::estimateCost($pTokens, $cTokens);

               $extractionMethod = $this->detectMethod($model);

            AiUsageLog::create([
                'service' => 'text_parsing',
                'model' => $model,
                'user_id' => auth()->id(),
                   'extraction_method' => $extractionMethod,
                'source_file_type' => 'docx',
                'prompt_tokens' => $pTokens,
                'completion_tokens' => $cTokens,
                'total_tokens' => $tTokens,
                'estimated_cost' => $estimatedCost,
                'metadata' => [
                    'status' => 'success',
                    'text_length' => strlen($text),
                    'duration_ms' => $durationMs,
                    'retries' => $retries
                ]
            ]);

               Log::info("AiParserService: Extraction completed", [
                   'extraction_method' => $extractionMethod,
                   'model' => $model,
                   'tokens_used' => $tTokens,
                   'cost' => $estimatedCost,
                   'duration_ms' => $durationMs
               ]);

            AiBudgetManager::incrementMonthlyCost($estimatedCost);
        }

        // Deterministic results always win over AI
        if ($preSelectedRequestType !== null) {
            $data['request_type'] = $preSelectedRequestType;
        } else if (empty($data['request_type'])) {
            $data['request_type'] = 'ICT Technical Support';
        }

        if (config('services.ai.cache_enabled', true)) {
            Cache::put($cacheKey, $data, now()->addHours(24));
        }

        return $data;
    }

    protected function callOpenRouter($text, $systemPrompt, $cacheKey, $preSelectedRequestType)
    {
        $apiKey = config('services.openrouter.key');
        $model  = config('services.openrouter.model', 'google/gemini-2.0-flash-001:free');
        $url    = "https://openrouter.ai/api/v1/chat/completions";

        if (!$apiKey) {
            throw new Exception("OpenRouter API key missing. Primary parsing failed and no fallback available.");
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "FORM TEXT TO ANALYZE:\n\n" . $text]
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
            Log::error('AiParserService OpenRouter fallback failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new Exception("Both primary and fallback AI parsing failed.");
        }

        return $this->processResponse($response, "openrouter/{$model}", $text, $durationMs, 0, $cacheKey, $preSelectedRequestType);
    }

    protected function cleanInputText(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_values(array_filter($lines, function ($line) {
            return $line !== '';
        }));

        // keep only first 1200 lines to avoid huge prompts
        $lines = array_slice($lines, 0, 1200);

        return implode("\n", $lines);
    }

    protected function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [408, 429, 500, 502, 503, 504], true);
    }

    protected function getInstructions(): string
    {
        return "Strict literal data extractor for ICT Request Forms. Transcribe EXACTLY. Never assume/invent. Blank fields MUST be null. Dates in YYYY-MM-DD. Request types: 'Technical Support', 'Network/Internet', 'Hardware Repair', 'Software Install', 'User Account Management', 'System Development', 'Others'. Statuses: 'Open', 'In Progress', 'Resolved', 'Escalated'.";
    }

    private function detectMethod(string $model): string
    {
        if (strpos($model, 'openrouter') !== false) {
            return 'openrouter_parser';
        }
        return 'gemini_parser';
    }
}
