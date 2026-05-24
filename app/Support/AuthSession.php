<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthSession
{
    public static function sessionTable(): string
    {
        return (string) config('session.table', 'sessions');
    }

    public static function sessionLifetimeMinutes(): int
    {
        return (int) config('session.lifetime', 120);
    }

    public static function usesDatabaseSessions(): bool
    {
        return config('session.driver') === 'database'
            && Schema::hasTable(self::sessionTable());
    }

    public static function hasActiveSessionColumn(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'active_session_id');
    }

    public static function assignActiveSession(User $user, string $sessionId): void
    {
        if (! self::hasActiveSessionColumn()) {
            return;
        }

        $payload = [
            'active_session_id' => $sessionId,
        ];

        if (Schema::hasColumn('users', 'active_session_at')) {
            $payload['active_session_at'] = now();
        }

        $user->forceFill($payload)->save();
    }

    public static function touchActiveSession(User $user): void
    {
        if (! self::hasActiveSessionColumn() || ! Schema::hasColumn('users', 'active_session_at')) {
            return;
        }

        $user->forceFill(['active_session_at' => now()])->save();
    }

    public static function clearActiveSession(User $user): void
    {
        if (! self::hasActiveSessionColumn()) {
            return;
        }

        $payload = ['active_session_id' => null];
        if (Schema::hasColumn('users', 'active_session_at')) {
            $payload['active_session_at'] = null;
        }

        $user->forceFill($payload)->save();
    }

    /**
     * Reload user from DB so we always compare the latest active session.
     */
    public static function freshUser(User $user): User
    {
        return $user->fresh() ?? $user;
    }

    /**
     * True when this account is already signed in on a different browser/tab.
     */
    public static function hasActiveSessionElsewhere(User $user): bool
    {
        $user = self::freshUser($user);

        if (! self::hasActiveSessionColumn()) {
            return false;
        }

        if (empty($user->active_session_id)) {
            return false;
        }

        return self::storedSessionIsAlive($user);
    }

    /**
     * Only the browser that last logged in may use this account.
     * When tracking columns are missing, defer to Laravel's session cookie only.
     */
    public static function isActiveSession(User $user, string $sessionId): bool
    {
        if (! self::hasActiveSessionColumn()) {
            return true;
        }

        $user = self::freshUser($user);

        if (empty($user->active_session_id)) {
            return false;
        }

        if (! hash_equals((string) $user->active_session_id, $sessionId)) {
            return false;
        }

        return self::storedSessionIsAlive($user);
    }

    public static function storedSessionIsAlive(User $user): bool
    {
        if (empty($user->active_session_id)) {
            return false;
        }

        if (self::usesDatabaseSessions()) {
            $cutoff = now()->subMinutes(self::sessionLifetimeMinutes())->getTimestamp();

            return DB::table(self::sessionTable())
                ->where('id', $user->active_session_id)
                ->where('last_activity', '>=', $cutoff)
                ->exists();
        }

        if (! Schema::hasColumn('users', 'active_session_at') || $user->active_session_at === null) {
            return false;
        }

        $seenAt = $user->active_session_at;
        if (! $seenAt instanceof CarbonInterface) {
            try {
                $seenAt = Carbon::parse($seenAt);
            } catch (\Throwable) {
                return false;
            }
        }

        return $seenAt->gte(now()->subMinutes(self::sessionLifetimeMinutes()));
    }

    /**
     * Keep only the current browser session for this user (logs out other browsers/tabs).
     */
    public static function invalidateOtherSessions(int $userId, string $currentSessionId): void
    {
        if (! self::usesDatabaseSessions()) {
            return;
        }

        DB::table(self::sessionTable())
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Invalidate persistent "remember me" cookies on every device.
     */
    public static function rotateRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }
}
