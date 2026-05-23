<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * Faculty loads are created manually via Add Faculty Load — not on user registration.
 */
class FacultyLoadProvisioning
{
    /**
     * @deprecated Faculty load rows are no longer auto-created when admins add teacher accounts.
     */
    public static function ensureForNewTeacher(User $user, ?Role $role): void
    {
        // Intentionally empty: teachers appear in the Add Faculty dropdown from User Accounts only.
    }
}
