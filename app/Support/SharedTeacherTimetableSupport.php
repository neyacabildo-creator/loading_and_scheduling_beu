<?php

namespace App\Support;

use App\Http\Controllers\MasterWeeklyScheduleController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SharedTeacherTimetableSupport
{
    /**
     * Weekly grid data for shared-teacher dashboard (print/export style).
     *
     * @param  Collection<int, object>  $schedules  Approved class_schedules rows
     * @param  Collection<int, object>  $weeklyGrid  master_weekly_schedules rows (optional)
     * @return array{
     *     school_level: string,
     *     division_label: string,
     *     time_slots: array<int, array<string, mixed>>,
     *     days: array<int, string>,
     *     cell_map: \Illuminate\Support\Collection<string, object>,
     *     subject_label: ?string,
     *     has_rows: bool
     * }
     */
    public static function buildWeeklyPresentation(
        Collection $schedules,
        Collection $weeklyGrid,
        string $schoolLevel
    ): array {
        $normalized = $schoolLevel === 'junior_high' ? 'junior_high' : 'grade_school';
        $timeSlots = MasterWeeklyScheduleController::timeSlots($normalized);
        $days = MasterWeeklyScheduleController::days();
        $cellMap = collect();

        foreach ($weeklyGrid as $row) {
            $day = self::normalizeDay((string) ($row->day_of_week ?? ''));
            $order = (int) ($row->slot_order ?? 0);
            if ($day === '' || $order <= 0) {
                continue;
            }
            $cellMap->put(self::cellKey($order, $day), (object) [
                'subject'       => trim((string) ($row->subject_handled ?? '')),
                'grade_section' => trim((string) ($row->grade_section ?? '')),
                'detail'        => trim((string) ($row->substitute_teacher ?? '')),
            ]);
        }

        if ($cellMap->isEmpty()) {
            $cellMap = self::cellMapFromClassSchedules($schedules, $timeSlots, $days);
        }

        $subjectLabel = $weeklyGrid->first()?->subject_handled
            ?? $schedules->pluck('subject')->filter()->unique()->implode(', ');

        return [
            'school_level'    => $normalized,
            'division_label'  => $normalized === 'junior_high' ? 'Junior High School' : 'Grade School',
            'time_slots'      => $timeSlots,
            'days'            => $days,
            'cell_map'        => $cellMap,
            'subject_label'   => $subjectLabel !== '' ? $subjectLabel : null,
            'has_rows'        => $cellMap->isNotEmpty() || $schedules->isNotEmpty(),
        ];
    }

    public static function fetchMasterWeeklyGrid(string $connection, int $facultyId, string $schoolYear = '2025-2026'): Collection
    {
        if ($facultyId <= 0 || ! Schema::connection($connection)->hasTable('master_weekly_schedules')) {
            return collect();
        }

        return DB::connection($connection)
            ->table('master_weekly_schedules')
            ->where('faculty_id', $facultyId)
            ->where('school_year', $schoolYear)
            ->orderBy('slot_order')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $schedules
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  array<int, string>  $days
     */
    private static function cellMapFromClassSchedules(
        Collection $schedules,
        array $timeSlots,
        array $days
    ): Collection {
        $cellMap = collect();

        foreach ($schedules as $schedule) {
            $day = self::normalizeDay((string) ($schedule->day_of_week ?? ''));
            if ($day === '' || ! in_array($day, $days, true)) {
                continue;
            }

            $start = substr((string) ($schedule->start_time ?? ''), 0, 5);
            $slotOrder = self::matchSlotOrder($timeSlots, $start);
            if ($slotOrder === null) {
                continue;
            }

            $gradeSection = trim(($schedule->grade_level ?? '') . ($schedule->section_name ? ' – ' . $schedule->section_name : ''));
            $cellMap->put(self::cellKey($slotOrder, $day), (object) [
                'subject'       => (string) ($schedule->subject ?? ''),
                'grade_section' => $gradeSection,
                'detail'        => '',
            ]);
        }

        return $cellMap;
    }

    private static function cellKey(int $slotOrder, string $day): string
    {
        return $slotOrder . '_' . $day;
    }

    private static function normalizeDay(string $day): string
    {
        $day = ucfirst(strtolower(trim($day)));

        return $day;
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     */
    private static function matchSlotOrder(array $slots, string $start): ?int
    {
        if ($start === '') {
            return null;
        }

        foreach ($slots as $slot) {
            if (($slot['start'] ?? '') === $start) {
                return (int) ($slot['order'] ?? 0);
            }
        }

        foreach ($slots as $slot) {
            $slotStart = (string) ($slot['start'] ?? '');
            $slotEnd = (string) ($slot['end'] ?? '');
            if ($slotStart !== '' && $start >= $slotStart && ($slotEnd === '' || $start < $slotEnd)) {
                return (int) ($slot['order'] ?? 0);
            }
        }

        return null;
    }

    /**
     * Distinct schedule dates for a teacher (both connections merged).
     *
     * @return array{saved: list<string>, future: list<string>, past: list<string>}
     */
    public static function scheduleDateBuckets(int $facultyId, string $schoolYear = '2025-2026'): array
    {
        $today = now()->toDateString();
        $dates = collect();

        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('class_schedules')) {
                continue;
            }
            $query = DB::connection($connection)
                ->table('class_schedules')
                ->where('faculty_id', $facultyId)
                ->where('admin_approved', true)
                ->whereNotNull('schedule_date');

            if (Schema::connection($connection)->hasColumn('class_schedules', 'school_year')) {
                $query->where('school_year', $schoolYear);
            }

            $dates = $dates->merge(
                $query->distinct()->pluck('schedule_date')->map(fn ($d) => substr((string) $d, 0, 10))
            );
        }

        $unique = $dates->filter()->unique()->sort()->values();

        return [
            'saved'  => $unique->filter(fn ($d) => $d <= $today)->values()->all(),
            'future' => $unique->filter(fn ($d) => $d > $today)->values()->all(),
            'past'   => $unique->filter(fn ($d) => $d < $today)->values()->all(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $schedules
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function filterSchedulesForView(
        Collection $schedules,
        string $view,
        ?string $selectedDate = null
    ): Collection {
        $today = now()->toDateString();

        if ($selectedDate) {
            return $schedules->filter(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10) === $selectedDate);
        }

        if ($view === 'last') {
            $lastDate = $schedules
                ->map(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10))
                ->filter(fn ($d) => $d !== '' && $d < $today)
                ->max();

            if (! $lastDate) {
                return collect();
            }

            return $schedules->filter(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10) === $lastDate);
        }

        return match ($view) {
            'future' => $schedules->filter(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10) > $today),
            'past' => $schedules->filter(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10) < $today),
            'saved' => $schedules->filter(fn ($s) => substr((string) ($s->schedule_date ?? ''), 0, 10) <= $today),
            default => $schedules,
        };
    }
}
