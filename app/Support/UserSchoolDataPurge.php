<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Removes a teacher's operational data from school admin DB(s) when their user account is deleted.
 */
class UserSchoolDataPurge
{
    /**
     * Purge schedules, loads, and related rows for a user being deleted.
     */
    public static function purge(User $user): void
    {
        $facultyId = (int) $user->id;
        if ($facultyId <= 0) {
            return;
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $isShared = $roleName === 'shared_teacher' || FacultyLoadSupport::isSharedTeacher($facultyId);

        if ($isShared) {
            foreach (['mysql_jh', 'mysql_gs'] as $conn) {
                self::purgeFacultyOnConnection($conn, $facultyId);
            }

            return;
        }

        $schoolLevel = (string) ($user->school_level ?? '');
        if ($schoolLevel === '') {
            return;
        }

        self::purgeFacultyOnConnection(
            TeacherDatabaseSupport::adminConnectionForSchool($schoolLevel),
            $facultyId
        );
    }

    public static function purgeFacultyOnConnection(string $connection, int $facultyId): void
    {
        if ($facultyId <= 0 || ! in_array($connection, ['mysql_jh', 'mysql_gs'], true)) {
            return;
        }

        try {
            if (Schema::connection($connection)->hasTable('class_schedules')) {
                DB::connection($connection)->table('class_schedules')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('faculty_loads')) {
                DB::connection($connection)->table('faculty_loads')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('master_weekly_schedules')) {
                DB::connection($connection)->table('master_weekly_schedules')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('teacher_requests')) {
                DB::connection($connection)->table('teacher_requests')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('teacher_leave_requests')) {
                DB::connection($connection)->table('teacher_leave_requests')
                    ->where('teacher_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('teacher_loading_schedules')) {
                DB::connection($connection)->table('teacher_loading_schedules')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                DB::connection($connection)->table('shared_teacher_requests')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('shared_teachers')) {
                DB::connection($connection)->table('shared_teachers')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('faculty_designations')) {
                DB::connection($connection)->table('faculty_designations')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('load_conflict_log')) {
                DB::connection($connection)->table('load_conflict_log')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            $teacherConn = str_contains($connection, 'gs') ? 'mysql_gs_teacher' : 'mysql_jh_teacher';
            if (Schema::connection($teacherConn)->hasTable('schedule_adjustment_requests')) {
                DB::connection($teacherConn)->table('schedule_adjustment_requests')
                    ->where('requested_by', $facultyId)
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::warning("UserSchoolDataPurge [{$connection}] faculty {$facultyId}: " . $e->getMessage());
        }
    }
}
