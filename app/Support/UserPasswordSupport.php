<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserPasswordSupport
{
    public static function aesKey(): string
    {
        return (string) env('MYSQL_AES_KEY', 'spup_ict_2026');
    }

    /**
     * Store reversible copy for admin display (alongside bcrypt hash).
     */
    public static function storeEncryptedCopy(int $userId, string $plainPassword): void
    {
        if (! Schema::hasColumn('users', 'password_encrypted')) {
            return;
        }

        DB::statement(
            'UPDATE users SET password_encrypted = AES_ENCRYPT(?, ?) WHERE id = ?',
            [$plainPassword, self::aesKey(), $userId]
        );
    }

    /**
     * Decrypt password_encrypted for admin User Accounts view.
     */
    public static function decryptPlainPassword(?int $userId): ?string
    {
        if (! $userId || ! Schema::hasColumn('users', 'password_encrypted')) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT CAST(AES_DECRYPT(password_encrypted, ?) AS CHAR) AS plain_password FROM users WHERE id = ?',
            [self::aesKey(), $userId]
        );

        $plain = $row->plain_password ?? null;

        return is_string($plain) && $plain !== '' ? $plain : null;
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $users
     * @return array<int, array<string, mixed>>
     */
    public static function attachPlainPasswords(iterable $users): array
    {
        $out = [];
        foreach ($users as $user) {
            $arr = is_array($user) ? $user : (array) $user;
            $id = (int) ($arr['id'] ?? 0);
            $arr['plain_password'] = self::decryptPlainPassword($id);
            $out[] = $arr;
        }

        return $out;
    }
}
