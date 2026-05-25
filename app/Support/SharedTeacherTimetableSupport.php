<?php

namespace App\Support;

use App\Http\Controllers\MasterWeeklyScheduleController;
use Illuminate\Support\Collection;

class SharedTeacherTimetableSupport
{
    /**
     * @param  Collection<int, object>|array<int, object>  $schedules
     * @return array{slots: array<int, array<string, mixed>>, cells: array<string, array<int, array<string, string>>>}
     */
    public static function buildGrid(Collection|array $schedules, string $schoolLevel): array
    {
        $slots = MasterWeeklyScheduleController::timeSlots($schoolLevel);
        $days = MasterWeeklyScheduleController::days();
        $cells = [];
        foreach ($days as $day) {
            $cells[$day] = [];
        }

        foreach ($schedules as $schedule) {
            $day = ucfirst(strtolower(trim((string) ($schedule->day_of_week ?? ''))));
            if ($day === '' || ! isset($cells[$day])) {
                continue;
            }

            $start = substr((string) ($schedule->start_time ?? ''), 0, 5);
            $end = substr((string) ($schedule->end_time ?? ''), 0, 5);
            $slotOrder = self::matchSlotOrder($slots, $start);
            if ($slotOrder === null) {
                continue;
            }

            $gradeSection = trim(($schedule->grade_level ?? '') . ($schedule->section_name ? ' – ' . $schedule->section_name : ''));
            $cells[$day][$slotOrder] = [
                'subject'       => (string) ($schedule->subject ?? ''),
                'grade_section' => $gradeSection,
                'time'          => $start && $end ? "{$start} – {$end}" : $start,
                'level'         => $schoolLevel === 'junior_high' ? 'JH' : 'GS',
            ];
        }

        return ['slots' => $slots, 'cells' => $cells];
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
}
