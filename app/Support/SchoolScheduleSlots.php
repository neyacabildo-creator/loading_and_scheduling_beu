<?php

namespace App\Support;

/**
 * Official class period times per school level (GS vs JH).
 * Used for request adjustments, print/export grids, and validation.
 */
class SchoolScheduleSlots
{
    /** @var list<string> */
    public const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    /**
     * Grade School class periods (excludes snack/lunch breaks).
     *
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function gradeSchoolClassSlots(): array
    {
        return [
            ['start' => '07:45', 'end' => '08:35', 'label' => '7:45 – 8:35'],
            ['start' => '08:35', 'end' => '09:25', 'label' => '8:35 – 9:25'],
            ['start' => '09:55', 'end' => '10:45', 'label' => '9:55 – 10:45'],
            ['start' => '10:45', 'end' => '11:35', 'label' => '10:45 – 11:35'],
            ['start' => '13:15', 'end' => '14:05', 'label' => '1:15 – 2:05'],
            ['start' => '14:05', 'end' => '14:55', 'label' => '2:05 – 2:55'],
            ['start' => '14:55', 'end' => '15:45', 'label' => '2:55 – 3:45'],
            ['start' => '15:45', 'end' => '16:35', 'label' => '3:45 – 4:35'],
        ];
    }

    /**
     * @return list<array{start: string, end: string, label: string, type?: string, name?: string}>
     */
    public static function gradeSchoolGridSlots(): array
    {
        return [
            ['start' => '07:45', 'end' => '08:35', 'label' => '7:45 – 8:35', 'type' => 'class'],
            ['start' => '08:35', 'end' => '09:25', 'label' => '8:35 – 9:25', 'type' => 'class'],
            ['start' => '09:25', 'end' => '09:55', 'label' => '9:25 – 10:45', 'type' => 'break', 'name' => 'SNACK BREAK'],
            ['start' => '09:55', 'end' => '10:45', 'label' => '9:55 – 10:45', 'type' => 'class'],
            ['start' => '10:45', 'end' => '11:35', 'label' => '10:45 – 11:35', 'type' => 'class'],
            ['start' => '11:35', 'end' => '13:15', 'label' => '11:35 – 1:15', 'type' => 'break', 'name' => 'LUNCH'],
            ['start' => '13:15', 'end' => '14:05', 'label' => '1:15 – 2:05', 'type' => 'class'],
            ['start' => '14:05', 'end' => '14:55', 'label' => '2:05 – 2:55', 'type' => 'class'],
            ['start' => '14:55', 'end' => '15:45', 'label' => '2:55 – 3:45', 'type' => 'class'],
            ['start' => '15:45', 'end' => '16:35', 'label' => '3:45 – 4:35', 'type' => 'class'],
        ];
    }

    /**
     * Junior High shared morning / lunch rows (all weekdays).
     *
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string}>
     */
    public static function juniorHighSharedGridSlots(): array
    {
        return [
            ['start' => '07:45', 'end' => '08:45', 'label' => '7:45 – 8:45', 'type' => 'class'],
            ['start' => '08:45', 'end' => '09:45', 'label' => '8:45 – 9:45', 'type' => 'class'],
            ['start' => '09:45', 'end' => '10:15', 'label' => '9:45 – 10:15', 'type' => 'homeroom', 'special' => 'RECESS'],
            ['start' => '10:15', 'end' => '11:15', 'label' => '10:15 – 11:15', 'type' => 'class'],
            ['start' => '11:15', 'end' => '12:15', 'label' => '11:15 – 12:15', 'type' => 'class'],
            ['start' => '12:15', 'end' => '13:15', 'label' => '12:15 – 1:15', 'type' => 'lunch', 'special' => 'LUNCH'],
            ['start' => '13:15', 'end' => '14:15', 'label' => '1:15 – 2:15', 'type' => 'class'],
            ['start' => '14:15', 'end' => '15:15', 'label' => '2:15 – 3:15', 'type' => 'class'],
        ];
    }

    /**
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string}>
     */
    public static function juniorHighWeekdayAfternoonGridSlots(): array
    {
        return [
            ['start' => '15:15', 'end' => '16:15', 'label' => '3:15 – 4:15', 'type' => 'class'],
            ['start' => '16:15', 'end' => '17:15', 'label' => '4:15 – 5:15', 'type' => 'class'],
        ];
    }

    /**
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string}>
     */
    public static function juniorHighTuesdayAfternoonGridSlots(): array
    {
        return [
            [
                'start'   => '15:15',
                'end'     => '16:45',
                'label'   => '3:15 – 4:45',
                'type'    => 'homeroom',
                'special' => 'HOMEROOM/CLUB ACTIVITIES',
            ],
        ];
    }

    /**
     * Junior High grid for a single day (create schedule, timetable, class schedule).
     *
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string, name?: string}>
     */
    public static function juniorHighGridSlotsForDay(?string $dayOfWeek): array
    {
        $slots = self::juniorHighSharedGridSlots();

        if (self::isTuesday($dayOfWeek)) {
            return array_merge($slots, self::juniorHighTuesdayAfternoonGridSlots());
        }

        return array_merge($slots, self::juniorHighWeekdayAfternoonGridSlots());
    }

    /**
     * Union of all JH rows for master loading grid (rows may apply to specific days only).
     *
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string, name?: string, days?: list<string>}>
     */
    public static function juniorHighGridSlotsUnion(): array
    {
        $weekdays = ['Monday', 'Wednesday', 'Thursday', 'Friday'];
        $shared = array_map(function (array $slot) {
            return $slot + ['days' => self::WEEKDAYS];
        }, self::juniorHighSharedGridSlots());

        $weekdayAfternoon = array_map(function (array $slot) use ($weekdays) {
            return $slot + ['days' => $weekdays];
        }, self::juniorHighWeekdayAfternoonGridSlots());

        $tuesdayAfternoon = array_map(function (array $slot) {
            return $slot + ['days' => ['Tuesday']];
        }, self::juniorHighTuesdayAfternoonGridSlots());

        return array_merge($shared, $weekdayAfternoon, $tuesdayAfternoon);
    }

    /**
     * Junior High class periods for teacher request adjustments (excludes recess/lunch).
     *
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function juniorHighClassSlots(?string $dayOfWeek = null): array
    {
        $slots = [];
        foreach (self::juniorHighGridSlotsForDay($dayOfWeek) as $slot) {
            if (in_array($slot['type'] ?? 'class', ['lunch', 'homeroom', 'break'], true)) {
                continue;
            }
            $slots[] = [
                'start' => $slot['start'],
                'end'   => $slot['end'],
                'label' => self::normalizeSlotLabel($slot['label']),
            ];
        }

        if (self::isTuesday($dayOfWeek)) {
            $slots[] = [
                'start' => '15:15',
                'end'   => '16:45',
                'label' => '3:15 – 4:45 (Homeroom/Club)',
            ];
        }

        return $slots;
    }

    /**
     * Master weekly loading grid rows for admin manage form (includes breaks).
     *
     * @return list<array{start: string, end: string, label: string, type?: string, special?: string, name?: string, days?: list<string>}>
     */
    public static function gridSlotsForSchoolLevel(?string $schoolLevel, ?string $dayOfWeek = null, bool $unionAllDays = false): array
    {
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            return self::gradeSchoolGridSlots();
        }

        if ($unionAllDays) {
            return self::juniorHighGridSlotsUnion();
        }

        return self::juniorHighGridSlotsForDay($dayOfWeek);
    }

    /**
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function classSlotsForSchoolLevel(?string $schoolLevel, ?string $dayOfWeek = null): array
    {
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            return self::gradeSchoolClassSlots();
        }

        return self::juniorHighClassSlots($dayOfWeek);
    }

    /**
     * All official class periods for validation when day is unknown (union across JH days).
     *
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function allClassSlotsForSchoolLevel(?string $schoolLevel): array
    {
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            return self::gradeSchoolClassSlots();
        }

        $seen = [];
        $slots = [];
        foreach (self::WEEKDAYS as $day) {
            foreach (self::juniorHighClassSlots($day) as $slot) {
                $key = $slot['start'] . '|' . $slot['end'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    /**
     * @return array<string, list<array{start: string, end: string, label: string}>>
     */
    public static function classSlotsByDayForSchoolLevel(?string $schoolLevel): array
    {
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            return ['default' => self::gradeSchoolClassSlots()];
        }

        $map = [];
        foreach (self::WEEKDAYS as $day) {
            $map[$day] = self::juniorHighClassSlots($day);
        }

        return $map;
    }

    /**
     * @return array<string, list<array{label: string, start: string, end: string, isBreak: bool}>>
     */
    public static function scheduleDashboardSlotsByDay(?string $schoolLevel): array
    {
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            return ['default' => self::scheduleDashboardSlots($schoolLevel)];
        }

        $map = [];
        foreach (self::WEEKDAYS as $day) {
            $map[$day] = self::scheduleDashboardSlots($schoolLevel, $day);
        }

        return $map;
    }

    public static function schoolLevelFromConnection(?string $connection): string
    {
        return $connection === 'mysql_gs' ? 'grade_school' : 'junior_high';
    }

    /**
     * Time column text for grids (two-line class times, single-line breaks).
     */
    public static function formatTimeCellLabel(array $slot): string
    {
        if (in_array($slot['type'] ?? 'class', ['lunch', 'homeroom', 'break'], true)) {
            return (string) ($slot['special'] ?? $slot['name'] ?? strtoupper($slot['type'] ?? 'BREAK'));
        }

        [$top, $bottom] = self::splitLabelToDisplayPair($slot['label']);

        return $bottom !== '' ? $top . "\n" . $bottom : $top;
    }

    /**
     * Print/export rows for one day (JH uses day-specific rows; GS ignores day).
     *
     * @return list<array<string, mixed>>
     */
    public static function printExportSlotsForDay(?string $schoolLevel, string $dayOfWeek): array
    {
        return self::printExportSlots($schoolLevel, $dayOfWeek);
    }

    /**
     * Weekly timetable / print-export rows (includes breaks).
     *
     * @return list<array<string, mixed>>
     */
    public static function printExportSlots(?string $schoolLevel, ?string $dayOfWeek = null): array
    {
        return array_map(function (array $slot) {
            $isBreak = in_array($slot['type'] ?? 'class', ['lunch', 'homeroom', 'break'], true);

            return [
                'start' => $slot['start'],
                'end'   => $slot['end'],
                'label' => $slot['label'],
                'type'  => $isBreak ? 'break' : 'class',
                'name'  => $slot['special'] ?? $slot['name'] ?? null,
                'days'  => $slot['days'] ?? null,
            ];
        }, self::gridSlotsForSchoolLevel(
            $schoolLevel,
            $dayOfWeek,
            self::isJuniorHighLevel($schoolLevel) && ($dayOfWeek === null || $dayOfWeek === '')
        ));
    }

    private static function isJuniorHighLevel(?string $schoolLevel): bool
    {
        $level = strtolower(trim((string) $schoolLevel));

        return ! in_array($level, ['grade_school', 'gs', 'grade school'], true);
    }

    /**
     * Dashboard weekly timetable slots (admin home).
     *
     * @return list<array{label: string, start: string, end: string, isBreak: bool}>
     */
    public static function scheduleDashboardSlots(?string $schoolLevel, ?string $dayOfWeek = null): array
    {
        return array_map(function (array $slot) {
            $isBreak = in_array($slot['type'] ?? 'class', ['lunch', 'homeroom', 'break'], true);
            if ($isBreak) {
                $label = $slot['special'] ?? $slot['name'] ?? strtoupper($slot['type'] ?? 'BREAK');
            } else {
                [$startLabel, $endLabel] = self::splitLabelToDisplayPair($slot['label']);
                $label = $startLabel . "\n" . $endLabel;
            }

            return [
                'label'   => $label,
                'start'   => $slot['start'],
                'end'     => $slot['end'],
                'isBreak' => $isBreak,
            ];
        }, self::gridSlotsForSchoolLevel($schoolLevel, $dayOfWeek, false));
    }

    /**
     * Rows for create-schedule grid forms.
     *
     * @return list<array{key: ?string, start: string, end: string, time_top: string, time_bottom: string, is_break: bool, break_title: string}>
     */
    public static function scheduleFormRows(?string $schoolLevel, ?string $dayOfWeek = null): array
    {
        $rows = [];
        foreach (self::gridSlotsForSchoolLevel($schoolLevel, $dayOfWeek, false) as $slot) {
            $isBreak = in_array($slot['type'] ?? 'class', ['lunch', 'homeroom', 'break'], true);
            [$timeTop, $timeBottom] = self::splitLabelToDisplayPair($slot['label']);

            $rows[] = [
                'key'         => $isBreak ? null : self::slotKey($slot['start'], $slot['end']),
                'start'       => $slot['start'],
                'end'         => $slot['end'],
                'time_top'    => $timeTop,
                'time_bottom' => $timeBottom,
                'is_break'    => $isBreak,
                'break_title' => $slot['special'] ?? $slot['name'] ?? strtoupper($slot['type'] ?? 'BREAK'),
            ];
        }

        return $rows;
    }

    /**
     * Form POST keys for class periods only: "0745_0845" => ['start' => '07:45', 'end' => '08:45'].
     *
     * @return array<string, array{start: string, end: string}>
     */
    public static function scheduleSlotKeyMap(?string $schoolLevel): array
    {
        $map = [];
        $level = strtolower(trim((string) $schoolLevel));

        if (in_array($level, ['grade_school', 'gs', 'grade school'], true)) {
            $daySources = [null];
        } else {
            $daySources = self::WEEKDAYS;
        }

        foreach ($daySources as $day) {
            foreach (self::scheduleFormRows($schoolLevel, $day) as $row) {
                if ($row['is_break'] || empty($row['key'])) {
                    continue;
                }
                $map[$row['key']] = ['start' => $row['start'], 'end' => $row['end']];
            }
        }

        return $map;
    }

    public static function slotAppliesToDay(array $slot, string $day): bool
    {
        if (empty($slot['days']) || ! is_array($slot['days'])) {
            return true;
        }

        return in_array($day, $slot['days'], true);
    }

    /**
     * Master loading grid rows that span all day columns (recess, lunch, snack).
     */
    public static function isMasterGridFixedRow(array $slot, int $dayColumnCount = 5): bool
    {
        if (in_array($slot['type'] ?? 'class', ['lunch', 'break'], true)) {
            return true;
        }

        if (($slot['type'] ?? '') !== 'homeroom') {
            return false;
        }

        $days = $slot['days'] ?? null;

        return $days === null || count($days) >= $dayColumnCount;
    }

    public static function slotKey(string $start, string $end): string
    {
        return str_replace(':', '', self::normalizeHi($start)) . '_' . str_replace(':', '', self::normalizeHi($end));
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitLabelToDisplayPair(string $label): array
    {
        if (preg_match('/^(.+?)\s*[–\-]\s*(.+)$/u', trim($label), $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [trim($label), ''];
    }

    public static function normalizeHi(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $time = trim((string) $time);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return $time;
    }

    public static function isValidClassSlot(?string $schoolLevel, ?string $start, ?string $end, ?string $dayOfWeek = null): bool
    {
        $start = self::normalizeHi($start);
        $end = self::normalizeHi($end);

        if ($start === '' || $end === '') {
            return false;
        }

        $candidates = $dayOfWeek !== null && $dayOfWeek !== ''
            ? self::classSlotsForSchoolLevel($schoolLevel, $dayOfWeek)
            : self::allClassSlotsForSchoolLevel($schoolLevel);

        foreach ($candidates as $slot) {
            if ($slot['start'] === $start && $slot['end'] === $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{start: string, end: string, label: string}|null
     */
    public static function matchClassSlot(?string $schoolLevel, ?string $start, ?string $end, ?string $dayOfWeek = null): ?array
    {
        $start = self::normalizeHi($start);
        $end = self::normalizeHi($end);

        if ($start === '' || $end === '') {
            return null;
        }

        $candidates = $dayOfWeek !== null && $dayOfWeek !== ''
            ? self::classSlotsForSchoolLevel($schoolLevel, $dayOfWeek)
            : self::allClassSlotsForSchoolLevel($schoolLevel);

        foreach ($candidates as $slot) {
            if ($slot['start'] === $start && $slot['end'] === $end) {
                return $slot;
            }
        }

        return null;
    }

    private static function isTuesday(?string $dayOfWeek): bool
    {
        return strcasecmp(trim((string) $dayOfWeek), 'Tuesday') === 0;
    }

    private static function normalizeSlotLabel(string $label): string
    {
        return preg_replace('/\s*-\s*/u', ' – ', trim($label)) ?? trim($label);
    }
}
