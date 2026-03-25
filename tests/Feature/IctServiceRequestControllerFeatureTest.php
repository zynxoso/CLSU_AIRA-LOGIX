<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IctServiceRequest;
use App\Models\User;

class IctServiceRequestControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_page_loads_for_authorized_user()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);
        $response = $this->actingAs($user)->get(route('ict.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('intake'));
    }

    public function test_edit_page_loads_for_authorized_user()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);
        $request = IctServiceRequest::factory()->create();
        $response = $this->actingAs($user)->get(route('ict.edit', $request->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('requests/edit'));
    }

    public function test_intake_page_forbidden_for_unauthorized_user()
    {
        $user = User::factory()->create(['role' => 'user', 'permissions' => []]);
        $response = $this->actingAs($user)->get(route('ict.create'));
        $response->assertStatus(403);
    }

    public function test_edit_page_forbidden_for_unauthorized_user()
    {
        $user = User::factory()->create(['role' => 'user', 'permissions' => []]);
        $request = IctServiceRequest::factory()->create();
        $response = $this->actingAs($user)->get(route('ict.edit', $request->id));
        $response->assertStatus(403);
    }
}
