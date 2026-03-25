<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Neuron\NeuronAI;
use Exception;

class AiOrchestrator
{
    /**
     * Orchestrate AI call with fallbacks.
     */
    public function prompt(string $prompt, array $options = [], ?string $base64Image = null, ?string $mimeType = null, ?string $systemPrompt = null): string
    {
        // 1. Check Budget
        if (class_exists('App\Services\AiBudgetManager') && !AiBudgetManager::canSpend()) {
             throw new Exception("AI Budget exceeded for this month.");
        }

        // 2. Try Cache if enabled
        $cacheKey = "ai_resp_" . md5($prompt . $systemPrompt . serialize($options) . ($base64Image ? md5($base64Image) : ''));
        if (config('services.ai.cache_enabled') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 3. Fallback Chain: Gemini -> OpenRouter
        try {
            return $this->tryGemini($prompt, $options, $cacheKey, $base64Image, $mimeType, $systemPrompt);
        } catch (Exception $e) {
            Log::warning("Gemini failed, trying OpenRouter: " . $e->getMessage());
            try {
                return $this->tryOpenRouter($prompt, $options, $cacheKey, $base64Image, $mimeType, $systemPrompt);
            } catch (Exception $e2) {
                Log::error("All AI providers failed: " . $e2->getMessage());
                throw new Exception("Document analysis failed across all providers. Details: " . $e2->getMessage());
            }
        }
    }

    protected function tryGemini(string $prompt, array $options, string $cacheKey, ?string $base64Image = null, ?string $mimeType = null, ?string $systemPrompt = null): string
    {
        $key = config('services.gemini.key');
        if (!$key) throw new Exception("Gemini API key not configured.");

        $model = config('services.gemini.model', 'gemini-3.1-flash-lite-preview');

        $parts = [['text' => $prompt]];
        if ($base64Image) {
            $parts[] = ['inline_data' => ['mime_type' => $mimeType ?? 'image/jpeg', 'data' => $base64Image]];
        }

        $body = [
            'contents' => [['parts' => $parts]]
        ];

        if ($systemPrompt) {
            $body['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $body);

        if ($response->failed()) {
            throw new Exception("Gemini API error ({$response->status()}): " . $response->body());
        }

        $result = $response->json('candidates.0.content.parts.0.text');
        if (!$result) throw new Exception("Invalid response structure from Gemini.");
        
        $this->logUsage('gemini', $model, $prompt, $result);
        $this->cacheResponse($cacheKey, $result);

        return $result;
    }

    protected function tryOpenRouter(string $prompt, array $options, string $cacheKey, ?string $base64Image = null, ?string $mimeType = null, ?string $systemPrompt = null): string
    {
        $key = config('services.openrouter.key');
        if (!$key) throw new Exception("OpenRouter API key not configured.");

        $model = config('services.openrouter.model', 'google/gemini-3.1-flash-lite-preview-exp:free');
        
        $userContent = [['type' => 'text', 'text' => $prompt]];
        if ($base64Image) {
             $userContent[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64Image}"]];
        }

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userContent];

        $response = Http::withToken($key)
            ->withHeaders(['HTTP-Referer' => config('app.url', 'http://localhost'), 'X-Title' => 'AIRA-LOGIX'])
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages
            ]);

        if ($response->failed()) {
            throw new Exception("OpenRouter API error ({$response->status()}): " . $response->body());
        }

        $result = $response->json('choices.0.message.content');
        if (!$result) throw new Exception("Invalid response structure from OpenRouter.");

        $this->logUsage('openrouter', $model, $prompt, $result);
        $this->cacheResponse($cacheKey, $result);

        return $result;
    }

    protected function logUsage(string $service, string $model, string $prompt, string $result): void
    {
        // Simple token estimation: chars / 4
        $promptTokens = (int) (strlen($prompt) / 4);
        $completionTokens = (int) (strlen($result) / 4);
        $totalTokens = $promptTokens + $completionTokens;
        
        $cost = ($totalTokens / 1000) * 0.0001; 

        try {
            AiUsageLog::create([
                'service' => $service,
                'model' => $model,
                'user_id' => auth()->id(),
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost' => $cost,
                'metadata' => ['options' => []]
            ]);

            if (class_exists('App\Services\AiBudgetManager')) {
                AiBudgetManager::incrementMonthlyCost($cost);
            }
        } catch (Exception $e) {
            Log::warning("Could not log AI usage: " . $e->getMessage());
        }
    }

    protected function cacheResponse(string $key, string $value): void
    {
        if (config('services.ai.cache_enabled')) {
            Cache::put($key, $value, now()->addDays(7));
        }
    }
}
