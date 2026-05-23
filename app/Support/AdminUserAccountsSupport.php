<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * User Accounts and faculty-load teacher queries for GS / JH admins.
 */
class AdminUserAccountsSupport
{
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
}
