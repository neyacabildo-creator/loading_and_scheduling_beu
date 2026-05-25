<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\User;
use Carbon\Carbon;

/**
 * Kinder 2, Kinder 1, and Nursery routines, sections, and weekly activity subjects.
 */
class KinderScheduleSupport
{
    public const GRADES = ['Kinder 2', 'Kinder 1', 'Nursery'];

    /** @var list<string> */
    public const ACTIVITY_SUBJECTS = [
        'Reading',
        'Language',
        'Filipino',
        'Mathematics',
        'CLVE/PE/Arts',
    ];

    /** @var array<string, string> */
    public const WEEKLY_ACTIVITY_BY_DAY = [
        'Monday'    => 'Reading',
        'Tuesday'   => 'Language',
        'Wednesday' => 'Filipino',
        'Thursday'  => 'Mathematics',
        'Friday'    => 'CLVE/PE/Arts',
    ];

    /** @var array<string, list<string>> */
    public const SECTIONS_BY_GRADE = [
        'Kinder 2' => ['K2 - GABRIEL', 'K2 - MICHAEL', 'K2 - RAPHAEL'],
        'Kinder 1' => ['NURSERY - CHERUBIM', 'K1 - SERAPHIM', 'K1 - URIEL'],
        'Nursery'  => ['NURSERY - CHERUBIM', 'K1 - SERAPHIM', 'K1 - URIEL'],
    ];

    /** @var list<string> */
    public const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public static function isKinderGrade(?string $grade): bool
    {
        $g = trim((string) $grade);

        return in_array($g, self::GRADES, true);
    }

    /**
     * Calendar date for a weekday (current week, Monday-based) when none was stored.
     */
    public static function inferredScheduleDateForDay(string $dayOfWeek, ?string $referenceDate = null): ?string
    {
        $day = trim($dayOfWeek);
        if (! in_array($day, self::WEEKDAYS, true)) {
            return null;
        }

        $index = array_search($day, self::WEEKDAYS, true);
        $ref = $referenceDate
            ? Carbon::parse($referenceDate)
            : Carbon::now();

        return $ref->copy()->startOfWeek(Carbon::MONDAY)->addDays((int) $index)->format('Y-m-d');
    }

    /**
     * Resolved schedule date for storage or display (explicit date wins).
     */
    public static function resolveScheduleDateForDay(string $dayOfWeek, ?string $scheduleDate = null): ?string
    {
        if ($scheduleDate !== null && trim($scheduleDate) !== '') {
            try {
                return Carbon::parse($scheduleDate)->format('Y-m-d');
            } catch (\Throwable) {
                // fall through to inferred
            }
        }

        return self::inferredScheduleDateForDay($dayOfWeek);
    }

    /**
     * @return list<string>
     */
    public static function sectionsForGrade(?string $grade): array
    {
        $g = trim((string) $grade);

        return self::SECTIONS_BY_GRADE[$g] ?? [];
    }

    /**
     * Daily routine rows for display (CLASS ROUTINE).
     *
     * @return list<array{start: string, end: string, label: string, type: string}>
     */
    public static function routineSlots(?string $grade): array
    {
        $g = trim((string) $grade);

        if ($g === 'Kinder 2') {
            return [
                ['start' => '08:00', 'end' => '08:15', 'label' => 'Arrival in School', 'type' => 'routine'],
                ['start' => '08:15', 'end' => '08:20', 'label' => 'Settling Down', 'type' => 'routine'],
                ['start' => '08:20', 'end' => '08:45', 'label' => 'Circle Time 1', 'type' => 'routine'],
                ['start' => '08:45', 'end' => '09:20', 'label' => 'Activity 1', 'type' => 'activity'],
                ['start' => '09:20', 'end' => '09:30', 'label' => 'ADLs and preparation for snacks', 'type' => 'routine'],
                ['start' => '09:30', 'end' => '09:50', 'label' => 'SNACKS', 'type' => 'break'],
                ['start' => '09:50', 'end' => '10:00', 'label' => 'Music and Movement', 'type' => 'routine'],
                ['start' => '10:00', 'end' => '10:30', 'label' => 'Circle Time 2', 'type' => 'routine'],
                ['start' => '10:30', 'end' => '10:40', 'label' => 'Story Time', 'type' => 'routine'],
                ['start' => '10:40', 'end' => '10:50', 'label' => 'ADLs', 'type' => 'routine'],
                ['start' => '10:50', 'end' => '11:00', 'label' => 'Goodbye', 'type' => 'routine'],
            ];
        }

        if ($g === 'Nursery') {
            return [
                ['start' => '13:00', 'end' => '13:15', 'label' => 'Arrival in School', 'type' => 'routine'],
                ['start' => '13:15', 'end' => '13:20', 'label' => 'Settling Down', 'type' => 'routine'],
                ['start' => '13:20', 'end' => '13:50', 'label' => 'Circle Time', 'type' => 'routine'],
                ['start' => '13:50', 'end' => '14:20', 'label' => 'Activity', 'type' => 'activity'],
                ['start' => '14:20', 'end' => '14:30', 'label' => 'ADLs and preparation for snacks', 'type' => 'routine'],
                ['start' => '14:30', 'end' => '14:50', 'label' => 'SNACKS', 'type' => 'break'],
                ['start' => '14:50', 'end' => '15:00', 'label' => 'Goodbye', 'type' => 'routine'],
            ];
        }

        // Kinder 1 (default afternoon)
        return [
            ['start' => '13:00', 'end' => '13:15', 'label' => 'Arrival in School', 'type' => 'routine'],
            ['start' => '13:15', 'end' => '13:20', 'label' => 'Settling Down', 'type' => 'routine'],
            ['start' => '13:20', 'end' => '13:50', 'label' => 'Circle Time 1', 'type' => 'routine'],
            ['start' => '13:50', 'end' => '14:20', 'label' => 'Activity 1', 'type' => 'activity'],
            ['start' => '14:20', 'end' => '14:30', 'label' => 'ADLs and preparation for snacks', 'type' => 'routine'],
            ['start' => '14:30', 'end' => '14:50', 'label' => 'SNACKS', 'type' => 'break'],
            ['start' => '14:50', 'end' => '15:00', 'label' => 'Music and Movement', 'type' => 'routine'],
            ['start' => '15:00', 'end' => '15:30', 'label' => 'Circle Time 2', 'type' => 'routine'],
            ['start' => '15:30', 'end' => '15:40', 'label' => 'Story Time', 'type' => 'routine'],
            ['start' => '15:40', 'end' => '15:50', 'label' => 'ADLs', 'type' => 'routine'],
            ['start' => '15:50', 'end' => '16:00', 'label' => 'Goodbye', 'type' => 'routine'],
        ];
    }

    /**
     * @return array{start: string, end: string, label: string}|null
     */
    public static function activitySlot(?string $grade): ?array
    {
        foreach (self::routineSlots($grade) as $slot) {
            if (($slot['type'] ?? '') === 'activity') {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Single activity row for admin print/export (approved weekly subjects).
     *
     * @return list<array{start: string, end: string, label: string, type: string}>
     */
    public static function printExportTimeSlots(?string $grade): array
    {
        $activity = self::activitySlot($grade);
        if (! $activity) {
            return [];
        }

        return [[
            'start' => $activity['start'],
            'end'   => $activity['end'],
            'label' => $activity['label'],
            'type'  => 'class',
        ]];
    }

    /**
     * Teachers-in-charge matrix (from official form).
     *
     * @return list<array{grade: string, section: string, teacher: string, assistant: string}>
     */
    /**
     * Matrix layout for Teachers-in-Charge card (official form).
     *
     * @return list<array{title: string, columns: list<string>, rows: list<array{label: string, values: list<string>}>}>
     */
    public static function teachersInChargeTables(): array
    {
        return [
            [
                'title'   => 'KINDER 2',
                'columns' => ['K2-GABRIEL', 'K2-MICHAEL', 'K2-RAPHAEL'],
                'rows'    => [
                    ['label' => 'Teacher', 'values' => ['D. Demapendan', 'A. Soriano', 'S. Umbalin']],
                    ['label' => 'Asst. Teacher', 'values' => ['M. Danipog', 'R. Lumboy', 'C. Orpilla']],
                ],
            ],
            [
                'title'   => 'KINDER 1',
                'columns' => ['NURSERY-CHERUBIM', 'K1-SERAPHIM', 'K1-URIEL'],
                'rows'    => [
                    ['label' => 'Teacher', 'values' => ['M. Danipog', 'R. Lumboy', 'C. Orpilla']],
                    ['label' => 'Asst. Teacher', 'values' => ['D. Demapendan', 'A. Soriano', 'S. Umbalin']],
                ],
            ],
        ];
    }

    public static function subjectsCsv(): string
    {
        return implode(', ', self::ACTIVITY_SUBJECTS);
    }

    /**
     * Match a faculty-load subject string to a canonical Kinder activity subject.
     */
    public static function normalizeActivitySubject(?string $raw): ?string
    {
        $needle = mb_strtolower(trim((string) $raw));
        if ($needle === '') {
            return null;
        }

        foreach (self::ACTIVITY_SUBJECTS as $canonical) {
            if (mb_strtolower($canonical) === $needle) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * Subjects this teacher may assign (from faculty load), limited to Kinder activity list.
     *
     * @return list<string>
     */
    public static function activitySubjectsForFaculty(int $facultyId, ?string $gradeLevel = null): array
    {
        if ($facultyId <= 0) {
            return [];
        }

        config(['database.school_connection' => 'mysql_gs']);
        $query = FacultyLoad::query()->where('faculty_id', $facultyId);
        if (self::isKinderGrade($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        } else {
            $query->whereIn('grade_level', self::GRADES);
        }

        $subjects = [];
        foreach ($query->get(['subject']) as $load) {
            foreach (explode(',', (string) ($load->subject ?? '')) as $part) {
                $canonical = self::normalizeActivitySubject($part);
                if ($canonical !== null) {
                    $subjects[mb_strtolower($canonical)] = $canonical;
                }
            }
        }

        $ordered = [];
        foreach (self::ACTIVITY_SUBJECTS as $canonical) {
            if (isset($subjects[mb_strtolower($canonical)])) {
                $ordered[] = $canonical;
            }
        }

        return $ordered;
    }

    /**
     * @param  array<string, string>  $subjectsByDay
     */
    public static function validateUniqueWeeklyActivities(array $subjectsByDay): ?string
    {
        $used = [];
        foreach (self::WEEKDAYS as $day) {
            $subject = trim((string) ($subjectsByDay[$day] ?? ''));
            if ($subject === '') {
                continue;
            }
            $key = mb_strtolower($subject);
            if (isset($used[$key])) {
                return 'Each activity subject may only appear once per week. "' . $subject . '" is assigned more than once.';
            }
            $used[$key] = true;
        }

        return null;
    }

    public static function teachersInCharge(): array
    {
        return [
            ['grade' => 'Kinder 2', 'section' => 'K2 - GABRIEL', 'teacher' => 'D. Demapendan', 'assistant' => 'M. Danipog'],
            ['grade' => 'Kinder 2', 'section' => 'K2 - MICHAEL', 'teacher' => 'A. Soriano', 'assistant' => 'R. Lumboy'],
            ['grade' => 'Kinder 2', 'section' => 'K2 - RAPHAEL', 'teacher' => 'S. Umbalin', 'assistant' => 'C. Orpilla'],
            ['grade' => 'Kinder 1', 'section' => 'NURSERY - CHERUBIM', 'teacher' => 'M. Danipog', 'assistant' => 'D. Demapendan'],
            ['grade' => 'Kinder 1', 'section' => 'K1 - SERAPHIM', 'teacher' => 'R. Lumboy', 'assistant' => 'A. Soriano'],
            ['grade' => 'Kinder 1', 'section' => 'K1 - URIEL', 'teacher' => 'C. Orpilla', 'assistant' => 'S. Umbalin'],
        ];
    }

    public static function resolveFacultyKinderGrade(int $facultyId, ?string $requested = null): ?string
    {
        if (self::isKinderGrade($requested)) {
            return trim((string) $requested);
        }

        config(['database.school_connection' => 'mysql_gs']);
        $fromLoad = FacultyLoad::query()
            ->where('faculty_id', $facultyId)
            ->whereIn('grade_level', self::GRADES)
            ->orderByDesc('updated_at')
            ->value('grade_level');

        return self::isKinderGrade($fromLoad) ? trim((string) $fromLoad) : null;
    }

    /**
     * Saved activity subjects per weekday for a section.
     *
     * @return array<string, string>
     */
    public static function savedWeeklyActivity(
        string $gradeLevel,
        string $sectionName,
        ?int $facultyId = null
    ): array {
        $activity = self::activitySlot($gradeLevel);
        if (! $activity) {
            return self::WEEKLY_ACTIVITY_BY_DAY;
        }

        config(['database.school_connection' => 'mysql_gs']);
        $query = ClassSchedule::query()
            ->where('grade_level', $gradeLevel)
            ->where('section_name', $sectionName)
            ->where('start_time', $activity['start'])
            ->where('end_time', $activity['end'])
            ->where('admin_approved', true)
            ->where('status', 'active');

        if ($facultyId) {
            $query->where('faculty_id', $facultyId);
        }

        $map = [];
        foreach ($query->get(['day_of_week', 'subject']) as $row) {
            $day = trim((string) ($row->day_of_week ?? ''));
            if ($day !== '') {
                $map[$day] = trim((string) ($row->subject ?? ''));
            }
        }

        foreach (self::WEEKDAYS as $day) {
            if (! isset($map[$day])) {
                $map[$day] = '';
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    public static function cardViewData(User $teacher, ?string $gradeLevel = null, ?string $sectionName = null): array
    {
        $grade = self::resolveFacultyKinderGrade((int) $teacher->id, $gradeLevel) ?? 'Kinder 2';
        $sections = self::sectionsForGrade($grade);
        $section = $sectionName && in_array($sectionName, $sections, true)
            ? $sectionName
            : ($sections[0] ?? '');

        return [
            'teacher'          => $teacher,
            'gradeLevel'       => $grade,
            'sectionName'      => $section,
            'sections'         => $sections,
            'routineSlots'     => self::routineSlots($grade),
            'weeklyActivity'   => self::savedWeeklyActivity($grade, $section, (int) $teacher->id),
            'activitySubjects' => self::activitySubjectsForFaculty((int) $teacher->id, $grade),
            'weekdays'         => self::WEEKDAYS,
            'teachersInCharge'       => self::teachersInCharge(),
            'teachersInChargeTables' => self::teachersInChargeTables(),
            'schoolYear'       => '2025-2026',
        ];
    }

    /**
     * Persist weekly activity row (Mon–Fri) for kinder section.
     *
     * @param  array<string, string>  $subjectsByDay
     */
    public static function storeWeeklyActivity(
        int $facultyId,
        string $gradeLevel,
        string $sectionName,
        array $subjectsByDay,
        ?int $actorUserId = null
    ): int {
        if (! self::isKinderGrade($gradeLevel)) {
            throw new \InvalidArgumentException('Invalid kinder grade level.');
        }

        $uniqueMsg = self::validateUniqueWeeklyActivities($subjectsByDay);
        if ($uniqueMsg !== null) {
            throw new \InvalidArgumentException($uniqueMsg);
        }

        $activity = self::activitySlot($gradeLevel);
        if (! $activity) {
            throw new \RuntimeException('Activity slot not defined for ' . $gradeLevel);
        }

        config(['database.school_connection' => 'mysql_gs']);

        $saved = 0;
        foreach (self::WEEKDAYS as $day) {
            $subject = trim((string) ($subjectsByDay[$day] ?? ''));
            if ($subject === '') {
                continue;
            }

            ClassSchedule::query()
                ->where('faculty_id', $facultyId)
                ->where('grade_level', $gradeLevel)
                ->where('section_name', $sectionName)
                ->where('day_of_week', $day)
                ->where('start_time', $activity['start'])
                ->where('end_time', $activity['end'])
                ->delete();

            $changeLog = ScheduleAudit::appendChangeLog(
                [],
                'created',
                $actorUserId ? (\App\Models\User::find($actorUserId)?->name) : (auth()->user()?->name),
                ['details' => 'Kinder/Nursery weekly activity submitted for approval']
            );

            ScheduleStoreSupport::createPendingSchedule([
                'faculty_id'     => $facultyId,
                'subject'        => $subject,
                'grade_level'    => $gradeLevel,
                'section_name'   => $sectionName,
                'day_of_week'    => $day,
                'schedule_date'  => self::resolveScheduleDateForDay($day, $scheduleDate),
                'start_time'     => $activity['start'],
                'end_time'       => $activity['end'],
                'student_count'  => 0,
                'status'         => 'pending',
                'admin_approved' => false,
                'change_log'     => $changeLog,
            ], $actorUserId ?? $facultyId);
            $saved++;
        }

        return $saved;
    }
}
