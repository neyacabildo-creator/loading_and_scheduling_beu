<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\User;

/**
 * Validates schedule grid cells for duplicate subject + teacher in one section.
 */
class ScheduleFormConflictSupport
{
    public static function facultyAvailabilityConflictMessage(int $facultyId, string $connection): ?string
    {
        if ($facultyId <= 0) {
            return null;
        }

        if (FacultyAvailabilitySupport::canAssignFaculty($connection, $facultyId)) {
            return null;
        }

        $teacher = User::find($facultyId);
        $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : "Teacher #{$facultyId}";
        $presence = TeacherPresenceSupport::activeStatusForTeacher($connection, $facultyId);
        if ($presence) {
            return "{$name} is {$presence['label']} and cannot be scheduled.";
        }

        if (FacultyAvailabilitySupport::isDuringSchoolBreak(FacultyAvailabilitySupport::schoolLevelForConnection($connection))) {
            return "{$name} is not available during break periods.";
        }

        return "{$name} is not available (in class or overloaded).";
    }

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

        $query = self::scheduleQuery()
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

        $query = self::scheduleQuery()
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

        $query = self::scheduleQuery()
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
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ClassSchedule>
     */
    private static function scheduleQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $conn = config('database.school_connection');
        if ($conn && array_key_exists($conn, config('database.connections', []))) {
            return ClassSchedule::on($conn)->newQuery();
        }

        return ClassSchedule::query();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\ClassSchedule>  $query
     */
    private static function applyScheduleDateScope($query, ?string $scheduleDate): void
    {
        if ($scheduleDate === null || trim($scheduleDate) === '') {
            return;
        }

        $date = substr(trim($scheduleDate), 0, 10);
        $query->where(function ($q) use ($date) {
            $q->whereDate('schedule_date', $date)
                ->orWhereNull('schedule_date');
        });
    }

    /**
     * Same grade + section + day + time already present in this submission grid.
     */
    public static function inFormSectionSlotDuplicateMessage(
        string $gradeLevel,
        string $sectionName,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate,
        ?string $endTime = null,
    ): string {
        return 'This form already assigns '
            . self::sectionRoomLabel($gradeLevel, $sectionName)
            . " on {$dayOfWeek}"
            . ($scheduleDate ? ', ' . \Carbon\Carbon::parse(substr($scheduleDate, 0, 10))->format('m/d/Y') : '')
            . ' at ' . self::normalizeTime($startTime)
            . ($endTime ? ' – ' . self::normalizeTime($endTime) : '')
            . '. Remove the duplicate row before saving.';
    }

    /**
     * Teacher assigned to more than one section at the same day/time in this form.
     */
    public static function inFormTeacherSlotDuplicateMessage(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        ?string $scheduleDate,
        ?string $endTime = null,
    ): string {
        $teacher = User::find($facultyId);
        $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : "Teacher #{$facultyId}";
        $datePart = ($scheduleDate && trim($scheduleDate) !== '')
            ? \Carbon\Carbon::parse(substr(trim($scheduleDate), 0, 10))->format('m/d/Y') . ', '
            : '';

        return "{$name} is assigned to multiple sections on {$dayOfWeek}, {$datePart}"
            . self::normalizeTime($startTime)
            . ($endTime ? ' – ' . self::normalizeTime($endTime) : '')
            . ' in this form. Each teacher can only teach one class per period.';
    }

    /**
     * @param  array<string, array<string, mixed>>  $slots
     * @param  array<int, string>  $sections
     * @param  array<int, int|string>  $sectionRooms
     * @return list<string>
     */
    public static function collectJuniorHighGridConflicts(
        string $gradeLevel,
        string $dayOfWeek,
        ?string $scheduleDate,
        array $slots,
        array $sections,
        array $sectionRooms = [],
    ): array {
        $timeMap = SchoolScheduleSlots::scheduleSlotKeyMap('junior_high');
        $sharedFacultyIds = ScheduleStoreSupport::sharedFacultyIdStrings();
        $seenTeacherSlots = [];
        $seenSectionSlots = [];
        $seenRoomSlots = [];
        $dailyNewCounts = [];
        $conflicts = [];

        foreach ($slots as $timeKey => $sectionSlots) {
            if (! isset($timeMap[$timeKey])) {
                continue;
            }
            $startTime = $timeMap[$timeKey]['start'];
            $endTime = $timeMap[$timeKey]['end'];

            foreach ($sectionSlots as $idx => $cell) {
                $sectionName = $sections[$idx] ?? ('SECTION ' . ((int) $idx + 1));
                $cellRows = self::collectCellRowsFromSlotData(is_array($cell) ? $cell : []);
                if ($cellRows === []) {
                    continue;
                }

                $cellDup = self::duplicateSubjectTeacherInCell($cellRows);
                if ($cellDup) {
                    $conflicts[] = "{$sectionName} at {$startTime}: {$cellDup}";
                    continue;
                }

                $sectionSlotKey = $gradeLevel . '|' . $sectionName . '|' . $timeKey . '|' . ($scheduleDate ?? '');
                if (isset($seenSectionSlots[$sectionSlotKey])) {
                    $conflicts[] = self::duplicateScheduleForSlotMessage(
                        $gradeLevel, $sectionName, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                } else {
                    $seenSectionSlots[$sectionSlotKey] = true;
                    $sectionMsg = self::sectionSlotConflictMessage(
                        $gradeLevel, $sectionName, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                    if ($sectionMsg) {
                        $conflicts[] = $sectionMsg;
                    }
                }

                $roomId = ! empty($sectionRooms[$idx]) ? (int) $sectionRooms[$idx] : 0;
                if ($roomId > 0) {
                    $roomSlotKey = $roomId . '|' . $timeKey . '|' . ($scheduleDate ?? '');
                    if (isset($seenRoomSlots[$roomSlotKey])) {
                        $conflicts[] = "Room #{$roomId} at {$startTime} is assigned more than once in this form for the same date.";
                    } else {
                        $seenRoomSlots[$roomSlotKey] = true;
                        $roomMsg = self::roomSlotConflictMessage($roomId, $dayOfWeek, $startTime, $scheduleDate, $endTime);
                        if ($roomMsg) {
                            $conflicts[] = $roomMsg;
                        }
                    }
                }

                foreach ($cellRows as $row) {
                    $primarySubject = $row['subject'];
                    $primaryFaculty = $row['faculty_id'];
                    if (! $primaryFaculty) {
                        $conflicts[] = "{$sectionName}: subject \"{$primarySubject}\" has no teacher assigned.";
                        continue;
                    }

                    $availMsg = self::facultyAvailabilityConflictMessage((int) $primaryFaculty, 'mysql_jh');
                    if ($availMsg) {
                        $conflicts[] = "{$sectionName} at {$startTime}: {$availMsg}";
                        continue;
                    }

                    $slotKey = $primaryFaculty . '|' . $timeKey;
                    if (isset($seenTeacherSlots[$slotKey])) {
                        $teacher = User::find((int) $primaryFaculty);
                        $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                        $conflicts[] = "{$name} is assigned to multiple sections at {$startTime}.";
                    }
                    $seenTeacherSlots[$slotKey] = $sectionName;

                    $teacherSlotMsg = self::teacherSlotConflictMessage(
                        (int) $primaryFaculty, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                    if ($teacherSlotMsg) {
                        $conflicts[] = $teacherSlotMsg;
                    }

                    if (in_array($primaryFaculty, $sharedFacultyIds, true)) {
                        $crossExisting = ScheduleStoreSupport::crossSchoolApprovedConflict(
                            (int) $primaryFaculty,
                            $dayOfWeek,
                            $startTime
                        );
                        if ($crossExisting) {
                            $conflicts[] = self::duplicateScheduleForSlotMessage(
                                (string) ($crossExisting->grade_level ?? ''),
                                (string) ($crossExisting->section_name ?? ''),
                                $dayOfWeek,
                                $startTime,
                                $scheduleDate,
                                $endTime,
                                null,
                                $crossExisting
                            );
                        }
                    }
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    /**
     * @param  array<string, array<string, mixed>>  $slots
     * @param  array<string, string>  $sectionDisplayMap
     * @return list<string>
     */
    public static function collectGradeSchoolGridConflicts(
        string $gradeLevel,
        string $dayOfWeek,
        ?string $scheduleDate,
        array $slots,
        array $sectionDisplayMap,
        array $sectionRooms = [],
    ): array {
        $timeSlotMap = SchoolScheduleSlots::scheduleSlotKeyMap('grade_school');
        $sharedFacultyIds = ScheduleStoreSupport::sharedFacultyIdStrings('mysql_gs');
        $seenTeacherSlots = [];
        $seenSectionSlots = [];
        $seenRoomSlots = [];
        $conflicts = [];

        foreach ($slots as $timeKey => $sectionData) {
            if (! isset($timeSlotMap[$timeKey])) {
                continue;
            }
            $startTime = $timeSlotMap[$timeKey]['start'];
            $endTime = $timeSlotMap[$timeKey]['end'];

            foreach ($sectionData as $sectionKey => $data) {
                if (! isset($sectionDisplayMap[$sectionKey])) {
                    continue;
                }
                $displaySec = $sectionDisplayMap[$sectionKey];
                $cellRows = self::collectCellRowsFromSlotData(is_array($data) ? $data : []);
                if ($cellRows === []) {
                    continue;
                }

                $cellDup = self::duplicateSubjectTeacherInCell($cellRows);
                if ($cellDup) {
                    $conflicts[] = "{$displaySec} at {$startTime}: {$cellDup}";
                    continue;
                }

                $dateKey = ($scheduleDate !== null && trim($scheduleDate) !== '')
                    ? substr(trim($scheduleDate), 0, 10)
                    : '';
                $sectionSlotKey = $gradeLevel . '|' . $displaySec . '|' . $dayOfWeek . '|' . $timeKey . '|' . $dateKey;
                if (isset($seenSectionSlots[$sectionSlotKey])) {
                    $conflicts[] = self::inFormSectionSlotDuplicateMessage(
                        $gradeLevel, $displaySec, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                } else {
                    $seenSectionSlots[$sectionSlotKey] = true;
                    $sectionMsg = self::sectionSlotConflictMessage(
                        $gradeLevel, $displaySec, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                    if ($sectionMsg) {
                        $conflicts[] = $sectionMsg;
                    }
                }

                $roomId = ! empty($sectionRooms[$sectionKey]) ? (int) $sectionRooms[$sectionKey] : 0;
                if ($roomId > 0) {
                    $roomSlotKey = $roomId . '|' . $timeKey . '|' . ($scheduleDate ?? '');
                    if (isset($seenRoomSlots[$roomSlotKey])) {
                        $conflicts[] = "Room #{$roomId} at {$startTime} is assigned more than once in this form for the same date.";
                    } else {
                        $seenRoomSlots[$roomSlotKey] = true;
                        $roomMsg = self::roomSlotConflictMessage($roomId, $dayOfWeek, $startTime, $scheduleDate, $endTime);
                        if ($roomMsg) {
                            $conflicts[] = $roomMsg;
                        }
                    }
                }

                foreach ($cellRows as $row) {
                    $primaryFaculty = $row['faculty_id'];
                    if (! $primaryFaculty) {
                        $conflicts[] = "{$displaySec}: subject \"{$row['subject']}\" has no teacher assigned.";
                        continue;
                    }

                    $availMsg = self::facultyAvailabilityConflictMessage((int) $primaryFaculty, 'mysql_gs');
                    if ($availMsg) {
                        $conflicts[] = "{$displaySec} at {$startTime}: {$availMsg}";
                        continue;
                    }

                    $teacherSlotKey = $primaryFaculty . '|' . $dayOfWeek . '|' . $timeKey . '|' . $dateKey;
                    if (isset($seenTeacherSlots[$teacherSlotKey])) {
                        $conflicts[] = self::inFormTeacherSlotDuplicateMessage(
                            (int) $primaryFaculty, $dayOfWeek, $startTime, $scheduleDate, $endTime
                        );
                    } else {
                        $seenTeacherSlots[$teacherSlotKey] = $displaySec;
                    }

                    $teacherSlotMsg = self::teacherSlotConflictMessage(
                        (int) $primaryFaculty, $dayOfWeek, $startTime, $scheduleDate, $endTime
                    );
                    if ($teacherSlotMsg) {
                        $conflicts[] = $teacherSlotMsg;
                    }

                    if (in_array($primaryFaculty, $sharedFacultyIds, true)) {
                        $crossExisting = ScheduleStoreSupport::crossSchoolApprovedConflict(
                            (int) $primaryFaculty,
                            $dayOfWeek,
                            $startTime,
                            'mysql_gs'
                        );
                        if ($crossExisting) {
                            $conflicts[] = self::duplicateScheduleForSlotMessage(
                                (string) ($crossExisting->grade_level ?? ''),
                                (string) ($crossExisting->section_name ?? ''),
                                $dayOfWeek,
                                $startTime,
                                $scheduleDate,
                                $endTime,
                                null,
                                $crossExisting
                            );
                        }
                    }
                }
            }
        }

        return array_values(array_unique($conflicts));
    }
}
