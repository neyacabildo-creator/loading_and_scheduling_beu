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
    public static function syncFromAdminRequest(User $user, Role $role, Request $request, string $portal = 'junior_high'): void
    {
        if ($role->name !== 'shared_teacher') {
            return;
        }

        $subjects = array_values(array_filter([
            trim($request->input('subject1', '')),
            trim($request->input('subject2', '')),
        ]));

        $schoolLevel = $portal === 'grade_school' ? 'grade_school' : 'junior_high';
        $connection = $schoolLevel === 'grade_school' ? 'mysql_gs' : 'mysql_jh';

        if (! SharedTeacherSupport::tableExists($connection)) {
            return;
        }

        $row = [
            'faculty_id'   => $user->id,
            'teacher_name' => $user->name,
            'email'        => $user->email,
            'department'   => $subjects[0] ?? 'Unassigned',
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
            'school_level' => $schoolLevel,
        ];

        if ($subjects !== []) {
            $row['subjects'] = json_encode($subjects);
        }

        if (! Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
            unset($row['subjects']);
        }
        if (! Schema::connection($connection)->hasColumn('shared_teachers', 'department')) {
            unset($row['department']);
        }
        if (! Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
            unset($row['school_level']);
        }

        $match = ['faculty_id' => $user->id];
        if (Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
            $match['school_level'] = $schoolLevel;
        }

        $updateRow = $row;
        unset($updateRow['created_at']);

        DB::connection($connection)->table('shared_teachers')->updateOrInsert($match, $updateRow);
    }
}
