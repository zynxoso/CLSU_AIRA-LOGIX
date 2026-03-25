<?php

namespace App\Support;

class AiExtractionConfig
{
    public static function minimumPrecheckCost(): float
    {
        return (float) config('services.ai.minimum_precheck_cost', 0.01);
    }

    public static function promptTokenCost(): float
    {
        return (float) config('services.ai.prompt_token_cost', 0.000000075);
    }

    public static function completionTokenCost(): float
    {
        return (float) config('services.ai.completion_token_cost', 0.00000030);
    }

    public static function estimateCost(int $promptTokens, int $completionTokens): float
    {
        return ($promptTokens * self::promptTokenCost()) + ($completionTokens * self::completionTokenCost());
    }

    public static function visionCacheKey(string $imageHash): string
    {
        return "ai_vision_cache_{$imageHash}";
    }

    public static function parserCacheKey(string $inputHash): string
    {
        return "ai_parser_cache_{$inputHash}";
    }

    public static function imageExtractionCacheKey(string $fileHash): string
    {
        return "ai_vision_v3_{$fileHash}";
    }
}
