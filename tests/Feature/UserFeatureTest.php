<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user()
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ];
        $user = User::create($data);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'jane@example.com']);
    }

    public function test_list_users()
    {
        User::factory()->count(2)->create();
        $this->assertEquals(2, User::count());
    }
}
