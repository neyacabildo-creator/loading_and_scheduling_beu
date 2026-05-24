<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\MasterWeeklySchedule;
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
     * Personal loading rows synced from faculty assignments (richest subject/grade data).
     */
    public static function teacherLoadingSchedulesForWorkload(int $facultyId, ?string $connection = null): Collection
    {
        $connection = $connection ?? config('database.school_connection', 'mysql_jh');

        if ($facultyId <= 0 || ! Schema::connection($connection)->hasTable('teacher_loading_schedules')) {
            return collect();
        }

        return DB::connection($connection)
            ->table('teacher_loading_schedules')
            ->where('faculty_id', $facultyId)
            ->orderBy('day_of_week')
            ->orderBy('time_start')
            ->orderBy('subject_name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rowFromTeacherLoadingSchedule(object $row): array
    {
        [$parsedGrade, $parsedSection] = ScheduleDisplaySupport::parseGradeSection($row->grade_level ?? null);
        $gradeLevel = $parsedGrade !== '' ? $parsedGrade : trim((string) ($row->grade_level ?? ''));
        $sectionName = $parsedSection !== '' ? $parsedSection : trim((string) ($row->section ?? ''));

        $status = strtolower(trim((string) ($row->status ?? 'draft')));
        if ($status === 'available') {
            $status = 'approved';
        }

        return [
            'id'               => $row->id ?? null,
            'source'           => 'teacher_loading_schedule',
            'subject'          => trim((string) ($row->subject_name ?? '')),
            'subject_name'     => trim((string) ($row->subject_name ?? '')),
            'subject_code'     => trim((string) ($row->subject_code ?? '')),
            'grade_level'      => $gradeLevel !== '' ? $gradeLevel : null,
            'section_name'     => $sectionName !== '' ? $sectionName : null,
            'day_of_week'      => $row->day_of_week ?? null,
            'start_time'       => $row->time_start ?? null,
            'end_time'         => $row->time_end ?? null,
            'units'            => max(1, (int) round((float) ($row->units ?? 0))),
            'classes_assigned' => max(1, (int) round((float) ($row->units ?? 0))),
            'load_hours'       => (float) ($row->load_hours ?? 0),
            'status'           => $status,
            'school_year'      => $row->school_year ?? null,
        ];
    }

    /**
     * Sync loading rows, then build workload table rows for teacher dashboards.
     *
     * @return list<array<string, mixed>>
     */
    public static function workloadRecordsForTeacher(int $facultyId, ?string $connection = null): array
    {
        $connection = $connection ?? config('database.school_connection', 'mysql_jh');

        if ($facultyId > 0) {
            FacultyLoad::query()
                ->where('faculty_id', $facultyId)
                ->get()
                ->each(fn (FacultyLoad $load) => FacultyLoadSupport::refreshTeacherLoadingScheduleRow($load));
        }

        $facultyLoads = FacultyLoad::where('faculty_id', $facultyId)->get();
        $classSchedules = self::classSchedulesForTeacherWorkload($facultyId, $connection);

        return self::buildWorkloadSchedules($classSchedules, $facultyLoads, $connection, $facultyId);
    }

    public static function masterWeeklySchedulesForWorkload(int $facultyId, ?string $connection = null): Collection
    {
        $connection = $connection ?? config('database.school_connection', 'mysql_jh');

        if ($facultyId <= 0 || ! Schema::connection($connection)->hasTable('master_weekly_schedules')) {
            return collect();
        }

        return MasterWeeklySchedule::on($connection)
            ->where('faculty_id', $facultyId)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('subject_handled')->where('subject_handled', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('grade_level')->where('grade_level', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('grade_section')->where('grade_section', '!=', '');
                });
            })
            ->orderBy('day_of_week')
            ->orderBy('slot_order')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rowFromMasterWeeklySchedule(object $row): array
    {
        $subject = trim((string) ($row->subject_handled ?? ''));
        $gradeLevel = trim((string) ($row->grade_level ?? ''));
        $sectionName = trim((string) ($row->section_name ?? ''));

        if ($gradeLevel === '' && ! empty($row->grade_section)) {
            [$parsedGrade, $parsedSection] = ScheduleDisplaySupport::parseGradeSection($row->grade_section);
            $gradeLevel = $parsedGrade !== '' ? $parsedGrade : trim((string) $row->grade_section);
            $sectionName = $sectionName !== '' ? $sectionName : $parsedSection;
        }

        return [
            'id'               => $row->id ?? null,
            'source'           => 'master_weekly',
            'subject'          => $subject,
            'subject_name'     => $subject,
            'grade_level'      => $gradeLevel !== '' ? $gradeLevel : null,
            'section_name'     => $sectionName !== '' ? $sectionName : null,
            'day_of_week'      => $row->day_of_week ?? null,
            'start_time'       => $row->time_start ?? null,
            'end_time'         => $row->time_end ?? null,
            'units'            => 1,
            'classes_assigned' => 1,
            'load_hours'       => self::scheduleDurationHours($row->time_start ?? null, $row->time_end ?? null),
            'status'           => 'approved',
            'school_year'      => $row->school_year ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function workloadDedupeKey(array $row): string
    {
        $subject = trim((string) ($row['subject'] ?? $row['subject_name'] ?? ''));
        if (strcasecmp($subject, 'unassigned') === 0) {
            $subject = '';
        }

        $gradeSection = trim((string) ($row['grade_section'] ?? ''));
        if ($gradeSection === '' && (! empty($row['grade_level']) || ! empty($row['section_name']))) {
            $gradeSection = self::gradeSectionLabel($row['grade_level'] ?? null, $row['section_name'] ?? null);
        }

        $base = strtolower(implode('|', [
            $subject,
            $gradeSection,
            trim((string) ($row['day_of_week'] ?? '')),
            trim((string) ($row['start_time'] ?? $row['time_start'] ?? '')),
        ]));

        if (trim(str_replace('|', '', $base)) === '') {
            return ($row['source'] ?? 'row') . ':' . (string) ($row['id'] ?? '0');
        }

        return ($row['source'] ?? '') . ':' . (string) ($row['id'] ?? '') . '|' . $base;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    public static function mergeWorkloadRows(array $a, array $b): array
    {
        $pick = function (string $field) use ($a, $b): mixed {
            foreach ([$a[$field] ?? null, $b[$field] ?? null] as $value) {
                if ($value === null || $value === '' || $value === '—') {
                    continue;
                }
                if (is_string($value) && strcasecmp(trim($value), 'unassigned') === 0) {
                    continue;
                }

                return $value;
            }

            return $a[$field] ?? $b[$field] ?? null;
        };

        $merged = $a;
        foreach (['subject', 'subject_name', 'subject_code', 'grade_level', 'section_name', 'grade_section', 'day_of_week', 'start_time', 'end_time', 'school_year', 'status'] as $field) {
            $merged[$field] = $pick($field);
        }

        $merged['units'] = max(
            (int) ($a['units'] ?? 0),
            (int) ($b['units'] ?? 0),
            (int) ($a['classes_assigned'] ?? 0),
            (int) ($b['classes_assigned'] ?? 0)
        );
        if ($merged['units'] <= 0) {
            $merged['units'] = 1;
        }
        $merged['classes_assigned'] = $merged['units'];
        $merged['load_hours'] = max((float) ($a['load_hours'] ?? 0), (float) ($b['load_hours'] ?? 0));
        $merged['id'] = $a['id'] ?? $b['id'] ?? null;
        $merged['source'] = $pick('source');

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function isSparseWorkloadRow(array $row): bool
    {
        $subject = trim((string) ($row['subject'] ?? $row['subject_name'] ?? ''));
        if ($subject !== '' && strcasecmp($subject, 'unassigned') !== 0) {
            return false;
        }

        $grade = trim((string) ($row['grade_level'] ?? ''));
        $section = trim((string) ($row['section_name'] ?? $row['section'] ?? ''));
        $day = trim((string) ($row['day_of_week'] ?? ''));

        return $grade === '' && $section === '' && $day === '';
    }

    /**
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $classSchedules
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $facultyLoads
     */
    public static function buildWorkloadSchedules($classSchedules, $facultyLoads, ?string $connection = null, ?int $facultyId = null): array
    {
        $connection = $connection ?? config('database.school_connection', 'mysql_jh');
        $loads = collect($facultyLoads);
        $allSchedules = collect($classSchedules);
        $facultyId = (int) ($facultyId ?? $loads->first()?->faculty_id ?? $allSchedules->first()?->faculty_id ?? 0);
        $masterWeekly = self::masterWeeklySchedulesForWorkload($facultyId, $connection);
        $rows = [];
        $seen = [];

        $pushRow = function (array $row) use (&$rows, &$seen): void {
            $key = self::workloadDedupeKey($row);
            if (isset($seen[$key])) {
                $rows[$seen[$key]] = self::mergeWorkloadRows($rows[$seen[$key]], $row);

                return;
            }
            $seen[$key] = count($rows);
            $rows[] = $row;
        };

        foreach ($classSchedules as $s) {
            $hours = self::scheduleDurationHours($s->start_time, $s->end_time);
            $load = $loads->first(fn ($l) => self::scheduleMatchesFacultyLoad($s, $l));
            $grade = ScheduleDisplaySupport::resolveGradeAndSection($s);

            $pushRow([
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
            ]);
        }

        foreach ($masterWeekly as $mw) {
            $pushRow(self::rowFromMasterWeeklySchedule($mw));
        }

        foreach ($loads as $load) {
            if (collect($rows)->contains(fn ($row) => self::rowMatchesFacultyLoad($row, $load))) {
                continue;
            }

            $comp = self::complementaryScheduleForLoad($load, $allSchedules);
            $master = self::complementaryMasterWeeklyForLoad($load, $masterWeekly);
            [$parsedGrade, $parsedSection] = ScheduleDisplaySupport::parseGradeSection($load->grade_level);
            $gradeLevel = $parsedGrade !== '' ? $parsedGrade : trim((string) ($load->grade_level ?? ''));
            $sectionName = $parsedSection !== '' ? $parsedSection : ($comp?->section_name ?? $master?->section_name ?? null);
            if ($gradeLevel === '' && $comp) {
                $fromSchedule = ScheduleDisplaySupport::resolveGradeAndSection($comp);
                $gradeLevel = $fromSchedule['grade_level'] ?: $gradeLevel;
                $sectionName = $sectionName ?: $fromSchedule['section_name'];
            }
            if ($gradeLevel === '' && $master) {
                $fromMaster = self::rowFromMasterWeeklySchedule($master);
                $gradeLevel = $fromMaster['grade_level'] ?? $gradeLevel;
                $sectionName = $sectionName ?: ($fromMaster['section_name'] ?? null);
            }

            $hours = (float) ($load->load_hours ?? 0);
            if ($hours <= 0 && $comp) {
                $hours = self::scheduleDurationHours($comp->start_time, $comp->end_time);
            }
            if ($hours <= 0 && $master) {
                $hours = self::scheduleDurationHours($master->time_start ?? null, $master->time_end ?? null);
            }

            $units = (int) ($load->classes_assigned ?? 0);
            if ($units <= 0) {
                $units = 1;
            }

            $subject = trim((string) ($load->subject ?? ''));
            if ($subject === '' && $comp) {
                $subject = trim((string) ($comp->subject ?? ''));
            }
            if ($subject === '' && $master) {
                $subject = trim((string) ($master->subject_handled ?? ''));
            }
            if ($subject === '' && ! empty($load->notes)) {
                $subject = trim((string) $load->notes);
            }
            if ($subject === '' && (int) ($load->faculty_id ?? 0) > 0) {
                $sharedSubjects = SharedTeacherSupport::assignedSubjectsForFaculty($connection, (int) $load->faculty_id);
                if (count($sharedSubjects) === 1) {
                    $subject = $sharedSubjects[0];
                } elseif (count($sharedSubjects) > 1) {
                    $subject = implode(', ', $sharedSubjects);
                }
            }

            $pushRow([
                'id'               => $load->id,
                'source'           => 'faculty_load',
                'subject'          => $subject,
                'subject_name'     => $subject,
                'load_hours'       => $hours,
                'classes_assigned' => $units,
                'units'            => $units,
                'status'           => $load->status ?? 'available',
                'day_of_week'      => $comp?->day_of_week ?? $master?->day_of_week,
                'start_time'       => $comp?->start_time ?? $master?->time_start,
                'end_time'         => $comp?->end_time ?? $master?->time_end,
                'grade_level'      => $gradeLevel !== '' ? $gradeLevel : null,
                'section_name'     => $sectionName,
                'school_year'      => $master?->school_year,
            ]);
        }

        foreach (self::teacherLoadingSchedulesForWorkload($facultyId, $connection) as $tls) {
            $tlsRow = self::rowFromTeacherLoadingSchedule($tls);
            if (self::isSparseWorkloadRow($tlsRow) && $loads->isNotEmpty()) {
                continue;
            }
            $pushRow($tlsRow);
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
        if (($row['source'] ?? '') === 'faculty_load' && (int) ($row['id'] ?? 0) === (int) ($load->id ?? 0)) {
            return true;
        }

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
        $collection = collect($classSchedules);
        $matched = $collection->first(fn ($schedule) => self::scheduleMatchesFacultyLoad($schedule, $load));
        if ($matched) {
            return $matched;
        }

        $loadSubject = trim((string) ($load->subject ?? ''));
        $loadGrade = trim((string) ($load->grade_level ?? ''));
        if ($loadSubject === '' || $loadGrade === '') {
            return $collection->sortBy('start_time')->first();
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection  $masterWeekly
     */
    public static function complementaryMasterWeeklyForLoad($load, $masterWeekly): ?object
    {
        $collection = collect($masterWeekly);
        $loadSubject = trim((string) ($load->subject ?? ''));
        $loadGrade = trim((string) ($load->grade_level ?? ''));

        if ($loadSubject !== '') {
            $bySubject = $collection->first(function ($row) use ($loadSubject) {
                return self::subjectsMatch($row->subject_handled ?? '', $loadSubject);
            });
            if ($bySubject) {
                return $bySubject;
            }
        }

        if ($loadGrade !== '') {
            [$parsedGrade] = ScheduleDisplaySupport::parseGradeSection($loadGrade);
            $byGrade = $collection->first(function ($row) use ($loadGrade, $parsedGrade) {
                $rowGrade = trim((string) ($row->grade_level ?? ''));
                if ($rowGrade !== '' && strcasecmp($rowGrade, $loadGrade) === 0) {
                    return true;
                }

                return $parsedGrade !== '' && strcasecmp($rowGrade, $parsedGrade) === 0;
            });
            if ($byGrade) {
                return $byGrade;
            }
        }

        return $collection->sortBy('slot_order')->first();
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
        if (strcasecmp($subject, 'unassigned') === 0) {
            $subject = '';
        }
        if ($subject === '' && ! empty($row['notes'])) {
            $subject = trim((string) $row['notes']);
        }
        if ($subject === '' && $gradeSection !== '') {
            $subject = $gradeSection;
        }

        $units = (int) ($row['units'] ?? $row['classes_assigned'] ?? 0);
        if ($units <= 0) {
            $units = $subject !== '' || $gradeSection !== '' ? 1 : 0;
        }

        $startTime = $row['start_time'] ?? $row['time_start'] ?? null;
        $endTime = $row['end_time'] ?? $row['time_end'] ?? null;

        $loadHours = (float) ($row['load_hours'] ?? 0);
        if ($loadHours <= 0 && ! empty($startTime) && ! empty($endTime)) {
            $loadHours = self::scheduleDurationHours($startTime, $endTime);
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
            'start_time'       => $startTime,
            'end_time'         => $endTime,
            'school_year'      => $row['school_year'] ?? null,
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
        $defaultYear = date('Y') . '-' . (date('Y') + 1);
        $resolvedYear = trim((string) ($row['school_year'] ?? $normalized['school_year'] ?? ''));
        $schoolYear = $resolvedYear !== '' ? $resolvedYear : ($schoolYear ?? $defaultYear);

        return [
            'id'            => $normalized['id'],
            'subject'       => $normalized['subject'],
            'subject_name'  => $normalized['subject_name'],
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
