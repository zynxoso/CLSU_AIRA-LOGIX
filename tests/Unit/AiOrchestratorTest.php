<?php

namespace Tests\Unit;

use App\Services\AiOrchestrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class AiOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These tests require mocking static methods on a non-facade class (AiBudgetManager),
     * which is not supported by Laravel's mocking tools. Refactor AiOrchestrator to allow
     * dependency injection for better testability, or use a static mocking library.
     */
    public function test_prompt_throws_exception_when_budget_exceeded()
    {
        $this->markTestIncomplete('Cannot mock static methods on AiBudgetManager. Refactor required for testability.');
    }

    public function test_prompt_returns_gemini_response()
    {
        $this->markTestIncomplete('Cannot mock static methods on AiBudgetManager. Refactor required for testability.');
    }

    public function test_prompt_fallbacks_to_openrouter()
    {
        $this->markTestIncomplete('Cannot mock static methods on AiBudgetManager. Refactor required for testability.');
    }
}
