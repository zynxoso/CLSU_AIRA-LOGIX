<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TolerantEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Mixed datasets may contain legacy plaintext or values encrypted with a different key.
            // If it looks like a Laravel payload, treat it as unreadable and return null.
            if ($this->looksLikeEncryptedPayload($value)) {
                return null;
            }

            return $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString((string) $value);
    }

    private function looksLikeEncryptedPayload(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && array_key_exists('iv', $json)
            && array_key_exists('value', $json)
            && array_key_exists('mac', $json);
    }
}
