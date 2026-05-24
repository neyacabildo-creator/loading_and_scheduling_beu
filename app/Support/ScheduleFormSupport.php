<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds data for admin "Create Schedule" forms with safe fallbacks when
 * school operational tables are missing or not yet migrated.
 */
class ScheduleFormSupport
{
    public const JH_SUBJECT_NORM = [
        'ARALING PANLIPUNAN' => ['AP'],
        'CHRISTIAN LIVING EDUCATION' => ['CLVE'],
        'TECHNOLOGY AND LIVELIHOOD EDUCATION' => ['TLE'],
        'MATHEMATICS' => ['MATHEMATICS'],
        'ADVANCED MATHEMATICS' => ['ADV MATH'],
        'SCIENCE' => ['SCIENCE'],
        'ADVANCED SCIENCE' => ['ADV SCI'],
        'ENGLISH' => ['ENGLISH'],
        'FILIPINO' => ['FILIPINO'],
        'MAPEH' => ['MAPEH'],
        'COMPUTER EDUCATION' => ['COMP'],
    ];

    public const GS_SUBJECT_NORM = [
        'ARALING PANLIPUNAN' => ['ARALING PANLIPUNAN'],
        'CHRISTIAN LIVING EDUCATION' => ['CHRISTIAN LIVING EDUCATION'],
        'CHRISTIAN LIVING' => ['CHRISTIAN LIVING EDUCATION'],
        'CLVE' => ['CHRISTIAN LIVING EDUCATION'],
        'EDUKASYON SA PAGPAPAKATAO' => ['EDUKASYON SA PAGPAPAKATAO'],
        'VALUES EDUCATION' => ['VALUES EDUCATION'],
        'TECHNOLOGY AND LIVELIHOOD EDUCATION' => ['TECHNOLOGY AND LIVELIHOOD EDUCATION'],
        'TECHNOLOGY AND LIVELIHOOD' => ['TECHNOLOGY AND LIVELIHOOD EDUCATION'],
        'TLE' => ['TECHNOLOGY AND LIVELIHOOD EDUCATION'],
        'MATHEMATICS' => ['MATHEMATICS'],
        'SCIENCE' => ['SCIENCE'],
        'ENGLISH' => ['ENGLISH'],
        'FILIPINO' => ['FILIPINO'],
        'MAPEH' => ['MAPEH'],
        'COMPUTER EDUCATION' => ['COMPUTER EDUCATION'],
        'COMPUTER' => ['COMPUTER EDUCATION'],
        'ICT' => ['COMPUTER EDUCATION'],
        'MOTHER TONGUE' => ['MOTHER TONGUE'],
        'READING' => ['READING'],
        'GMRC' => ['GMRC'],
        'GMRC / ESP' => ['GMRC'],
    ];

    public const GS_SUBJECT_NORM_FOR_GRADE = [
        'ARALING PANLIPUNAN' => ['ARALING PANLIPUNAN'],
        'CHRISTIAN LIVING EDUCATION' => ['CHRISTIAN LIVING EDUCATION'],
        'CHRISTIAN LIVING' => ['CHRISTIAN LIVING EDUCATION'],
        'CLVE' => ['CHRISTIAN LIVING EDUCATION'],
        'EDUKASYON SA PAGPAPAKATAO' => ['EDUKASYON SA PAGPAPAKATAO'],
        'VALUES EDUCATION' => ['VALUES EDUCATION'],
        'MATHEMATICS' => ['MATHEMATICS'],
        'SCIENCE' => ['SCIENCE'],
        'ENGLISH' => ['ENGLISH'],
        'FILIPINO' => ['FILIPINO'],
        'MAPEH' => ['MAPEH'],
        'GMRC' => ['GMRC'],
        'GMRC / ESP' => ['GMRC'],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function buildJuniorHigh(): array
    {
        return self::build(
            schoolLevel: 'junior_high',
            connection: 'mysql_jh',
            crossConnection: 'mysql_gs',
            subjectNorm: self::JH_SUBJECT_NORM,
            subjectNormForGrade: self::JH_SUBJECT_NORM,
            grades: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
            crossSchoolSuffix: ' (GS)',
            useFullTeacherName: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildGradeSchool(): array
    {
        return self::build(
            schoolLevel: 'grade_school',
            connection: 'mysql_gs',
            crossConnection: 'mysql_jh',
            subjectNorm: self::GS_SUBJECT_NORM,
            subjectNormForGrade: self::GS_SUBJECT_NORM_FOR_GRADE,
            grades: ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
            crossSchoolSuffix: ' (JH)',
            useFullTeacherName: true,
        );
    }

    /**
     * @param  list<string>  $grades
     * @return array<string, mixed>
     */
    public static function build(
        string $schoolLevel,
        string $connection,
        string $crossConnection,
        array $subjectNorm,
        array $subjectNormForGrade,
        array $grades,
        string $crossSchoolSuffix,
        bool $useFullTeacherName,
    ): array {
        config(['database.school_connection' => $connection]);

        $teachers = AdminUserAccountsSupport::scopeFacultyAssignable(User::query(), $schoolLevel)->get();

        $rooms = self::availableRooms($connection);
        $teacherSubjects = self::teacherSubjectsMap($connection);
        $teachersByGrade = self::teachersByGradeMap($connection);
        $teachersByGradeAndSubject = self::teachersByGradeAndSubjectFromSchedules($connection);

        if (self::hasTable($connection, 'faculty_loads')) {
            FacultyLoadSupport::mergeTeachersByGradeAndSubjectFromLoads(
                $teachersByGradeAndSubject,
                $subjectNormForGrade
            );
        }

        $teacherConflicts = self::teacherConflictsMap($connection);
        $sharedIds = SharedTeacherSupport::activeFacultyIds($connection);
        $crossRows = SharedTeacherSupport::crossSchoolConflicts(
            $crossConnection,
            $sharedIds,
            ['faculty_id', 'day_of_week', 'start_time', 'end_time', 'section_name', 'grade_level']
        );
        self::mergeCrossSchoolConflicts($teacherConflicts, $crossRows, $crossSchoolSuffix);

        $sharedTeachers = SharedTeacherSupport::activeList($connection, self::sharedTeacherColumns($connection));
        $teachersBySubject = self::teachersBySubjectMap($connection, $subjectNorm, $sharedTeachers, $subjectNorm);
        self::appendSharedTeachersToGrades($teachersByGrade, $sharedTeachers, $grades);
        $allTeachersForDropdown = self::allTeachersForDropdown(
            $teachers,
            $sharedTeachers,
            $useFullTeacherName
        );

        return compact(
            'rooms',
            'teachers',
            'teacherSubjects',
            'teachersByGrade',
            'teachersByGradeAndSubject',
            'teacherConflicts',
            'sharedTeachers',
            'teachersBySubject',
            'allTeachersForDropdown'
        ) + [
            'scheduleFormRows' => SchoolScheduleSlots::scheduleFormRows($schoolLevel),
            'scheduleFormRowsTuesday' => $schoolLevel === 'junior_high'
                ? SchoolScheduleSlots::scheduleFormRows('junior_high', 'Tuesday')
                : [],
        ];
    }

    public static function hasTable(string $connection, string $table): bool
    {
        return Schema::connection($connection)->hasTable($table);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function availableRooms(string $connection): Collection
    {
        if (! self::hasTable($connection, 'rooms')) {
            return collect();
        }

        return DB::connection($connection)->table('rooms')
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();
    }

    /**
     * @return array<int|string, list<string>>
     */
    public static function teacherSubjectsMap(string $connection): array
    {
        if (! self::hasTable($connection, 'faculty_loads')
            || ! Schema::connection($connection)->hasColumn('faculty_loads', 'subject')) {
            return [];
        }

        $map = [];
        $rows = DB::connection($connection)->table('faculty_loads')
            ->select('faculty_id', 'subject')
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->orderBy('faculty_id')
            ->get();

        foreach ($rows as $row) {
            $subjects = array_values(array_unique(array_filter(array_map(
                'trim',
                explode(',', (string) $row->subject)
            ))));
            if ($subjects !== []) {
                $map[$row->faculty_id] = $subjects;
            }
        }

        return $map;
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function teachersByGradeMap(string $connection): array
    {
        if (! self::hasTable($connection, 'faculty_loads')
            || ! Schema::connection($connection)->hasColumn('faculty_loads', 'grade_level')) {
            return [];
        }

        $map = [];
        $rows = DB::connection($connection)->table('faculty_loads')
            ->select('faculty_id', 'grade_level')
            ->whereNotNull('grade_level')
            ->where('grade_level', '!=', '')
            ->get();

        foreach ($rows as $row) {
            $grade = trim((string) $row->grade_level);
            if ($grade === '') {
                continue;
            }
            $map[$grade][] = $row->faculty_id;
        }

        foreach ($map as $grade => $ids) {
            $map[$grade] = array_values(array_unique($ids));
        }

        return $map;
    }

    /**
     * @return array<string, array<string, list<int|string>>>
     */
    public static function teachersByGradeAndSubjectFromSchedules(string $connection): array
    {
        if (! self::hasTable($connection, 'class_schedules')
            || ! Schema::connection($connection)->hasColumn('class_schedules', 'grade_level')) {
            return [];
        }

        $map = [];
        $query = DB::connection($connection)->table('class_schedules')
            ->select('faculty_id', 'grade_level', 'subject')
            ->whereNotNull('grade_level')
            ->where('grade_level', '!=', '')
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->where('admin_approved', true);

        foreach ($query->get() as $row) {
            $grade = trim((string) $row->grade_level);
            $subj = strtoupper(trim((string) $row->subject));
            if ($grade === '' || $subj === '') {
                continue;
            }
            $map[$grade][$subj][] = $row->faculty_id;
        }

        foreach ($map as $grade => $subjects) {
            foreach ($subjects as $subject => $ids) {
                $map[$grade][$subject] = array_values(array_unique($ids));
            }
        }

        return $map;
    }

    /**
     * @return array<int|string, list<array{day: mixed, start: string, end: string, section: string}>>
     */
    public static function teacherConflictsMap(string $connection): array
    {
        if (! self::hasTable($connection, 'class_schedules')) {
            return [];
        }

        $map = [];
        $rows = DB::connection($connection)->table('class_schedules')
            ->select('faculty_id', 'day_of_week', 'start_time', 'end_time', 'section_name', 'grade_level')
            ->where('admin_approved', true)
            ->whereNotNull('day_of_week')
            ->whereNotNull('start_time')
            ->get();

        foreach ($rows as $row) {
            $fid = (string) $row->faculty_id;
            $map[$fid][] = [
                'day' => $row->day_of_week,
                'start' => substr((string) $row->start_time, 0, 5),
                'end' => substr((string) ($row->end_time ?? ''), 0, 5),
                'section' => trim(((string) ($row->grade_level ?? '')).' '.((string) ($row->section_name ?? ''))),
            ];
        }

        return $map;
    }

    /**
     * @param  array<int|string, list<array{day: mixed, start: string, end: string, section: string}>>  $teacherConflicts
     */
    public static function mergeCrossSchoolConflicts(
        array &$teacherConflicts,
        Collection $rows,
        string $suffix,
    ): void {
        foreach ($rows as $row) {
            $fid = (string) $row->faculty_id;
            if (! isset($teacherConflicts[$fid])) {
                $teacherConflicts[$fid] = [];
            }
            $teacherConflicts[$fid][] = [
                'day' => $row->day_of_week,
                'start' => substr((string) $row->start_time, 0, 5),
                'end' => substr((string) ($row->end_time ?? ''), 0, 5),
                'section' => trim(((string) ($row->grade_level ?? '')).' '.((string) ($row->section_name ?? ''))).$suffix,
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $sharedTeachers
     * @return array<string, list<int|string>>
     */
    public static function teachersBySubjectMap(
        string $connection,
        array $subjectNorm,
        array $sharedTeachers,
        array $sharedSubjectNorm,
    ): array {
        $map = [];

        if (self::hasTable($connection, 'faculty_loads')
            && Schema::connection($connection)->hasColumn('faculty_loads', 'subject')) {
            $loadRows = DB::connection($connection)->table('faculty_loads')
                ->select('faculty_id', 'subject')
                ->whereNotNull('subject')
                ->where('subject', '!=', '')
                ->get();

            foreach ($loadRows as $row) {
                foreach (array_map('trim', explode(',', (string) $row->subject)) as $sub) {
                    if ($sub === '') {
                        continue;
                    }
                    $raw = strtoupper($sub);
                    $targets = $subjectNorm[$raw] ?? [$raw];
                    foreach ($targets as $key) {
                        $map[$key][] = $row->faculty_id;
                    }
                }
            }
        }

        foreach ($sharedTeachers as $st) {
            if (! ($st['faculty_id'] ?? null)) {
                continue;
            }
            $stSubjects = $st['subjects'] ?? null;
            if (is_string($stSubjects)) {
                $stSubjects = json_decode($stSubjects, true);
            }
            if (! is_array($stSubjects)) {
                continue;
            }
            foreach ($stSubjects as $sub) {
                $raw = strtoupper(trim((string) $sub));
                if ($raw === '') {
                    continue;
                }
                $targets = $sharedSubjectNorm[$raw] ?? [$raw];
                foreach ($targets as $key) {
                    $map[$key][] = $st['faculty_id'];
                }
            }
        }

        foreach ($map as $key => $ids) {
            $map[$key] = array_values(array_unique($ids));
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $sharedTeachers
     * @param  list<string>  $grades
     * @param  array<string, list<int|string>>  $teachersByGrade
     */
    public static function appendSharedTeachersToGrades(
        array &$teachersByGrade,
        array $sharedTeachers,
        array $grades,
    ): void {
        foreach ($sharedTeachers as $st) {
            if (! ($st['faculty_id'] ?? null)) {
                continue;
            }
            $stSubjects = $st['subjects'] ?? null;
            if (is_string($stSubjects)) {
                $stSubjects = json_decode($stSubjects, true);
            }
            if (! is_array($stSubjects) || $stSubjects === []) {
                continue;
            }
            foreach ($grades as $grade) {
                $teachersByGrade[$grade][] = $st['faculty_id'];
            }
        }

        foreach ($teachersByGrade as $grade => $ids) {
            $teachersByGrade[$grade] = array_values(array_unique($ids));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $teachers
     * @param  list<array<string, mixed>>  $sharedTeachers
     * @return list<array{id: int|string, name: string}>
     */
    public static function allTeachersForDropdown(
        Collection $teachers,
        array $sharedTeachers,
        bool $useFullName,
    ): array {
        $dropdown = $teachers->map(function (User $t) use ($useFullName) {
            $name = $useFullName
                ? trim(($t->first_name ?? '').' '.($t->last_name ?? '')) ?: ($t->name ?? '')
                : ($t->name ?? trim(($t->first_name ?? '').' '.($t->last_name ?? '')));

            return ['id' => $t->id, 'name' => $name];
        })->values()->all();

        $ids = array_column($dropdown, 'id');
        foreach ($sharedTeachers as $st) {
            if (! ($st['faculty_id'] ?? null)) {
                continue;
            }
            if (! in_array($st['faculty_id'], $ids, false)) {
                $dropdown[] = [
                    'id' => $st['faculty_id'],
                    'name' => ($st['teacher_name'] ?? 'Shared Teacher').' (Shared)',
                ];
                $ids[] = $st['faculty_id'];
            }
        }

        return $dropdown;
    }

    /**
     * @return list<string>
     */
    private static function sharedTeacherColumns(string $connection): array
    {
        $columns = ['id', 'faculty_id', 'teacher_name'];
        if (Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
            $columns[] = 'school_level';
        }
        if (Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
            $columns[] = 'subjects';
        }

        return $columns;
    }
}
