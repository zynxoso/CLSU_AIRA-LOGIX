<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->boolean('must_reset_password')->default(false)->after('permissions');
        });

        DB::table('users')
            ->whereNull('permissions')
            ->update([
                'permissions' => json_encode([
                    'dashboard',
                    'smart_scan',
                    'documentation',
                ]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions', 'must_reset_password']);
        });
    }
};
