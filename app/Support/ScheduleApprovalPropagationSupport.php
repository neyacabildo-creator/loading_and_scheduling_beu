<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\MasterWeeklySchedule;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps master weekly grid and related views aligned after an approved schedule change.
 */
class ScheduleApprovalPropagationSupport
{
    /**
     * @param  array<string, mixed>|null  $beforeAttributes  Schedule attributes before the update (day, times, grade, section).
     */
    public static function afterClassScheduleUpdate(
        ClassSchedule $record,
        ?array $beforeAttributes = null
    ): void {
        $connection = $record->getConnectionName() ?: config('database.school_connection', 'mysql_jh');

        if ($beforeAttributes !== null && $beforeAttributes !== []) {
            $ghost = new ClassSchedule;
            $ghost->setConnection($connection);
            $ghost->forceFill(array_merge(
                ['faculty_id' => $record->faculty_id],
                $beforeAttributes
            ));
            FacultyLoadSupport::removeMasterWeeklyRowsForSchedule($ghost);
        } else {
            FacultyLoadSupport::removeMasterWeeklyRowsForSchedule($record);
        }

        self::upsertMasterWeeklyFromClassSchedule($record, $connection);
    }

    public static function snapshotFromScheduleObject(object $schedule): array
    {
        return array_filter([
            'day_of_week'   => $schedule->day_of_week ?? null,
            'start_time'    => $schedule->start_time ?? null,
            'end_time'      => $schedule->end_time ?? null,
            'grade_level'   => $schedule->grade_level ?? null,
            'section_name'  => $schedule->section_name ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private static function upsertMasterWeeklyFromClassSchedule(ClassSchedule $schedule, string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('master_weekly_schedules')) {
            return;
        }

        $facultyId = (int) ($schedule->faculty_id ?? 0);
        $day = trim((string) ($schedule->day_of_week ?? ''));
        if ($facultyId <= 0 || $day === '') {
            return;
        }

        $timeStart = $schedule->start_time ? substr((string) $schedule->start_time, 0, 5) : null;
        $timeEnd = $schedule->end_time ? substr((string) $schedule->end_time, 0, 5) : null;
        $gradeLevel = $schedule->grade_level ?? null;
        $sectionName = $schedule->section_name ?? null;
        $gradeSection = trim(($gradeLevel ?? '') . ($sectionName ? ' – ' . $sectionName : ''));
        $schoolYear = $schedule->school_year ?? '2025-2026';

        $match = [
            'faculty_id'  => $facultyId,
            'school_year' => $schoolYear,
            'day_of_week' => $day,
        ];

        if ($timeStart) {
            $match['time_start'] = $timeStart;
        }

        MasterWeeklySchedule::on($connection)->updateOrCreate(
            $match,
            [
                'subject_handled' => $schedule->subject ?? null,
                'time_end'          => $timeEnd,
                'entry_type'        => 'class',
                'grade_section'     => $gradeSection !== '' ? $gradeSection : null,
                'grade_level'       => $gradeLevel,
                'section_name'      => $sectionName,
            ]
        );
    }
}
