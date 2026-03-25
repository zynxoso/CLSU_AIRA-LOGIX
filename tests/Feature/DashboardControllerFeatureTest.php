<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IctServiceRequest;
use App\Models\User;

class DashboardControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_loads_for_authorized_user()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);
        IctServiceRequest::factory()->count(2)->create(['status' => 'Open']);
        IctServiceRequest::factory()->count(1)->create(['status' => 'Resolved']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('dashboard'); // Inertia page name
    }

    public function test_dashboard_page_forbidden_for_unauthorized_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [],
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(403);
    }
}
