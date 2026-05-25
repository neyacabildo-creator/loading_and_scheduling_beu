<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserProfileSupport
{
    public static function displayName(?User $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $full = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $full !== '' ? $full : (string) ($user->name ?? 'User');
    }

    public static function initials(?User $user): string
    {
        if (! $user) {
            return '?';
        }

        $first = strtoupper(substr((string) ($user->first_name ?? ''), 0, 1));
        $last = strtoupper(substr((string) ($user->last_name ?? ''), 0, 1));

        if ($first !== '' && $last !== '') {
            return $first . $last;
        }

        $name = self::displayName($user);

        return strtoupper(substr($name, 0, 2)) ?: '?';
    }

    public static function photoUrl(?User $user): ?string
    {
        if (! $user || empty($user->profile_photo_path)) {
            return null;
        }

        return asset('storage/' . ltrim((string) $user->profile_photo_path, '/'));
    }

    /**
     * Keep users.name in sync and refresh cached teacher_name on school DB request tables.
     */
    public static function syncTeacherNameReferences(User $user): void
    {
        $full = self::displayName($user);
        if ($full === '' || $full === 'User') {
            return;
        }

        if ($user->name !== $full) {
            $user->forceFill(['name' => $full])->saveQuietly();
        }

        $facultyId = (int) $user->id;
        $payload = ['teacher_name' => $full, 'updated_at' => now()];

        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                DB::connection($connection)->table('shared_teacher_requests')
                    ->where('faculty_id', $facultyId)
                    ->update($payload);
            }

            if (Schema::connection($connection)->hasTable('teacher_requests')) {
                $query = DB::connection($connection)->table('teacher_requests')
                    ->where('faculty_id', $facultyId);
                if (Schema::connection($connection)->hasColumn('teacher_requests', 'teacher_name')) {
                    $query->update($payload);
                }
            }
        }
    }
}
