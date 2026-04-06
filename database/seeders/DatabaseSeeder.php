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
        // Only seed privileged users in local or testing environments
        if (app()->environment(['local', 'testing'])) {
            $superAdminPassword = 'password123';
            $testUserPassword = 'password123';

            User::updateOrCreate([
                'email' => 'superadmin@example.com',
            ], [
                'name' => 'Super Admin',
                'password' => Hash::make($superAdminPassword),
                'role' => 'super_admin',
                'permissions' => [
                    'dashboard',
                    'smart_scan',
                    'documentation',
                    'ai_consumption',
                ],
                'email_verified_at' => now(),
                'must_reset_password' => true, // force reset on first login
            ]);

            User::updateOrCreate([
                'email' => 'test@example.com',
            ], [
                'name' => 'Test User',
                'password' => Hash::make($testUserPassword),
                'role' => 'admin',
                'permissions' => [
                    'dashboard',
                    'smart_scan',
                    'documentation',
                ],
                'email_verified_at' => now(),
                'must_reset_password' => true,
            ]);

            // Output credentials for local devs
            echo "\n[dev] Super Admin seeded: superadmin@example.com | password: {$superAdminPassword}\n";
            echo "[dev] Test User seeded: test@example.com | password: {$testUserPassword}\n";
        } else {
            // In production, do NOT seed privileged users with default/weak credentials
            echo "\n[secure] Privileged user seeding is disabled outside local/testing environments.\n";
        }
    }
}
