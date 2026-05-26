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
    /** @var list<string> */
    public const ADMIN_ROLE_NAMES = [
        'admin_grade_school',
        'admin_junior_high',
        'admin',
        'principal',
        'super_admin',
    ];

    /** Roles the principal may assign when creating users. */
    public const PRINCIPAL_ASSIGNABLE_ROLE_NAMES = [
        'admin_grade_school',
        'admin_junior_high',
        'admin',
        'teacher_grade_school',
        'teacher_junior_high',
        'teacher',
        'shared_teacher',
    ];

    /** @var list<string> */
    public const TEACHER_ROLE_NAMES = [
        'teacher_grade_school',
        'teacher_junior_high',
        'teacher',
    ];

    /** Known seeded admin emails (repair if legacy login logic changed their role). */
    private const KNOWN_ADMIN_EMAILS = [
        'admin.gradeschool@spup.edu.ph'  => 'admin_grade_school',
        'admin.juniorhigh@spup.edu.ph' => 'admin_junior_high',
    ];

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
            'admin' => ($user->school_level ?? '') === 'grade_school'
                ? 'grade-school-admin.dashboard'
                : 'admin.dashboard',
            'admin_junior_high' => 'admin.dashboard',
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

    public static function isAdminRole(?string $roleName): bool
    {
        return $roleName !== null && in_array($roleName, self::ADMIN_ROLE_NAMES, true);
    }

    public static function isTeacherRole(?string $roleName): bool
    {
        return $roleName !== null && in_array($roleName, self::TEACHER_ROLE_NAMES, true);
    }

    public static function isSharedTeacherRole(?string $roleName): bool
    {
        return $roleName === 'shared_teacher';
    }

    /**
     * Restore seeded GS/JH admin accounts if a prior bug overwrote their role as teacher.
     */
    /**
     * Normalize role/school_level after principal provisioning or legacy data so login reaches the right portal.
     */
    public static function repairAccountForPortalAccess(?User $user = null): void
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return;
        }

        self::repairKnownAdminAccounts($user);
        $user = self::prepareUser($user);

        $roleName = $user->role?->name;

        if ($roleName === 'admin_grade_school' && $user->school_level !== 'grade_school') {
            $user->forceFill(['school_level' => 'grade_school'])->saveQuietly();
        }

        if ($roleName === 'admin_junior_high' && $user->school_level !== 'junior_high') {
            $user->forceFill(['school_level' => 'junior_high'])->saveQuietly();
        }

        if ($roleName === 'shared_teacher' && empty($user->school_level)) {
            $user->forceFill(['school_level' => 'grade_school'])->saveQuietly();
        }

        if (self::isTeacherRole($roleName) || $roleName === 'shared_teacher') {
            self::repairTeacherAccount($user);
            self::normalizeTeacherSchoolLevel($user);
        }
    }

    public static function portalLabelForUser(?User $user = null): string
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return 'login page';
        }

        return match (self::homeRouteName($user)) {
            'grade-school-admin.dashboard' => 'Grade School Admin dashboard',
            'admin.dashboard' => 'Junior High Admin dashboard',
            'grade-school-teacher.dashboard' => 'Grade School Teacher dashboard',
            'teacher.dashboard' => 'Junior High Teacher dashboard',
            'shared-teacher.dashboard' => 'Shared Teacher dashboard',
            'principal.dashboard' => 'Principal dashboard',
            default => 'correct portal',
        };
    }

    public static function repairKnownAdminAccounts(?User $user = null): void
    {
        $user = self::prepareUser($user);
        if (! $user) {
            return;
        }

        $email = strtolower(trim((string) $user->email));
        $expectedRoleName = self::KNOWN_ADMIN_EMAILS[$email] ?? null;
        if ($expectedRoleName === null || $user->role?->name === $expectedRoleName) {
            return;
        }

        $role = Role::query()->where('name', $expectedRoleName)->first();
        if (! $role) {
            return;
        }

        $user->forceFill([
            'role_id'      => $role->id,
            'school_level' => $expectedRoleName === 'admin_grade_school' ? 'grade_school' : 'junior_high',
        ])->saveQuietly();
        $user->load('role');
    }

    /**
     * Fix legacy teacher accounts only — never change admin or shared-teacher roles.
     */
    public static function repairTeacherAccount(?User $user = null): void
    {
        $user = self::prepareUser($user);
        if (! $user || ! self::isTeacherRole($user->role?->name)) {
            return;
        }

        $roleName = $user->role->name;

        if ($roleName === 'teacher_grade_school' || ($roleName === 'teacher' && $user->school_level === 'grade_school')) {
            $gsTeacher = Role::query()->where('name', 'teacher_grade_school')->first();
            if ($gsTeacher && $roleName !== 'teacher_grade_school') {
                $user->forceFill([
                    'role_id'      => $gsTeacher->id,
                    'school_level' => 'grade_school',
                ])->saveQuietly();
                $user->load('role');
            }

            return;
        }

        if (in_array($roleName, ['teacher_junior_high', 'teacher'], true)) {
            $jhTeacher = Role::query()->where('name', 'teacher_junior_high')->first()
                ?? Role::query()->where('name', 'teacher')->first();
            if ($jhTeacher && $roleName === 'teacher' && $user->school_level !== 'grade_school') {
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
        $user = self::prepareUser($user);
        if (! $user || ! self::isTeacherRole($user->role?->name)) {
            return;
        }

        self::repairTeacherAccount($user);
        $user = self::prepareUser($user);

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
        $message = 'You do not have access to this portal.';

        if ($home === 'login' || $request->routeIs($home)) {
            abort(403, 'Your account is not set up for this portal. Ask your administrator to assign the correct role.');
        }

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return redirect()->route($home)->with('error', $message);
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
