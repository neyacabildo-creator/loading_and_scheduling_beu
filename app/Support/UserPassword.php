<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserPassword
{
    public static function aesKey(): string
    {
        return (string) config('app.password_aes_key', 'spup_ict_2026');
    }

    /**
     * Plain-text password when stored with AES_ENCRYPT, or null.
     */
    public static function plainText(User $user): ?string
    {
        if (! Schema::hasColumn('users', 'password_encrypted')) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT CAST(AES_DECRYPT(password_encrypted, ?) AS CHAR) AS plain_password FROM users WHERE id = ?',
            [self::aesKey(), $user->id]
        );

        $plain = $row->plain_password ?? null;

        return is_string($plain) && $plain !== '' ? $plain : null;
    }

    public static function storeEncrypted(int $userId, string $plainPassword): void
    {
        if (! Schema::hasColumn('users', 'password_encrypted')) {
            return;
        }

        DB::statement(
            'UPDATE users SET password_encrypted = AES_ENCRYPT(?, ?) WHERE id = ?',
            [$plainPassword, self::aesKey(), $userId]
        );
    }
}
