<?php

namespace App\Support;

use App\Models\User;

/**
 * Post-login dashboard route resolution by role.
 */
class AuthRedirectSupport
{
    public static function homeRouteName(?User $user = null): string
    {
        $user ??= auth()->user();
        if (! $user) {
            return 'login';
        }

        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        $roleName = $user->role?->name;
        $schoolLevel = $user->school_level;

        return match ($roleName) {
            'principal', 'super_admin' => 'principal.dashboard',
            'admin_grade_school' => 'grade-school-admin.dashboard',
            'admin_junior_high', 'admin' => 'admin.dashboard',
            'teacher_grade_school' => 'grade-school-teacher.dashboard',
            'teacher_junior_high', 'teacher' => 'teacher.dashboard',
            'shared_teacher' => 'shared-teacher.dashboard',
            default => match ($schoolLevel) {
                'grade_school' => 'grade-school-teacher.dashboard',
                'junior_high'  => 'teacher.dashboard',
                default        => 'login',
            },
        };
    }

    public static function applyDepartmentSession(?User $user = null): void
    {
        $user ??= auth()->user();
        if (! $user?->role?->name) {
            return;
        }

        $roleName = $user->role->name;
        if (str_contains($roleName, 'grade_school') || $user->school_level === 'grade_school') {
            session(['department' => 'grade_school']);
        } elseif (str_contains($roleName, 'junior_high') || $user->school_level === 'junior_high') {
            session(['department' => 'junior_high']);
        }
    }
}
