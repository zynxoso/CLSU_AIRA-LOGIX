<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'permissions' => [
                'dashboard',
                'smart_scan',
                'documentation',
                'ai_consumption',
            ],
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'permissions' => [
                'dashboard',
                'smart_scan',
                'documentation',
            ],
            'email_verified_at' => now(),
        ]);
    }
}
