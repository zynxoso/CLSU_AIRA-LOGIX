<?php

namespace Tests\Unit;

use App\Services\IctImageExtractionService;
use App\Services\Extraction\ImageOptimizer;
use App\Services\Extraction\AiVisionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use App\Support\AiExtractionConfig;
use Tests\TestCase;
use Mockery;

class IctImageExtractionServiceTest extends TestCase
{
    public function test_extract_returns_cached_result()
    {
        $imagePath = __FILE__;
        $fileHash = 'abc123';
        $cacheKey = 'cache_key';
        $expected = ['foo' => 'bar'];
        Cache::shouldReceive('has')->andReturn(true);
        Cache::shouldReceive('get')->andReturn($expected);
        // Instead of mocking, patch the service to use the known cache key
        // This assumes the service will call Cache::get($cacheKey) as above
        $service = new IctImageExtractionService(
            Mockery::mock(ImageOptimizer::class),
            Mockery::mock(AiVisionService::class)
        );
        $result = $service->extract($imagePath);
        $this->assertEquals($expected, $result);
    }

    public function test_extract_circuit_breaker_returns_fallback()
    {
        $imagePath = __FILE__;
        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
        $service = new IctImageExtractionService(
            Mockery::mock(ImageOptimizer::class),
            Mockery::mock(AiVisionService::class)
        );
        $result = $service->extract($imagePath);
        $this->assertArrayHasKey('request_description', $result);
        $this->assertEquals('Extraction Failed', $result['name']);
    }
}
