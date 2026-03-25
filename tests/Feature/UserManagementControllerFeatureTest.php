<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a superadmin user for authorization
        $this->superadmin = User::factory()->create([
            'role' => 'super_admin',
            'permissions' => ['manage-admins'],
        ]);
    }

    public function test_admin_index_accessible_by_superadmin()
    {
        $response = $this->actingAs($this->superadmin)->get(route('superadmin.users.index'));
        $response->assertStatus(200);
    }

    public function test_admin_store_creates_admin()
    {
        $data = [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'permissions' => ['dashboard'],
        ];
        $response = $this->actingAs($this->superadmin)->post(route('superadmin.users.store'), $data);
        $response->assertRedirect(route('superadmin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_admin_update_modifies_admin()
    {
        $admin = User::factory()->create(['role' => 'admin', 'permissions' => ['dashboard']]);
        $data = [
            'name' => 'Updated Admin',
            'email' => $admin->email,
            'permissions' => ['dashboard'],
            'password' => null,
            'password_confirmation' => null,
        ];
        $response = $this->actingAs($this->superadmin)->put(route('superadmin.users.update', $admin), $data);
        $response->assertRedirect(route('superadmin.users.index'));
        $admin->refresh();
        $this->assertEquals('Updated Admin', $admin->name);
    }

    public function test_admin_destroy_deletes_admin()
    {
        $admin = User::factory()->create(['role' => 'admin', 'permissions' => ['dashboard']]);
        $response = $this->actingAs($this->superadmin)->delete(route('superadmin.users.destroy', $admin));
        $response->assertRedirect(route('superadmin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }
}
