<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class SuperAdminDashboardControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dashboard_page_loads_for_super_admin()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($user)->get(route('superadmin.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('superadmin/dashboard'));
    }

    public function test_superadmin_dashboard_page_forbidden_for_non_super_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get(route('superadmin.dashboard'));
        $response->assertStatus(403);
    }
}
