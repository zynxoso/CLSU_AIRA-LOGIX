<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $cacheControl = $response->headers->get('Cache-Control') ?? '';
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    public function test_logout_sets_the_inertia_clear_history_session_flag()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertSessionHas('inertia.clear_history', true);
    }

    public function test_users_cannot_access_protected_routes_after_logging_out()
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'permissions' => ['dashboard', 'smart_scan', 'documentation', 'ai_consumption'],
        ]);

        $this->actingAs($user)->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/superadmin/users')->assertRedirect('/login');
    }
}
