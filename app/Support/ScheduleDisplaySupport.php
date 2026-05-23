<?php

namespace App\Support;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Normalizes grade/section, date, and room labels for admin schedule tables and APIs.
 */
class ScheduleDisplaySupport
{
    /**
     * @return array{0: string, 1: string} [grade_level, section_name]
     */
    public static function parseGradeSection(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return ['', ''];
        }

        if (preg_match('/^(Grade\s+\d+)\s*[-–]\s*(.+)$/iu', $value, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        if (preg_match('/^(Grade\s+\d+)\s+(.+)$/iu', $value, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return ['', $value];
    }

    /**
     * @return array{grade_level: string, section_name: string}
     */
    public static function resolveGradeAndSection(object|array $schedule): array
    {
        $grade = trim((string) (is_array($schedule) ? ($schedule['grade_level'] ?? '') : ($schedule->grade_level ?? '')));
        $section = trim((string) (is_array($schedule) ? ($schedule['section_name'] ?? '') : ($schedule->section_name ?? '')));

        if ($grade === '' && $section === '') {
            $legacy = trim((string) (is_array($schedule) ? ($schedule['grade_section'] ?? '') : ($schedule->grade_section ?? '')));
            if ($legacy !== '') {
                [$grade, $section] = self::parseGradeSection($legacy);
            }
        }

        return ['grade_level' => $grade, 'section_name' => $section];
    }

    public static function gradeSectionLabel(object|array $schedule): string
    {
        $parts = self::resolveGradeAndSection($schedule);
        $label = TeacherPortalSupport::gradeSectionLabel($parts['grade_level'], $parts['section_name']);

        return $label !== '' ? $label : '—';
    }

    public static function formatScheduleDate(mixed $scheduleDate): string
    {
        if ($scheduleDate === null || $scheduleDate === '') {
            return '—';
        }

        try {
            if ($scheduleDate instanceof \DateTimeInterface) {
                return $scheduleDate->format('m/d/Y');
            }

            $raw = (string) $scheduleDate;
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
                return Carbon::parse(substr($raw, 0, 10))->format('m/d/Y');
            }

            return Carbon::parse($raw)->format('m/d/Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function roomLabel(object|array $schedule, mixed $room = null): string
    {
        $row = is_array($schedule) ? (object) $schedule : $schedule;
        if ($room !== null) {
            $row->room = $room;
        }

        $label = TeacherPortalSupport::displayRoomFromRow($row);

        return $label !== '' && $label !== '—' ? $label : self::gradeSectionLabel($schedule);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichForApi(array $data, object $schedule, mixed $room = null, mixed $faculty = null): array
    {
        $parts = self::resolveGradeAndSection($schedule);
        $data['grade_level'] = $parts['grade_level'] ?: ($data['grade_level'] ?? null);
        $data['section_name'] = $parts['section_name'] ?: ($data['section_name'] ?? null);
        $data['grade_section_label'] = self::gradeSectionLabel($schedule);
        $data['grade'] = $data['grade_section_label'];

        if ($schedule instanceof ClassSchedule && method_exists($schedule, 'getRawOriginal')) {
            $data['schedule_date'] = $schedule->getRawOriginal('schedule_date') ?? $data['schedule_date'] ?? null;
        }

        $data['display_date'] = self::formatScheduleDate($data['schedule_date'] ?? null);
        $data['room_label'] = self::roomLabel($schedule, $room);

        if ($faculty !== null) {
            $facultyName = is_array($faculty)
                ? trim((string) ($faculty['name'] ?? ''))
                : trim((string) ($faculty->name ?? ''));
            if ($facultyName === '' && is_object($faculty)) {
                $facultyName = trim(($faculty->first_name ?? '').' '.($faculty->last_name ?? ''));
            }
            if ($facultyName !== '') {
                $data['faculty'] = is_array($faculty)
                    ? array_merge($faculty, ['name' => $facultyName])
                    : ['id' => $faculty->id ?? null, 'name' => $facultyName];
            }
        }

        return $data;
    }

    /**
     * @param  Collection<int, ClassSchedule>|iterable<int, ClassSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    public static function mapCollectionForApi(iterable $schedules, Collection $users, Collection $rooms): array
    {
        $rows = [];

        foreach ($schedules as $schedule) {
            $data = $schedule instanceof ClassSchedule ? $schedule->toArray() : (array) $schedule;
            $facultyId = $schedule->faculty_id ?? null;
            $roomId = $schedule->room_id ?? null;
            $rows[] = self::enrichForApi(
                $data,
                $schedule,
                $roomId && isset($rooms[$roomId]) ? $rooms[$roomId] : null,
                $facultyId && isset($users[$facultyId]) ? $users[$facultyId] : null
            );
        }

        return $rows;
    }

    /**
     * Blade helpers: set display attributes on schedule models.
     */
    public static function applyToModel(object $schedule, mixed $room = null): void
    {
        $parts = self::resolveGradeAndSection($schedule);
        $schedule->grade_level = $parts['grade_level'] ?: $schedule->grade_level;
        $schedule->section_name = $parts['section_name'] ?: $schedule->section_name;
        $schedule->grade_section = self::gradeSectionLabel($schedule);
        $schedule->display_date = self::formatScheduleDate(
            $schedule instanceof ClassSchedule ? $schedule->getRawOriginal('schedule_date') : ($schedule->schedule_date ?? null)
        );
        $schedule->room_label = self::roomLabel($schedule, $room);
    }
}
