<?php

namespace App\Services;

use App\Services\Extraction\ImageOptimizer;
use App\Services\Extraction\AiVisionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Support\AiExtractionConfig;
use Exception;

class IctImageExtractionService
{
    private ImageOptimizer $imageOptimizer;
    private AiVisionService $aiVisionService;

    // Circuit Breaker Configuration
    protected int $maxFailures = 5;
    protected int $decaySeconds = 300; // 5 minutes lockout

    public function __construct(ImageOptimizer $imageOptimizer, AiVisionService $aiVisionService)
    {
        $this->imageOptimizer = $imageOptimizer;
        $this->aiVisionService = $aiVisionService;
    }

    /**
     * Extract ICT form data from an image using AI Vision (Gemini).
     * Now includes SHA-256 hashing and result caching for better performance.
     */
    public function extract(string $imagePath): array
    {
        Log::info("IctImageExtractionService: Starting extraction process.");

        $fileHash = hash_file('sha256', $imagePath);
        $cacheKey = AiExtractionConfig::imageExtractionCacheKey($fileHash);

        if (Cache::has($cacheKey)) {
               $cached = Cache::get($cacheKey);
            Log::info("IctImageExtractionService: Cache hit! Returning stored result for hash: {$fileHash}");
                Log::info("IctImageExtractionService: Using cached extraction", [
                    'file_hash' => $fileHash,
                    'file_type' => pathinfo($imagePath, PATHINFO_EXTENSION)
                ]);
                return $cached;
        }

        if (RateLimiter::tooManyAttempts('ai_vision_failures', $this->maxFailures)) {
            Log::emergency("IctImageExtractionService: Circuit breaker OPEN. AI Service is temporarily disabled.");
            return $this->fallbackResult();
        }

        Log::info("IctImageExtractionService: Preparing image for AI analysis.");
            $fileType = pathinfo($imagePath, PATHINFO_EXTENSION);
        
        $optimizedImagePath = $this->imageOptimizer->optimize($imagePath);

        try {
            $data = $this->aiVisionService->extractFromImage($optimizedImagePath);
            
            Cache::forever($cacheKey, $data);
                Log::info("IctImageExtractionService: Extraction successful and cached", [
                    'file_hash' => $fileHash,
                    'file_type' => $fileType,
                    'fields_extracted' => count($data)
                ]);
            
            return $data;
        } catch (Exception $e) {
            RateLimiter::hit('ai_vision_failures', $this->decaySeconds);
                Log::error("IctImageExtractionService: AI Vision extraction failed", [
                    'file_type' => $fileType,
                    'error' => $e->getMessage()
                ]);
            
            return $this->fallbackResult();
        } finally {
            // Clean up optimized temp file
            if ($optimizedImagePath !== $imagePath && file_exists($optimizedImagePath)) {
                unlink($optimizedImagePath);
            }
        }
    }

    protected function fallbackResult(): array
    {
        return [
            'request_description' => '[AI SERVICE UNAVAILABLE] Manual entry required.',
            'request_type' => 'ICT Technical Support',
            'status' => 'Open',
            'name' => 'Extraction Failed'
        ];
    }
}
