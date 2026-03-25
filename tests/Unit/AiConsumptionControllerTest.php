<?php

namespace Tests\Unit;

use App\Http\Controllers\AiConsumptionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class AiConsumptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_inertia_view_with_summary_and_logs()
    {
        // Arrange
        $user = \App\Models\User::factory()->create([
            'role' => 'admin',
            'permissions' => ['ai_consumption'],
        ]);
        AiUsageLog::factory()->count(3)->create();
        Cache::flush();

        // Act
        $response = $this->actingAs($user)->get(route('ai.consumption'));

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('ai-consumption')
                ->has('summary')
                ->has('logs')
        );
    }
}
