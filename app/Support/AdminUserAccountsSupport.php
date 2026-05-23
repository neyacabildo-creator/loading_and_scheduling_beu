<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * User Accounts and faculty-load teacher queries for GS / JH admins.
 */
class AdminUserAccountsSupport
{
    public static function normalizePersonName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($parts)
            ->map(function (string $part) {
                $first = mb_substr($part, 0, 1);
                $rest = mb_substr($part, 1);

                return mb_strtoupper($first) . mb_strtolower($rest);
            })
            ->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withNormalizedNames(array $data): array
    {
        if (array_key_exists('first_name', $data)) {
            $data['first_name'] = self::normalizePersonName($data['first_name'] ?? null);
        }
        if (array_key_exists('last_name', $data)) {
            $data['last_name'] = self::normalizePersonName($data['last_name'] ?? null);
        }
        if (isset($data['first_name'], $data['last_name'])) {
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        }

        return $data;
    }

    /** Roles shown on the User Accounts management page. */
    public const ACCOUNT_ROLE_NAMES = [
        'admin_grade_school',
        'admin_junior_high',
        'teacher_grade_school',
        'teacher_junior_high',
        'teacher',
        'shared_teacher',
    ];

    /** Roles that may be assigned a faculty load (excludes school admins). */
    public const FACULTY_ASSIGNABLE_ROLE_NAMES = [
        'teacher_grade_school',
        'teacher_junior_high',
        'teacher',
        'shared_teacher',
    ];

    public static function scopeUserAccounts(Builder $query, string $schoolLevel): Builder
    {
        return $query
            ->where(function (Builder $q) use ($schoolLevel) {
                $q->where('school_level', $schoolLevel)
                    ->orWhereHas('role', fn (Builder $r) => $r->where('name', 'shared_teacher'));
            })
            ->whereHas('role', fn (Builder $r) => $r->whereIn('name', self::ACCOUNT_ROLE_NAMES));
    }

    public static function scopeFacultyAssignable(Builder $query, string $schoolLevel): Builder
    {
        return $query
            ->where(function (Builder $q) use ($schoolLevel) {
                $q->where('school_level', $schoolLevel)
                    ->orWhereHas('role', fn (Builder $r) => $r->where('name', 'shared_teacher'));
            })
            ->whereHas('role', fn (Builder $r) => $r->whereIn('name', self::FACULTY_ASSIGNABLE_ROLE_NAMES));
    }

    public static function findUserAccount(string $schoolLevel, int $id): User
    {
        return self::scopeUserAccounts(User::query(), $schoolLevel)->findOrFail($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapUsersForApi(iterable $users): array
    {
        $mapped = [];
        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }
            $data = $user->toArray();
            $data['plain_password'] = UserPasswordSupport::decryptPlainPassword($user->id);
            $mapped[] = $data;
        }

        return $mapped;
    }

    /**
     * Teachers for the Add Faculty Load dropdown (includes shared-teacher assigned subjects).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function mapUsersForFacultyApi(iterable $users, string $schoolLevel): array
    {
        $connection = $schoolLevel === 'grade_school' ? 'mysql_gs' : 'mysql_jh';
        $mapped = self::mapUsersForApi($users);

        foreach ($mapped as &$user) {
            $roleName = $user['role']['name'] ?? null;
            $user['role_name'] = $roleName;
            $user['assigned_subjects'] = $roleName === 'shared_teacher'
                ? SharedTeacherSupport::assignedSubjectsForFaculty($connection, (int) ($user['id'] ?? 0), $schoolLevel)
                : [];
        }
        unset($user);

        return $mapped;
    }
}
