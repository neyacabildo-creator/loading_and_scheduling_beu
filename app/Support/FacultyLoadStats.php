<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\User;

class FacultyLoadStats
{
    public static function computeLoadHours(int $facultyId, ?string $gradeLevel = null, ?string $subjectsCsv = null): float
    {
        if ($facultyId <= 0) {
            return 0.0;
        }

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved']);

        if (! empty($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        }

        $schedules = $query->get(['subject', 'day_of_week', 'start_time', 'end_time', 'grade_level']);

        $selectedSubjects = collect(explode(',', (string) $subjectsCsv))
            ->map(fn ($s) => strtolower(trim($s)))
            ->filter()
            ->values();

        $filtered = $schedules->filter(function ($schedule) use ($selectedSubjects) {
            if ($selectedSubjects->isEmpty()) {
                return true;
            }

            return $selectedSubjects->contains(strtolower(trim((string) $schedule->subject)));
        });

        return FacultyAvailabilitySupport::weeklyLoadHours($filtered);
    }

    /**
     * Count approved schedules that are in session right now (today + current time).
     */
    public static function countOngoingClasses(int $facultyId, ?string $gradeLevel = null): int
    {
        if ($facultyId <= 0) {
            return 0;
        }

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved']);

        if (! empty($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        }

        return FacultyAvailabilitySupport::countOngoingClasses(
            $query->get(['day_of_week', 'start_time', 'end_time', 'grade_level']),
            $gradeLevel
        );
    }

    public static function resolveStatus(int $facultyId): string
    {
        if ($facultyId <= 0) {
            return 'available';
        }

        $connection = (new ClassSchedule)->getConnectionName();
        $allScheds = ClassSchedule::on($connection)
            ->where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved'])
            ->get(['day_of_week', 'start_time', 'end_time', 'grade_level']);

        $presence = TeacherPresenceSupport::activeStatusForTeacher($connection, $facultyId);
        $isShared = User::where('id', $facultyId)->whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))->exists();
        $sharedCount = $isShared ? FacultyLoadSupport::countLoadsForTeacher($facultyId) : null;

        return FacultyAvailabilitySupport::computeLiveStats(
            $connection,
            $facultyId,
            $allScheds,
            $presence,
            $isShared,
            $sharedCount
        )['status'];
    }

    private static function timeToMinutes(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $parts   = explode(':', $time);
        $hours   = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }
}
