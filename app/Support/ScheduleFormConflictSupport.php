<?php

namespace App\Support;

/**
 * Validates schedule grid cells for duplicate subject + teacher in one section.
 */
class ScheduleFormConflictSupport
{
    /**
     * @param  array<int, array{subject: string, faculty_id: string|null}>  $cellRows
     * @return string|null Error message when duplicate found
     */
    public static function duplicateSubjectTeacherInCell(array $cellRows): ?string
    {
        $seen = [];

        foreach ($cellRows as $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $facultyId = trim((string) ($row['faculty_id'] ?? ''));

            if ($subject === '' || $facultyId === '') {
                continue;
            }

            $key = strtolower($subject) . '|' . $facultyId;
            if (isset($seen[$key])) {
                return "Duplicate assignment: \"{$subject}\" is already assigned to the same teacher in this section slot.";
            }

            $seen[$key] = true;
        }

        return null;
    }

    /**
     * @param  array<int, array{subject: string, faculty_id: string|null}>  $cellRows
     */
    public static function collectCellRowsFromSlotData(array $data): array
    {
        $rows = [];
        $primarySubject = trim((string) ($data['subject'] ?? ''));
        $primaryFaculty = ! empty($data['faculty_id']) ? (string) $data['faculty_id'] : null;

        if ($primarySubject !== '') {
            $rows[] = ['subject' => $primarySubject, 'faculty_id' => $primaryFaculty];
        }

        foreach ($data['extra'] ?? [] as $extra) {
            $subject = trim((string) ($extra['subject'] ?? ''));
            $facultyId = ! empty($extra['faculty_id']) ? (string) $extra['faculty_id'] : null;
            if ($subject !== '') {
                $rows[] = ['subject' => $subject, 'faculty_id' => $facultyId];
            }
        }

        return $rows;
    }

    public static function normalizeTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return substr((string) $time, 0, 5);
    }

  /**
     * Existing schedule occupying the same grade/section slot (day + time + date).
     */
    public static function findSectionSlotConflict(
        string $gradeLevel,
        string $sectionName,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?int $excludeScheduleId = null,
    ): ?\App\Models\ClassSchedule {
        $start = self::normalizeTime($startTime);
        if ($start === '') {
            return null;
        }

        $query = \App\Models\ClassSchedule::query()
            ->where('grade_level', $gradeLevel)
            ->where('section_name', $sectionName)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', 'like', $start . '%')
            ->whereNotIn('status', ['cancelled', 'deleted', 'rejected']);

        self::applyScheduleDateScope($query, $scheduleDate);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->first();
    }

    /**
     * Existing schedule occupying the same room slot (day + time + date).
     */
    public static function findRoomSlotConflict(
        int $roomId,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?int $excludeScheduleId = null,
    ): ?\App\Models\ClassSchedule {
        if ($roomId <= 0) {
            return null;
        }

        $start = self::normalizeTime($startTime);
        if ($start === '') {
            return null;
        }

        $query = \App\Models\ClassSchedule::query()
            ->where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', 'like', $start . '%')
            ->whereNotIn('status', ['cancelled', 'deleted', 'rejected']);

        self::applyScheduleDateScope($query, $scheduleDate);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->first();
    }

    /**
     * Teacher already booked at this day + time + date (any section).
     */
    public static function findTeacherSlotConflict(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?int $excludeScheduleId = null,
    ): ?\App\Models\ClassSchedule {
        if ($facultyId <= 0) {
            return null;
        }

        $start = self::normalizeTime($startTime);
        if ($start === '') {
            return null;
        }

        $query = \App\Models\ClassSchedule::query()
            ->where('faculty_id', $facultyId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', 'like', $start . '%')
            ->whereNotIn('status', ['cancelled', 'deleted', 'rejected']);

        self::applyScheduleDateScope($query, $scheduleDate);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->first();
    }

    public static function sectionRoomLabel(string $gradeLevel, string $sectionName): string
    {
        $grade = trim($gradeLevel);
        $section = trim($sectionName);

        if ($grade !== '' && $section !== '') {
            return $grade . ' – ' . $section;
        }

        return $grade !== '' ? $grade : ($section !== '' ? $section : '—');
    }

    /**
     * Toast-friendly duplicate message (grade/section room, day, date, time).
     */
    public static function duplicateScheduleForSlotMessage(
        string $gradeLevel,
        string $sectionName,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?string $endTime = null,
        ?string $roomDisplay = null,
        ?object $existing = null,
    ): string {
        $room = $roomDisplay ?? self::sectionRoomLabel($gradeLevel, $sectionName);
        $dateStr = ($scheduleDate !== null && trim($scheduleDate) !== '')
            ? \Carbon\Carbon::parse(substr(trim($scheduleDate), 0, 10))->format('m/d/Y')
            : 'this date';
        $start = self::normalizeTime($startTime);
        $end = self::normalizeTime($endTime ?? ($existing->end_time ?? null));
        $timeStr = ($start && $end) ? "{$start} – {$end}" : ($start ?: $startTime);
        $detail = '';
        if ($existing && ! empty($existing->subject)) {
            $detail = ' (' . $existing->subject . ')';
        }

        return "Has already a schedule for {$room} on {$dayOfWeek}, {$dateStr} at {$timeStr}{$detail}.";
    }

    public static function sectionSlotConflictMessage(
        string $gradeLevel,
        string $sectionName,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?string $endTime = null,
    ): ?string {
        $existing = self::findSectionSlotConflict($gradeLevel, $sectionName, $dayOfWeek, $startTime, $scheduleDate);
        if (! $existing) {
            return null;
        }

        return self::duplicateScheduleForSlotMessage(
            $gradeLevel,
            $sectionName,
            $dayOfWeek,
            $startTime,
            $scheduleDate,
            $endTime,
            null,
            $existing
        );
    }

    public static function roomSlotConflictMessage(
        int $roomId,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?string $endTime = null,
    ): ?string {
        $existing = self::findRoomSlotConflict($roomId, $dayOfWeek, $startTime, $scheduleDate);
        if (! $existing) {
            return null;
        }

        $roomDisplay = 'Room #' . $roomId;

        return self::duplicateScheduleForSlotMessage(
            (string) ($existing->grade_level ?? ''),
            (string) ($existing->section_name ?? ''),
            $dayOfWeek,
            $startTime,
            $scheduleDate,
            $endTime,
            $roomDisplay,
            $existing
        );
    }

    public static function teacherSlotConflictMessage(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate = null,
        ?string $endTime = null,
    ): ?string {
        $existing = self::findTeacherSlotConflict($facultyId, $dayOfWeek, $startTime, $scheduleDate);
        if (! $existing) {
            return null;
        }

        return self::duplicateScheduleForSlotMessage(
            (string) ($existing->grade_level ?? ''),
            (string) ($existing->section_name ?? ''),
            $dayOfWeek,
            $startTime,
            $scheduleDate,
            $endTime,
            null,
            $existing
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\ClassSchedule>  $query
     */
    private static function applyScheduleDateScope($query, ?string $scheduleDate): void
    {
        if ($scheduleDate !== null && trim($scheduleDate) !== '') {
            $query->whereDate('schedule_date', substr(trim($scheduleDate), 0, 10));
        } else {
            $query->where(function ($q) {
                $q->whereNull('schedule_date')->orWhere('schedule_date', '');
            });
        }
    }
}
