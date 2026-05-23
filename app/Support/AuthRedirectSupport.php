<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Post-login dashboard route resolution by role (avoids redirect loops).
 */
class AuthRedirectSupport
{
    public static function homeRouteName(?User $user = null): string
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return 'login';
        }

        $roleName = $user->role?->name;

        return match ($roleName) {
            'principal', 'super_admin' => 'principal.dashboard',
            'admin_grade_school' => 'grade-school-admin.dashboard',
            'admin_junior_high', 'admin' => 'admin.dashboard',
            'teacher_grade_school' => 'grade-school-teacher.dashboard',
            'teacher_junior_high', 'teacher' => self::canAccessJuniorHighTeacherPortal($user)
                ? 'teacher.dashboard'
                : 'grade-school-teacher.dashboard',
            'shared_teacher' => 'shared-teacher.dashboard',
            default => match (true) {
                self::canAccessGradeSchoolTeacherPortal($user) => 'grade-school-teacher.dashboard',
                self::canAccessJuniorHighTeacherPortal($user) => 'teacher.dashboard',
                default => 'login',
            },
        };
    }

    public static function applyDepartmentSession(?User $user = null): void
    {
        $user = self::prepareUser($user);
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

    /**
     * Fix legacy accounts: correct role_id and school_level for teacher portals.
     */
    public static function repairTeacherAccount(?User $user = null): void
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return;
        }

        $roleName = $user->role?->name;

        if ($user->school_level === 'grade_school' || $roleName === 'teacher_grade_school') {
            $gsTeacher = Role::query()->where('name', 'teacher_grade_school')->first();
            if ($gsTeacher && $roleName !== 'teacher_grade_school') {
                $user->forceFill([
                    'role_id'      => $gsTeacher->id,
                    'school_level' => 'grade_school',
                ])->saveQuietly();
                $user->load('role');

                return;
            }
        }

        if ($user->school_level === 'junior_high' || in_array($roleName, ['teacher_junior_high', 'teacher'], true)) {
            $jhTeacher = Role::query()->where('name', 'teacher_junior_high')->first()
                ?? Role::query()->where('name', 'teacher')->first();
            if ($jhTeacher && ! in_array($user->role?->name, ['teacher_junior_high', 'teacher'], true)) {
                $user->forceFill([
                    'role_id'      => $jhTeacher->id,
                    'school_level' => 'junior_high',
                ])->saveQuietly();
                $user->load('role');
            }
        }
    }

    public static function normalizeTeacherSchoolLevel(?User $user = null): void
    {
        self::repairTeacherAccount($user);

        $user = self::prepareUser($user);
        if (! $user) {
            return;
        }

        $expected = match ($user->role?->name) {
            'teacher_grade_school' => 'grade_school',
            'teacher_junior_high', 'teacher' => 'junior_high',
            default => null,
        };

        if ($expected !== null && $user->school_level !== $expected) {
            $user->forceFill(['school_level' => $expected])->saveQuietly();
        }
    }

    public static function canAccessJuniorHighTeacherPortal(?User $user = null): bool
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return false;
        }

        $roleName = $user->role?->name;

        if ($roleName === 'teacher_grade_school') {
            return false;
        }

        if (in_array($roleName, ['teacher_junior_high', 'teacher'], true)) {
            return $user->school_level !== 'grade_school';
        }

        return false;
    }

    public static function canAccessGradeSchoolTeacherPortal(?User $user = null): bool
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return false;
        }

        $roleName = $user->role?->name;

        if ($roleName === 'teacher_grade_school') {
            return true;
        }

        return $roleName === 'teacher' && $user->school_level === 'grade_school';
    }

    public static function isJuniorHighTeacher(?User $user = null): bool
    {
        return self::canAccessJuniorHighTeacherPortal($user);
    }

    public static function isGradeSchoolTeacher(?User $user = null): bool
    {
        return self::canAccessGradeSchoolTeacherPortal($user);
    }

    /**
     * When access is denied: redirect to home portal, or abort if that would loop.
     */
    public static function redirectAwayFromPortal(?User $user, Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = self::prepareUser($user);
        $home = self::homeRouteName($user);

        if ($home === 'login' || $request->routeIs($home)) {
            abort(403, 'Your account is not set up for this portal. Ask your administrator to assign the correct teacher role.');
        }

        return redirect()->route($home);
    }

    private static function prepareUser(?User $user): ?User
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        return $user;
    }
}
