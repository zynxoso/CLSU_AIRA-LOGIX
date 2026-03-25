<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
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
            $table->text('name')->nullable()->change();
            $table->longText('permissions')->nullable()->change();
        });

        DB::table('users')
            ->select(['id', 'name', 'permissions'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $newName = $this->encryptStringIfNeeded($user->name);
                    $newPermissions = $this->encryptJsonIfNeeded($user->permissions);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'name' => $newName,
                            'permissions' => $newPermissions,
                        ]);
                }
            });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->longText('metadata')->nullable()->change();
        });

        DB::table('ai_usage_logs')
            ->select(['id', 'metadata'])
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $newMetadata = $this->encryptJsonIfNeeded($log->metadata);

                    DB::table('ai_usage_logs')
                        ->where('id', $log->id)
                        ->update([
                            'metadata' => $newMetadata,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration performs in-place encryption and is intentionally irreversible.
    }

    private function encryptStringIfNeeded(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            $value = (string) $value;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    private function encryptJsonIfNeeded(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && $this->isEncrypted($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $payload = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        } else {
            $payload = $value;
        }

        $encoded = json_encode($payload);

        if ($encoded === false) {
            $encoded = json_encode(['value' => (string) $payload], JSON_THROW_ON_ERROR);
        }

        return Crypt::encryptString($encoded);
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
