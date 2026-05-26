<?php

namespace App\Support;

use App\Models\FacultyLoad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Principal user create/update: school level, login routing, faculty loads, shared teachers.
 */
class PrincipalUserProvisioningSupport
{
    /** @var list<string> */
    public const SCHOOL_LEVEL_REQUIRED_ROLES = [
        'admin',
        'admin_grade_school',
        'admin_junior_high',
        'teacher',
        'teacher_grade_school',
        'teacher_junior_high',
    ];

    public static function requiresSchoolLevel(string $roleName): bool
    {
        return in_array($roleName, self::SCHOOL_LEVEL_REQUIRED_ROLES, true);
    }

    public static function resolveSchoolLevel(Role $role, ?string $schoolLevel): ?string
    {
        $level = trim((string) $schoolLevel);

        if (str_contains($role->name, 'grade_school')) {
            return 'grade_school';
        }

        if (str_contains($role->name, 'junior_high')) {
            return 'junior_high';
        }

        if ($role->name === 'shared_teacher') {
            return $level !== '' ? $level : 'grade_school';
        }

        return $level !== '' ? $level : null;
    }

    public static function isTeacherRole(string $roleName): bool
    {
        return $roleName === 'shared_teacher'
            || str_contains($roleName, 'teacher');
    }

    public static function isAdminRole(string $roleName): bool
    {
        return in_array($roleName, ['admin', 'admin_grade_school', 'admin_junior_high'], true);
    }

    /**
     * Map legacy/generic role names to portal-specific roles when school level is known.
     */
    public static function normalizeAssignableRole(Role $role, ?string $schoolLevel): Role
    {
        $level = trim((string) $schoolLevel);

        $target = match ($role->name) {
            'admin' => match ($level) {
                'grade_school' => 'admin_grade_school',
                'junior_high' => 'admin_junior_high',
                default => null,
            },
            'teacher' => match ($level) {
                'grade_school' => 'teacher_grade_school',
                'junior_high' => 'teacher_junior_high',
                default => null,
            },
            default => null,
        };

        if ($target === null) {
            return $role;
        }

        $resolved = Role::query()->where('name', $target)->first();

        return $resolved ?? $role;
    }

    /**
     * After principal creates a user: faculty load rows + shared_teacher registry.
     */
    public static function provisionNewUser(User $user, Role $role, Request $request): void
    {
        if (! self::isTeacherRole($role->name)) {
            return;
        }

        $loadData = [
            'faculty_id'       => $user->id,
            'teacher_name'     => $user->name,
            'department'       => 'Unassigned',
            'classes_assigned' => 0,
            'load_hours'       => 0,
            'status'           => 'available',
            'notes'            => 'Auto-created on account registration.',
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        if ($role->name === 'shared_teacher') {
            foreach (['mysql_jh', 'mysql_gs'] as $conn) {
                DB::connection($conn)->table('faculty_loads')->insertOrIgnore($loadData);
            }
            self::syncSharedTeacherRegistry($user, $request);

            return;
        }

        $schoolLevel = $user->school_level;
        if (! $schoolLevel) {
            return;
        }

        $schoolConn = FacultyLoadSupport::connectionForSchoolLevel($schoolLevel);
        config(['database.school_connection' => $schoolConn]);
        FacultyLoad::create(array_diff_key($loadData, array_flip(['created_at', 'updated_at'])));
    }

    /**
     * Keep shared_teacher registry aligned when principal changes role or profile.
     */
    public static function syncAfterUpdate(User $user, Role $role, Request $request, ?string $previousRoleName = null): void
    {
        if ($role->name === 'shared_teacher') {
            self::syncSharedTeacherRegistry($user, $request);

            return;
        }

        if ($previousRoleName === 'shared_teacher') {
            foreach (['mysql_jh', 'mysql_gs'] as $conn) {
                if (Schema::connection($conn)->hasTable('shared_teachers')) {
                    DB::connection($conn)->table('shared_teachers')->where('faculty_id', $user->id)->delete();
                }
            }
        }

        if (self::isTeacherRole($role->name) && ! FacultyLoad::query()->where('faculty_id', $user->id)->exists()) {
            self::provisionNewUser($user, $role, $request);
        }
    }

    public static function syncSharedTeacherRegistry(User $user, Request $request): void
    {
        $subjects = array_values(array_filter([
            trim($request->input('subject1', '')),
            trim($request->input('subject2', '')),
        ]));
        $department = $subjects[0] ?? 'Unassigned';
        $stData = [
            'faculty_id'   => $user->id,
            'teacher_name' => $user->name,
            'email'        => $user->email,
            'department'   => $department,
            'subjects'     => json_encode($subjects),
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('shared_teachers')) {
                continue;
            }
            $sl = $conn === 'mysql_jh' ? 'junior_high' : 'grade_school';
            DB::connection($conn)->table('shared_teachers')->updateOrInsert(
                ['faculty_id' => $user->id, 'school_level' => $sl],
                array_merge($stData, ['school_level' => $sl, 'updated_at' => now()])
            );
        }

        if ($subjects !== []) {
            SharedTeacherSupport::persistSubjectsOnUser($user->id, $subjects);
        }
    }
}
