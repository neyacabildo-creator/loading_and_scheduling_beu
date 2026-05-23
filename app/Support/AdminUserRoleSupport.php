<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Role options and validation for GS / JH admin user-account forms.
 */
class AdminUserRoleSupport
{
    /** @var array<string, list<string>> */
    public const PORTAL_ROLE_NAMES = [
        'junior_high'  => ['admin_junior_high', 'teacher_junior_high', 'shared_teacher'],
        'grade_school' => ['admin_grade_school', 'teacher_grade_school', 'shared_teacher'],
    ];

    /**
     * @return Collection<int, Role>
     */
    public static function roleOptionsForPortal(string $portal): Collection
    {
        $names = self::PORTAL_ROLE_NAMES[$portal] ?? [];

        return Role::query()
            ->whereIn('name', $names)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $names, true))
            ->values();
    }

    public static function validateRoleForPortal(string $portal, int $roleId): Role
    {
        $allowed = self::PORTAL_ROLE_NAMES[$portal] ?? [];
        $role = Role::query()->find($roleId);

        if (! $role || ! in_array($role->name, $allowed, true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Please select a valid role for this school level.',
            ]);
        }

        return $role;
    }

    public static function schoolLevelForNewUser(Role $role, string $portal): string
    {
        return match ($role->name) {
            'admin_grade_school', 'teacher_grade_school' => 'grade_school',
            'admin_junior_high', 'teacher_junior_high' => 'junior_high',
            'shared_teacher' => $portal === 'grade_school' ? 'grade_school' : 'junior_high',
            default => $portal === 'grade_school' ? 'grade_school' : 'junior_high',
        };
    }
}
