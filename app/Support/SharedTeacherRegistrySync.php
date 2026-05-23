<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registers shared teachers in mysql_jh / mysql_gs shared_teachers tables.
 */
class SharedTeacherRegistrySync
{
    public static function syncFromAdminRequest(User $user, Role $role, Request $request): void
    {
        if ($role->name !== 'shared_teacher') {
            return;
        }

        $subjects = array_values(array_filter([
            trim($request->input('subject1', '')),
            trim($request->input('subject2', '')),
        ]));

        $base = [
            'faculty_id'   => $user->id,
            'teacher_name' => $user->name,
            'email'        => $user->email,
            'department'   => $subjects[0] ?? 'Unassigned',
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        if ($subjects !== []) {
            $base['subjects'] = json_encode($subjects);
        }

        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! SharedTeacherSupport::tableExists($connection)) {
                continue;
            }

            $row = array_merge($base, [
                'school_level' => $connection === 'mysql_jh' ? 'junior_high' : 'grade_school',
            ]);

            if (! Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
                unset($row['subjects']);
            }
            if (! Schema::connection($connection)->hasColumn('shared_teachers', 'department')) {
                unset($row['department']);
            }

            DB::connection($connection)->table('shared_teachers')->insertOrIgnore($row);
        }
    }
}
