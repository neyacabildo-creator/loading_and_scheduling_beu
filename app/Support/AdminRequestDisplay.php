<?php

namespace App\Support;

class AdminRequestDisplay
{
    public static function formatTime(?string $time): string
    {
        return TeacherPortalSupport::formatTimeLabel($time);
    }

    public static function formatTimeRange(?string $start, ?string $end): string
    {
        if (! $start) {
            return '';
        }

        $line = self::formatTime($start);
        if ($end) {
            $line .= ' – ' . self::formatTime($end);
        }

        return $line;
    }

    public static function requestTypeLabel(?string $type): string
    {
        if (TeacherPresenceSupport::isAbsenceLeaveType($type)) {
            return TeacherPresenceSupport::typeLabel($type);
        }

        return match ($type) {
            'schedule_change'        => 'Schedule Change',
            'time_change'            => 'Time Change',
            'room_change'            => 'Room Change',
            'teacher_reassignment'   => 'Teacher Reassignment',
            'section_change'         => 'Section Change',
            'schedule_request'       => 'Schedule Request',
            'load_adjustment'        => 'Load Adjustment',
            'conflict_report'        => 'Conflict Report',
            'other'                  => 'Other',
            default                  => $type ? ucfirst(str_replace('_', ' ', (string) $type)) : 'Adjustment',
        };
    }

    public static function gradeSectionLabel(?string $gradeSection, ?string $gradeLevel, ?string $sectionName): string
    {
        $level = trim((string) ($gradeLevel ?? ''));
        $section = trim((string) ($sectionName ?? ''));

        if ($level !== '' || $section !== '') {
            return trim($level . ($section !== '' ? ' – ' . $section : ''));
        }

        $label = trim((string) ($gradeSection ?? ''));
        if ($label !== '' && ! self::looksLikeFreeTextNote($label)) {
            return $label;
        }

        return '';
    }

    /**
     * Admin table row: subject, grade/section, day/time, and notes kept separate.
     *
     * @return array{
     *   subject: string,
     *   request_type_label: string,
     *   grade_section: string,
     *   day: string,
     *   time_range: string,
     *   has_time: bool,
     *   reason: string,
     *   detail: string|null,
     *   notes: string
     * }
     */
    public static function teacherRequestView(object $row): array
    {
        $parsed = self::parseProposed($row->proposed_changes ?? null);

        $gradeLevel = trim((string) ($row->grade_level ?? $parsed['grade_level'] ?? ''));
        $sectionName = trim((string) ($row->section_name ?? $parsed['section_name'] ?? ''));
        $gradeSection = self::gradeSectionLabel($row->grade_section ?? null, $gradeLevel ?: null, $sectionName ?: null);
        if ($gradeSection === '' && ! empty($row->grade_section)) {
            $gradeSection = trim((string) $row->grade_section);
        }

        $subject = trim((string) ($row->subject ?? $parsed['subject'] ?? ''));
        $typeLabel = ! empty($row->request_type_label)
            ? (string) $row->request_type_label
            : self::requestTypeLabel($row->request_type ?? null);

        $day = trim((string) ($row->day_of_week ?? $parsed['day_of_week'] ?? $parsed['preferred_day'] ?? ''));
        $start = $row->preferred_start_time ?? $parsed['preferred_start_time'] ?? $parsed['preferred_time'] ?? $parsed['start_time'] ?? null;
        $end = $row->preferred_end_time ?? $parsed['preferred_end_time'] ?? $parsed['end_time'] ?? null;
        $timeRange = self::formatTimeRange(
            $start !== null && $start !== '' ? (string) $start : null,
            $end !== null && $end !== '' ? (string) $end : null
        );

        $reason = trim((string) ($row->description ?? $row->reason ?? ''));
        $detail = self::proposedFreeTextDetail($row->proposed_changes ?? null);
        if ($detail !== null && $detail === $reason) {
            $detail = null;
        }

        $dateFrom = $row->date_from ?? $parsed['date_from'] ?? null;
        $dateTo = $row->date_to ?? $parsed['date_to'] ?? null;
        $leaveDates = '';
        if ($dateFrom && $dateTo) {
            $leaveDates = \Carbon\Carbon::parse($dateFrom)->format('M d, Y')
                . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y');
        }

        return [
            'subject'              => $subject !== '' ? $subject : '—',
            'request_type_label'   => $typeLabel,
            'grade_section'        => $gradeSection,
            'day'                  => $day,
            'has_day'              => $day !== '',
            'time_range'           => $timeRange,
            'has_time'             => $timeRange !== '',
            'leave_dates'          => $leaveDates,
            'has_leave_dates'      => $leaveDates !== '',
            'is_absence_leave'     => TeacherPresenceSupport::isAbsenceLeaveType($row->request_type ?? null),
            'reason'               => $reason,
            'detail'               => $detail,
            'notes'                => self::notesOnly($reason, $detail),
        ];
    }

    /**
     * Notes column: reason and optional extra detail only (never grade/time/type).
     */
    public static function notesOnly(?string $reason, ?string $detail): string
    {
        $reason = trim((string) ($reason ?? ''));
        $detail = trim((string) ($detail ?? ''));

        if ($reason !== '' && $detail !== '') {
            return $reason;
        }

        if ($reason !== '') {
            return $reason;
        }

        return $detail !== '' ? $detail : '';
    }

    /**
     * @deprecated Use notesOnly() — kept for any legacy callers
     */
    public static function notesText(?string $reason, ?string $proposedChanges): string
    {
        return self::notesOnly($reason, self::proposedFreeTextDetail($proposedChanges));
    }

    public static function proposedFreeTextDetail(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $text = trim($raw);

            return $text !== '' ? $text : null;
        }

        return isset($decoded['detail']) && trim((string) $decoded['detail']) !== ''
            ? trim((string) $decoded['detail'])
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseProposed(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['detail' => trim($raw)];
    }

    private static function looksLikeFreeTextNote(string $value): bool
    {
        if (strlen($value) <= 4 && ! preg_match('/\d/', $value)) {
            return true;
        }

        if (str_contains($value, ' — ') || str_contains($value, ' - ')) {
            return true;
        }

        return false;
    }
}
