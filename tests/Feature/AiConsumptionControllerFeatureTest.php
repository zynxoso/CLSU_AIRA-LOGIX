<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AiUsageLog;
use App\Models\User;

class AiConsumptionControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_consumption_page_loads_for_authorized_user()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['ai_consumption'],
        ]);
        AiUsageLog::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('ai.consumption'));
        $response->assertStatus(200);
        $response->assertSee('ai-consumption'); // Inertia page name
    }

    public function test_ai_consumption_page_forbidden_for_unauthorized_user()
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('ai.consumption'));
        $response->assertStatus(403);
    }
}
