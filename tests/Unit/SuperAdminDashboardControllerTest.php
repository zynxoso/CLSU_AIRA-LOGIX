<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\IctServiceRequest;
use App\Models\AiUsageLog;

class SuperAdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dashboard_returns_metrics()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        IctServiceRequest::factory()->count(2)->create(['status' => 'Open']);
        AiUsageLog::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('superadmin.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('superadmin/dashboard')
                ->has('metrics')
        );
    }
}
