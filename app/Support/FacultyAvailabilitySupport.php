<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Live faculty availability: class sessions, breaks, leave, and overload.
 */
class FacultyAvailabilitySupport
{
    /** @var list<string> */
    private const BREAK_TYPES = ['break', 'lunch', 'homeroom'];

    public static function schoolLevelForConnection(string $connection): string
    {
        return $connection === 'mysql_gs' ? 'grade_school' : 'junior_high';
    }

    public static function isDuringSchoolBreak(string $schoolLevel, ?Carbon $now = null): bool
    {
        $now = $now ?? now();
        $nowMins = $now->hour * 60 + $now->minute;

        foreach (self::breakSlotsForLevel($schoolLevel) as $slot) {
            $start = self::timeToMinutes($slot['start'] ?? null);
            $end = self::timeToMinutes($slot['end'] ?? null);
            if ($start <= $nowMins && $end > $nowMins) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{start: string, end: string, type?: string}>
     */
    public static function breakSlotsForLevel(string $schoolLevel): array
    {
        if ($schoolLevel === 'junior_high') {
            return array_values(array_filter(
                SchoolScheduleSlots::juniorHighSharedGridSlots(),
                fn (array $slot) => in_array($slot['type'] ?? 'class', self::BREAK_TYPES, true)
            ));
        }

        return array_values(array_filter(
            SchoolScheduleSlots::gradeSchoolGridSlots(),
            fn (array $slot) => in_array($slot['type'] ?? 'class', self::BREAK_TYPES, true)
        ));
    }

    /**
     * @param  iterable<object|array<string, mixed>>  $schedules
     */
    public static function countOngoingClasses(iterable $schedules, ?string $gradeLevel = null, ?Carbon $now = null): int
    {
        $now = $now ?? now();
        $today = $now->format('l');
        $nowMins = $now->hour * 60 + $now->minute;
        $normalizedGrade = strtolower(trim((string) $gradeLevel));

        $count = 0;
        foreach ($schedules as $schedule) {
            $s = self::normalizeSchedule($schedule);
            if (strcasecmp($s['day_of_week'], $today) !== 0) {
                continue;
            }
            $start = self::timeToMinutes($s['start_time']);
            $end = self::timeToMinutes($s['end_time']);
            if (! ($start <= $nowMins && $end > $nowMins)) {
                continue;
            }
            if ($normalizedGrade !== '') {
                $sg = strtolower(trim($s['grade_level']));
                if ($sg !== '' && $sg !== $normalizedGrade) {
                    continue;
                }
            }
            $count++;
        }

        return $count;
    }

    /**
     * Minutes from today's classes that have started (ongoing or finished).
     *
     * @param  iterable<object|array<string, mixed>>  $schedules
     */
    public static function todayStartedMinutes(iterable $schedules, ?Carbon $now = null): int
    {
        $now = $now ?? now();
        $today = $now->format('l');
        $nowMins = $now->hour * 60 + $now->minute;
        $total = 0;

        foreach ($schedules as $schedule) {
            $s = self::normalizeSchedule($schedule);
            if (strcasecmp($s['day_of_week'], $today) !== 0) {
                continue;
            }
            $start = self::timeToMinutes($s['start_time']);
            $end = self::timeToMinutes($s['end_time']);
            if ($start > $nowMins) {
                continue;
            }
            $effectiveEnd = min($end, $nowMins);
            if ($effectiveEnd > $start) {
                $total += $effectiveEnd - $start;
            }
        }

        return $total;
    }

    /**
     * @param  iterable<object|array<string, mixed>>  $schedules
     */
    public static function weeklyLoadHours(iterable $schedules): float
    {
        $totalMins = 0;
        foreach ($schedules as $schedule) {
            $s = self::normalizeSchedule($schedule);
            $duration = self::timeToMinutes($s['end_time']) - self::timeToMinutes($s['start_time']);
            if ($duration > 0) {
                $totalMins += $duration;
            }
        }

        return round($totalMins / 60, 2);
    }

    /**
     * @param  Collection<int, object>|iterable<object|array<string, mixed>>  $allScheds
     * @return array{
     *     classes_assigned: int,
     *     load_hours: float,
     *     status: string,
     *     max_day_count: int,
     *     overloaded_day: ?string,
     *     availability_note: ?string,
     *     in_break: bool
     * }
     */
    public static function computeLiveStats(
        string $connection,
        int $facultyId,
        iterable $allScheds,
        ?array $presence = null,
        bool $isSharedTeacher = false,
        ?int $sharedLoadCount = null
    ): array {
        $schoolLevel = self::schoolLevelForConnection($connection);
        $now = now();
        $todayScheds = collect($allScheds)->filter(function ($s) use ($now) {
            $row = self::normalizeSchedule($s);

            return strcasecmp($row['day_of_week'], $now->format('l')) === 0;
        });

        $ongoing = self::countOngoingClasses($todayScheds, null, $now);
        $todayMins = self::todayStartedMinutes($todayScheds, $now);
        $inBreak = self::isDuringSchoolBreak($schoolLevel, $now);

        $dayCounts = collect($allScheds)->groupBy(function ($s) {
            return self::normalizeSchedule($s)['day_of_week'];
        })->map(fn ($g) => $g->count());
        $maxDayCount = (int) ($dayCounts->max() ?? 0);
        $overloadedDay = $dayCounts->filter(fn ($c) => $c > 5)->keys()->first();

        $sharedConflict = $isSharedTeacher
            && $sharedLoadCount !== null
            && $sharedLoadCount > FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS;

        $stats = [
            'classes_assigned' => 0,
            'load_hours'       => 0.0,
            'status'           => 'available',
            'max_day_count'    => $maxDayCount,
            'overloaded_day'   => $overloadedDay ? (string) $overloadedDay : null,
            'availability_note' => null,
            'in_break'         => $inBreak,
        ];

        if ($maxDayCount > 5 || $sharedConflict) {
            $stats['status'] = 'overloaded';
        }

        if ($presence) {
            $stats['classes_assigned'] = 0;
            $stats['load_hours'] = 0.0;
            $stats['status'] = 'not_available';

            return $stats;
        }

        if ($inBreak) {
            $stats['status'] = 'not_available';
            $stats['availability_note'] = 'Not Available — Break period';

            return $stats;
        }

        if ($ongoing > 0) {
            $stats['classes_assigned'] = $ongoing;
            $stats['load_hours'] = round($todayMins / 60, 1);
            if ($stats['status'] !== 'overloaded') {
                $stats['status'] = 'not_available';
            }

            return $stats;
        }

        if ($todayMins > 0) {
            $stats['load_hours'] = round($todayMins / 60, 1);
        }

        if ($stats['status'] !== 'overloaded') {
            $stats['status'] = 'available';
            $stats['classes_assigned'] = 0;
            $stats['load_hours'] = 0.0;
        }

        return $stats;
    }

    public static function canAssignFaculty(string $connection, int $facultyId, ?Carbon $at = null): bool
    {
        if ($facultyId <= 0) {
            return false;
        }

        if (TeacherPresenceSupport::activeStatusForTeacher($connection, $facultyId, $at)) {
            return false;
        }

        $schoolLevel = self::schoolLevelForConnection($connection);
        if (self::isDuringSchoolBreak($schoolLevel, $at)) {
            return false;
        }

        $schedules = \App\Models\ClassSchedule::on($connection)
            ->where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved'])
            ->get(['day_of_week', 'start_time', 'end_time', 'grade_level']);

        if (self::countOngoingClasses($schedules, null, $at) > 0) {
            return false;
        }

        $dayCounts = $schedules->groupBy('day_of_week')->map(fn ($g) => $g->count());
        if (($dayCounts->max() ?? 0) > 5) {
            return false;
        }

        $isShared = User::query()->where('id', $facultyId)->whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))->exists();
        if ($isShared && FacultyLoadSupport::countLoadsForTeacher($facultyId) >= FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, string> faculty_id => reason
     */
    public static function unavailableFacultyMap(string $connection, array $facultyIds): array
    {
        $map = [];
        foreach (array_unique(array_filter(array_map('intval', $facultyIds))) as $id) {
            if (! self::canAssignFaculty($connection, $id)) {
                $presence = TeacherPresenceSupport::activeStatusForTeacher($connection, $id);
                if ($presence) {
                    $map[$id] = $presence['label'] ?? 'On Leave';
                } elseif (self::isDuringSchoolBreak(self::schoolLevelForConnection($connection))) {
                    $map[$id] = 'Break period';
                } else {
                    $map[$id] = 'Not Available';
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichFacultyLoadRow(
        array $data,
        string $connection,
        int $facultyId,
        iterable $allScheds,
        ?array $presence,
        bool $isSharedTeacher,
        ?int $sharedLoadCount = null
    ): array {
        $live = self::computeLiveStats(
            $connection,
            $facultyId,
            $allScheds,
            $presence,
            $isSharedTeacher,
            $sharedLoadCount
        );

        $data['classes_assigned'] = $live['classes_assigned'];
        $data['load_hours'] = $live['load_hours'];
        $data['status'] = $live['status'];
        $data['max_day_count'] = $live['max_day_count'];
        $data['overloaded_day'] = $live['overloaded_day'];
        $data['in_break'] = $live['in_break'];

        if (! empty($live['availability_note']) && empty($data['availability_note'])) {
            $data['availability_note'] = $live['availability_note'];
        }

        if ($presence) {
            $data = TeacherPresenceSupport::applyPresenceToFacultyLoadRow($data, $presence);
            $data['status'] = 'not_available';
        }

        return $data;
    }

    /**
     * @param  object|array<string, mixed>  $schedule
     * @return array{day_of_week: string, start_time: string, end_time: string, grade_level: string}
     */
    private static function normalizeSchedule(object|array $schedule): array
    {
        if (is_array($schedule)) {
            return [
                'day_of_week' => (string) ($schedule['day_of_week'] ?? ''),
                'start_time'  => substr((string) ($schedule['start_time'] ?? ''), 0, 5),
                'end_time'    => substr((string) ($schedule['end_time'] ?? ''), 0, 5),
                'grade_level' => (string) ($schedule['grade_level'] ?? ''),
            ];
        }

        return [
            'day_of_week' => (string) ($schedule->day_of_week ?? ''),
            'start_time'  => substr((string) ($schedule->start_time ?? ''), 0, 5),
            'end_time'    => substr((string) ($schedule->end_time ?? ''), 0, 5),
            'grade_level' => (string) ($schedule->grade_level ?? ''),
        ];
    }

    private static function timeToMinutes(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $parts = explode(':', (string) $time);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }
}
