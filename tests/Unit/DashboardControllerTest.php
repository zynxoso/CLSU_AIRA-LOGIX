<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IctServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_index_returns_inertia_view_with_metrics()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);
        IctServiceRequest::factory()->count(2)->create(['status' => 'Open']);
        IctServiceRequest::factory()->count(1)->create(['status' => 'Resolved']);
        Cache::flush();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('dashboard')
                ->has('metrics')
        );
    }
}
