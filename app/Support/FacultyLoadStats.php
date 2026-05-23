<?php

namespace App\Support;

use App\Models\ClassSchedule;

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

        if (!empty($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        }

        $schedules = $query->get(['subject', 'start_time', 'end_time']);

        $selectedSubjects = collect(explode(',', (string) $subjectsCsv))
            ->map(fn ($s) => strtolower(trim($s)))
            ->filter()
            ->values();

        $totalMins = 0;
        foreach ($schedules as $schedule) {
            $subject = strtolower(trim((string) $schedule->subject));
            if ($selectedSubjects->isNotEmpty() && !$selectedSubjects->contains($subject)) {
                continue;
            }

            $duration = self::timeToMinutes($schedule->end_time) - self::timeToMinutes($schedule->start_time);
            if ($duration > 0) {
                $totalMins += $duration;
            }
        }

        return round($totalMins / 60, 2);
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

        $currentDay  = now()->format('l');
        $currentTime = now()->format('H:i');

        return $query->get(['day_of_week', 'start_time', 'end_time'])->filter(function ($s) use ($currentDay, $currentTime) {
            return strcasecmp($s->day_of_week ?? '', $currentDay) === 0
                && substr((string) $s->start_time, 0, 5) <= $currentTime
                && substr((string) $s->end_time, 0, 5) > $currentTime;
        })->count();
    }

    public static function resolveStatus(int $facultyId): string
    {
        if ($facultyId <= 0) {
            return 'available';
        }

        $allScheds = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved'])
            ->get(['day_of_week', 'start_time', 'end_time']);

        $dayCounts = $allScheds->groupBy('day_of_week')->map(fn ($g) => $g->count());
        if (($dayCounts->max() ?? 0) > 5) {
            return 'overloaded';
        }

        $currentTime = now()->format('H:i');
        $currentDay  = now()->format('l');

        $inClassNow = $allScheds->contains(function ($s) use ($currentDay, $currentTime) {
            return strcasecmp($s->day_of_week ?? '', $currentDay) === 0
                && substr((string) $s->start_time, 0, 5) <= $currentTime
                && substr((string) $s->end_time, 0, 5) > $currentTime;
        });

        return $inClassNow ? 'not_available' : 'available';
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
