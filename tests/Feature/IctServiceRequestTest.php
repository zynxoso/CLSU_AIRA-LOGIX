<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IctServiceRequest;
use App\Models\User;

class IctServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_route_renders_intake_view()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('ict.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('intake')); // Checks for the Inertia page name
    }

    public function test_edit_route_renders_edit_view_with_request()
    {
        $user = User::factory()->create();
        $request = IctServiceRequest::factory()->create();
        $response = $this->actingAs($user)->get(route('ict.edit', $request->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('requests/edit')); // Checks for the Inertia page name
        $response->assertSee($request->id);
    }
}
