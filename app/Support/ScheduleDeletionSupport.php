<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Notifications\ScheduleRemovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ScheduleDeletionSupport
{
    /**
     * Remove pending/approval rows and principal logs so deleted schedules disappear everywhere.
     */
    public static function purgeRelatedRecords(ClassSchedule $schedule): void
    {
        $scheduleId = (int) $schedule->id;
        $dbConn = $schedule->getConnectionName();
        $schoolLevel = $dbConn === 'mysql_gs' ? 'grade_school' : 'junior_high';

        try {
            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('purgeRelatedRecords pending_schedules: ' . $e->getMessage());
        }

        try {
            DB::connection($dbConn)->table('schedule_approvals')
                ->where('schedule_id', $scheduleId)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('purgeRelatedRecords schedule_approvals: ' . $e->getMessage());
        }

        try {
            if (Schema::connection('mysql_principal')->hasTable('schedule_approval_logs')) {
                DB::connection('mysql_principal')->table('schedule_approval_logs')
                    ->where('school_level', $schoolLevel)
                    ->where('schedule_id', $scheduleId)
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('purgeRelatedRecords schedule_approval_logs: ' . $e->getMessage());
        }
    }

    public static function notifyPrincipalRemoved(ClassSchedule $schedule, string $action = 'deleted', ?string $reason = null): void
    {
        try {
            $principals = User::whereHas('role', fn ($q) => $q->where('name', 'principal'))->get();
            if ($principals->isEmpty()) {
                return;
            }

            $teacherName = 'Unknown teacher';
            if ($schedule->faculty_id) {
                $teacher = User::find($schedule->faculty_id);
                if ($teacher) {
                    $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: $teacher->name;
                }
            }

            $principals->each(fn (User $principal) => $principal->notify(
                new ScheduleRemovedNotification($schedule, $action, $reason, $teacherName)
            ));
        } catch (\Throwable $e) {
            Log::warning('notifyPrincipalRemoved: ' . $e->getMessage());
        }
    }
}
