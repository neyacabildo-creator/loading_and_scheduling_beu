<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherPortalSupport
{
    public static function hasClassSchedulesTable(?string $connection = null): bool
    {
        $connection = $connection ?? config('database.school_connection', 'mysql_jh');

        try {
            return Schema::connection($connection)->hasTable('class_schedules');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Approved/active schedules for a teacher dashboard (empty collection if table missing).
     */
    public static function approvedSchedulesForTeacher(int $facultyId, ?string $connection = null): Collection
    {
        if (! self::hasClassSchedulesTable($connection)) {
            return collect();
        }

        return ClassSchedule::on($connection ?? config('database.school_connection', 'mysql_jh'))
            ->where('faculty_id', $facultyId)
            ->where(function ($q) {
                $q->where('admin_approved', true)->orWhere('status', 'active');
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return array{mySchedules: Collection, myClasses: int, totalStudents: int, teachingLoad: int|float, pendingTasks: int}
     */
    public static function dashboardMetrics(int $facultyId, ?string $connection = null): array
    {
        $mySchedules = self::approvedSchedulesForTeacher($facultyId, $connection);

        return [
            'mySchedules'   => $mySchedules,
            'myClasses'     => $mySchedules->count(),
            'totalStudents' => (int) $mySchedules->sum(fn ($schedule) => $schedule->student_count ?? 0),
            'teachingLoad'  => (int) $mySchedules->sum(fn ($schedule) => $schedule->units ?? 0),
            'pendingTasks'  => $mySchedules->where('status', 'pending')->count(),
        ];
    }

    public static function scheduleDurationHours(?string $start, ?string $end): float
    {
        if (! $start || ! $end) {
            return 0;
        }

        $s = strtotime('1970-01-01 ' . substr((string) $start, 0, 8));
        $e = strtotime('1970-01-01 ' . substr((string) $end, 0, 8));

        if ($s === false || $e === false || $e <= $s) {
            return 0;
        }

        return round(($e - $s) / 3600, 2);
    }

    /**
     * Class schedules shown on teacher workload (faculty load + history).
     */
    public static function classSchedulesForTeacherWorkload(int $facultyId, ?string $connection = null): Collection
    {
        if (! self::hasClassSchedulesTable($connection)) {
            return collect();
        }

        return ClassSchedule::on($connection ?? config('database.school_connection', 'mysql_jh'))
            ->where('faculty_id', $facultyId)
            ->whereNotIn('status', ['rejected', 'deleted'])
            ->with(['room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $classSchedules
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $facultyLoads
     */
    public static function buildWorkloadSchedules($classSchedules, $facultyLoads): array
    {
        $loads = collect($facultyLoads);
        $allSchedules = collect($classSchedules);
        $rows = [];

        foreach ($classSchedules as $s) {
            $hours = self::scheduleDurationHours($s->start_time, $s->end_time);
            $load = $loads->first(fn ($l) => self::scheduleMatchesFacultyLoad($s, $l));
            $grade = ScheduleDisplaySupport::resolveGradeAndSection($s);

            $rows[] = [
                'id'               => $s->id,
                'source'           => 'class_schedule',
                'subject'          => trim((string) ($s->subject ?? '')) ?: trim((string) ($load?->subject ?? '')),
                'subject_name'     => trim((string) ($s->subject ?? '')) ?: trim((string) ($load?->subject ?? '')),
                'load_hours'       => $hours > 0 ? $hours : (float) ($load?->load_hours ?? 0),
                'classes_assigned' => (int) ($load?->classes_assigned ?? 0),
                'units'            => max(1, (int) ($load?->classes_assigned ?? 1)),
                'status'           => $s->admin_approved ? 'approved' : ($s->status ?? 'active'),
                'day_of_week'      => $s->day_of_week,
                'start_time'       => $s->start_time,
                'end_time'         => $s->end_time,
                'grade_level'      => $grade['grade_level'] ?: ($s->grade_level ?? null),
                'section_name'     => $grade['section_name'] ?: ($s->section_name ?? null),
            ];
        }

        foreach ($loads as $load) {
            if (collect($rows)->contains(fn ($row) => self::rowMatchesFacultyLoad($row, $load))) {
                continue;
            }

            $comp = self::complementaryScheduleForLoad($load, $allSchedules);
            [$parsedGrade, $parsedSection] = ScheduleDisplaySupport::parseGradeSection($load->grade_level);
            $gradeLevel = $parsedGrade !== '' ? $parsedGrade : trim((string) ($load->grade_level ?? ''));
            $sectionName = $parsedSection !== '' ? $parsedSection : ($comp?->section_name ?? null);
            if ($gradeLevel === '' && $comp) {
                $fromSchedule = ScheduleDisplaySupport::resolveGradeAndSection($comp);
                $gradeLevel = $fromSchedule['grade_level'] ?: $gradeLevel;
                $sectionName = $sectionName ?: $fromSchedule['section_name'];
            }

            $hours = (float) ($load->load_hours ?? 0);
            if ($hours <= 0 && $comp) {
                $hours = self::scheduleDurationHours($comp->start_time, $comp->end_time);
            }

            $units = (int) ($load->classes_assigned ?? 0);
            if ($units <= 0) {
                $units = 1;
            }

            $rows[] = [
                'id'               => $load->id,
                'source'           => 'faculty_load',
                'subject'          => trim((string) ($load->subject ?? '')),
                'subject_name'     => trim((string) ($load->subject ?? '')),
                'load_hours'       => $hours,
                'classes_assigned' => $units,
                'units'            => $units,
                'status'           => $load->status ?? 'available',
                'day_of_week'      => $comp?->day_of_week,
                'start_time'       => $comp?->start_time,
                'end_time'         => $comp?->end_time,
                'grade_level'      => $gradeLevel !== '' ? $gradeLevel : null,
                'section_name'     => $sectionName,
            ];
        }

        return array_values(array_map(fn (array $row) => self::normalizeWorkloadRow($row), $rows));
    }

    /**
     * @param  object  $schedule
     * @param  object  $load
     */
    public static function scheduleMatchesFacultyLoad($schedule, $load): bool
    {
        if (self::subjectsMatch($schedule->subject ?? '', $load->subject ?? '')) {
            return true;
        }

        $scheduleGrade = trim((string) ($schedule->grade_level ?? ''));
        $loadGrade = trim((string) ($load->grade_level ?? ''));

        if ($scheduleGrade === '' || $loadGrade === '') {
            return false;
        }

        if (strcasecmp($scheduleGrade, $loadGrade) === 0) {
            return true;
        }

        [$parsedGrade] = ScheduleDisplaySupport::parseGradeSection($loadGrade);

        return $parsedGrade !== '' && strcasecmp($scheduleGrade, $parsedGrade) === 0;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  object  $load
     */
    public static function rowMatchesFacultyLoad(array $row, $load): bool
    {
        if (self::subjectsMatch($row['subject'] ?? '', $load->subject ?? '')) {
            return true;
        }

        $rowGrade = trim((string) ($row['grade_level'] ?? ''));
        $loadGrade = trim((string) ($load->grade_level ?? ''));

        if ($rowGrade === '' || $loadGrade === '') {
            return false;
        }

        if (strcasecmp($rowGrade, $loadGrade) === 0) {
            return true;
        }

        [$parsedGrade] = ScheduleDisplaySupport::parseGradeSection($loadGrade);

        return $parsedGrade !== '' && strcasecmp($rowGrade, $parsedGrade) === 0;
    }

    /**
     * @param  \Illuminate\Support\Collection  $classSchedules
     */
    public static function complementaryScheduleForLoad($load, $classSchedules): ?object
    {
        return collect($classSchedules)->first(function ($schedule) use ($load) {
            return self::scheduleMatchesFacultyLoad($schedule, $load);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function normalizeWorkloadRow(array $row): array
    {
        $gradeLevel = trim((string) ($row['grade_level'] ?? ''));
        $sectionName = trim((string) ($row['section_name'] ?? $row['section'] ?? ''));

        if ($gradeLevel === '' && $sectionName === '') {
            [$parsedGrade, $parsedSection] = ScheduleDisplaySupport::parseGradeSection($row['grade_section'] ?? null);
            $gradeLevel = $parsedGrade;
            $sectionName = $parsedSection;
        }

        $gradeSection = self::gradeSectionLabel($gradeLevel, $sectionName);
        $subject = trim((string) ($row['subject'] ?? $row['subject_name'] ?? ''));
        if ($subject === '' && $gradeSection !== '') {
            $subject = $gradeSection;
        }

        $units = (int) ($row['units'] ?? $row['classes_assigned'] ?? 0);
        if ($units <= 0) {
            $units = $subject !== '' || $gradeSection !== '' ? 1 : 0;
        }

        $loadHours = (float) ($row['load_hours'] ?? 0);
        if ($loadHours <= 0 && ! empty($row['start_time']) && ! empty($row['end_time'])) {
            $loadHours = self::scheduleDurationHours($row['start_time'], $row['end_time']);
        }
        if ($loadHours <= 0 && $units > 0) {
            $loadHours = (float) $units;
        }

        $day = trim((string) ($row['day_of_week'] ?? ''));

        return [
            'id'               => $row['id'] ?? null,
            'source'           => $row['source'] ?? null,
            'subject'          => $subject !== '' ? $subject : '—',
            'subject_name'     => $subject !== '' ? $subject : '—',
            'grade_level'      => $gradeLevel !== '' ? $gradeLevel : null,
            'section_name'     => $sectionName !== '' ? $sectionName : null,
            'grade_section'    => $gradeSection !== '' ? $gradeSection : '—',
            'room'             => $gradeSection !== '' ? $gradeSection : '—',
            'day_of_week'      => $day !== '' ? $day : null,
            'start_time'       => $row['start_time'] ?? null,
            'end_time'         => $row['end_time'] ?? null,
            'units'            => $units,
            'classes_assigned' => $units,
            'load_hours'       => round($loadHours, 2),
            'status'           => $row['status'] ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function workloadHistoryEntry(array $row, ?string $schoolYear = null): array
    {
        $normalized = self::normalizeWorkloadRow($row);
        $schoolYear = $schoolYear ?? (date('Y') . '-' . (date('Y') + 1));

        return [
            'id'            => $normalized['id'],
            'subject'       => $normalized['subject'],
            'grade_level'   => $normalized['grade_level'],
            'section'       => $normalized['section_name'],
            'section_name'  => $normalized['section_name'],
            'grade_section' => $normalized['grade_section'],
            'room'          => $normalized['room'],
            'day_of_week'   => $normalized['day_of_week'] ?? '—',
            'start_time'    => $normalized['start_time'],
            'end_time'      => $normalized['end_time'],
            'units'         => (int) $normalized['units'],
            'load_hours'    => (float) $normalized['load_hours'],
            'school_year'   => $schoolYear,
            'status'        => $normalized['status'],
        ];
    }

    public static function subjectsMatch(string $a, string $b): bool
    {
        $a = trim(strtolower($a));
        $b = trim(strtolower($b));

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }

    /**
     * Grade level and section label (e.g. "Grade 5 – ST. MARGARETTE").
     */
    public static function gradeSectionLabel(?string $gradeLevel, ?string $sectionName = null, ?string $section = null): string
    {
        $grade = trim((string) ($gradeLevel ?? ''));
        $sec = trim((string) ($sectionName ?? $section ?? ''));

        if ($grade === '' && $sec === '') {
            return '';
        }

        return $grade . ($sec !== '' ? ' – ' . $sec : '');
    }

    /**
     * Room column for teacher loading tables: physical room when set, otherwise grade & section.
     *
     * @param  object  $schedule  Class schedule row/model or teacher_loading_schedules row
     */
    public static function roomLabel(object $schedule): string
    {
        return self::displayRoomFromRow($schedule);
    }

    /**
     * @param  object  $row  DB row or mapped schedule object
     */
    public static function displayRoomFromRow(object $row): string
    {
        $room = $row->room ?? null;

        if (is_object($room) && ! empty($room->room_number)) {
            return 'Room ' . $room->room_number;
        }

        if (is_string($room)) {
            $text = trim($room);
            if ($text !== '' && ! self::isPlaceholderRoom($text)) {
                return $text;
            }
        }

        if (! empty($row->room_id)) {
            $related = $row->room ?? null;
            if (is_object($related) && ! empty($related->room_number)) {
                return 'Room ' . $related->room_number;
            }
        }

        $fromGrade = self::gradeSectionLabel(
            $row->grade_level ?? null,
            $row->section_name ?? null,
            $row->section ?? null
        );

        if ($fromGrade !== '') {
            return $fromGrade;
        }

        if (! empty($row->grade_section)) {
            return trim((string) $row->grade_section);
        }

        return '—';
    }

    private static function isPlaceholderRoom(string $value): bool
    {
        $v = strtolower(trim($value));

        return in_array($v, ['tba', 'to be assigned', '—', '-', 'n/a'], true)
            || preg_match('/^room\s*#\d+$/', $v) === 1;
    }

    /**
     * Shared teacher registry keyed by faculty_id.
     */
    public static function sharedTeacherMap(string $schoolConnection): Collection
    {
        try {
            $fromTable = DB::connection($schoolConnection)
                ->table('shared_teachers')
                ->where('is_active', true)
                ->get()
                ->keyBy(fn ($r) => (int) $r->faculty_id);
        } catch (\Throwable $e) {
            $fromTable = collect();
        }

        $fromRole = User::whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))
            ->get()
            ->map(fn ($u) => (object) [
                'faculty_id'   => $u->id,
                'teacher_name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->name,
                'department'   => null,
                'school_level' => null,
            ])
            ->keyBy(fn ($r) => (int) $r->faculty_id);

        return $fromTable->merge($fromRole);
    }

    public static function sharedTeacherBadge(?object $meta): ?array
    {
        if (! $meta) {
            return null;
        }

        $dept = trim((string) ($meta->department ?? ''));
        $level = str_replace('_', ' ', (string) ($meta->school_level ?? ''));
        $levelLabel = $level !== ''
            ? ucwords($level)
            : 'Cross-department';

        return [
            'label'      => 'Shared Teacher',
            'department' => $dept !== '' ? $dept : $levelLabel,
            'detail'     => $dept !== '' ? $dept : $levelLabel,
        ];
    }

    /**
     * @param  iterable  $schedules  Array of schedule arrays
     */
    public static function enrichSchedulesForReview(iterable $schedules, string $schoolConnection): array
    {
        $shared = self::sharedTeacherMap($schoolConnection);
        $roomIds = collect($schedules)->pluck('room_id')->filter()->unique();
        $rooms = $roomIds->isNotEmpty()
            ? Room::whereIn('id', $roomIds)->get()->keyBy('id')
            : collect();

        $facultyIds = collect($schedules)->pluck('faculty_id')->filter()->unique();
        $users = $facultyIds->isNotEmpty()
            ? User::whereIn('id', $facultyIds)->get()->keyBy('id')
            : collect();

        return collect($schedules)->map(function ($s) use ($shared, $rooms, $users) {
            $arr = is_array($s) ? $s : (array) $s;
            $facultyId = (int) ($arr['faculty_id'] ?? 0);
            $faculty = $users->get($facultyId);
            $room = isset($arr['room_id']) ? $rooms->get($arr['room_id']) : null;

            $arr['grade_section'] = self::gradeSectionLabel($arr['grade_level'] ?? null, $arr['section_name'] ?? null);
            $arr['room_label'] = $room && $room->room_number
                ? 'Room ' . $room->room_number
                : ($arr['grade_section'] !== '' ? $arr['grade_section'] : '—');

            if ($faculty) {
                $arr['faculty'] = [
                    'id'         => $faculty->id,
                    'first_name' => $faculty->first_name,
                    'last_name'  => $faculty->last_name,
                    'name'       => trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? '')) ?: $faculty->name,
                ];
            }

            $meta = $shared->get($facultyId);
            if ($meta) {
                $badge = self::sharedTeacherBadge($meta);
                $arr['is_shared_teacher'] = true;
                $arr['shared_teacher_label'] = $badge['label'];
                $arr['shared_from_department'] = $badge['detail'];
            } else {
                $arr['is_shared_teacher'] = false;
            }

            return $arr;
        })->values()->all();
    }

    public static function normalizeGradeKey(?string $grade): string
    {
        if (! $grade) {
            return '';
        }

        if (preg_match('/(\d+)/', $grade, $m)) {
            return 'Grade ' . $m[1];
        }

        return trim($grade);
    }

    /**
     * Approved/active class schedules for the logged-in teacher (export & print).
     */
    public static function teacherSchedulesForExport(int $facultyId): Collection
    {
        return \App\Models\ClassSchedule::query()
            ->where('faculty_id', $facultyId)
            ->where(function ($q) {
                $q->where('admin_approved', true)->orWhere('status', 'active');
            })
            ->with(['room'])
            ->orderByRaw("FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
            ->orderBy('start_time')
            ->get();
    }

    public static function formatTimeLabel(?string $time): string
    {
        if (! $time) {
            return '—';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('g:i A');
        } catch (\Throwable $e) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i', substr((string) $time, 0, 5))->format('g:i A');
            } catch (\Throwable $e2) {
                return substr((string) $time, 0, 5);
            }
        }
    }
}
