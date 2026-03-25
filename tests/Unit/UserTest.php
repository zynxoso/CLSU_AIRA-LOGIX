<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_fields()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ];
        $user = new User($data);
        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $this->assertTrue(\Illuminate\Support\Facades\Hash::check($value, $user->password));
            } else {
                $this->assertEquals($value, $user->$key);
            }
        }
    }

    public function test_is_super_admin()
    {
        $user = User::factory()->make(['role' => 'super_admin']);
        $this->assertTrue($user->isSuperAdmin());
        $user = User::factory()->make(['role' => 'admin']);
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_has_permission()
    {
        $user = User::factory()->make(['role' => 'admin', 'permissions' => ['dashboard', 'ai_consumption']]);
        $this->assertTrue($user->hasPermission('dashboard'));
        $this->assertFalse($user->hasPermission('nonexistent'));
    }

    public function test_has_any_permission()
    {
        $user = User::factory()->make(['role' => 'admin', 'permissions' => ['dashboard', 'ai_consumption']]);
        $this->assertTrue($user->hasAnyPermission(['ai_consumption', 'other']));
        $this->assertFalse($user->hasAnyPermission(['other', 'none']));
    }
}
