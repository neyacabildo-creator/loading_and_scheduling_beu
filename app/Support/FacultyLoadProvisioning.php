<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures new teacher accounts have faculty_load rows in admin databases.
 */
class FacultyLoadProvisioning
{
    public static function ensureForNewTeacher(User $user, ?Role $role): void
    {
        if (! $role || ! in_array($role->name, ['teacher', 'teacher_grade_school', 'teacher_junior_high'], true)) {
            return;
        }

        $base = [
            'faculty_id'       => $user->id,
            'teacher_name'     => $user->name,
            'grade_level'      => null,
            'subject'          => null,
            'classes_assigned' => 0,
            'load_hours'       => 0,
            'notes'            => 'Auto-created on account registration.',
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('faculty_loads')) {
                continue;
            }

            $exists = DB::connection($connection)
                ->table('faculty_loads')
                ->where('faculty_id', $user->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $row = $base;
            $row['status'] = 'available';

            if (Schema::connection($connection)->hasColumn('faculty_loads', 'department')) {
                $row['department'] = 'Unassigned';
            }

            DB::connection($connection)->table('faculty_loads')->insert($row);
        }
    }
}
