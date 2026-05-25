<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\LoadConflictLog;
use App\Models\MasterWeeklySchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FacultyLoadSupport
{
    /** Max distinct subject/grade load rows per shared teacher. */
    public const SHARED_TEACHER_MAX_LOADS = 5;

    public static function formatHoursLabel($hours): string
    {
        if ($hours === null || $hours === '') {
            return '0 hour/s';
        }

        $value = is_numeric($hours) ? round((float) $hours, 2) : 0;
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $formatted . ' hour/s';
    }

    public static function isSharedTeacher(int $facultyId): bool
    {
        if ($facultyId <= 0) {
            return false;
        }

        return User::where('id', $facultyId)
            ->whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))
            ->exists();
    }

    public static function countLoadsForTeacher(int $facultyId, ?int $excludeLoadId = null): int
    {
        if ($facultyId <= 0) {
            return 0;
        }

        $query = FacultyLoad::where('faculty_id', $facultyId);
        if ($excludeLoadId) {
            $query->where('id', '!=', $excludeLoadId);
        }

        return (int) $query->count();
    }

    /**
     * @return string|null Error message when limit exceeded
     */
    public static function sharedTeacherLoadLimitMessage(int $facultyId, ?int $excludeLoadId = null): ?string
    {
        if (! self::isSharedTeacher($facultyId)) {
            return null;
        }

        $count = self::countLoadsForTeacher($facultyId, $excludeLoadId);
        if ($count >= self::SHARED_TEACHER_MAX_LOADS) {
            return 'Shared teachers are limited to ' . self::SHARED_TEACHER_MAX_LOADS
                . ' subject/load assignments. This teacher already has ' . $count . ' load(s).';
        }

        return null;
    }

    public static function assertSharedTeacherLoadLimit(int $facultyId, ?int $excludeLoadId = null): void
    {
        $message = self::sharedTeacherLoadLimitMessage($facultyId, $excludeLoadId);
        if ($message !== null) {
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * After grade/subject change on a load row, keep approved schedules aligned so Create Schedule filters stay correct.
     */
    public static function syncSchedulesAfterLoadChange(
        int $facultyId,
        ?string $oldGrade,
        ?string $oldSubject,
        ?string $newGrade,
        ?string $newSubject
    ): int {
        if ($facultyId <= 0) {
            return 0;
        }

        $oldGrade = trim((string) $oldGrade);
        $oldSubject = trim((string) $oldSubject);
        $newGrade = trim((string) $newGrade);
        $newSubject = trim((string) $newSubject);

        if ($oldGrade === $newGrade && strcasecmp($oldSubject, $newSubject) === 0) {
            return 0;
        }

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true);

        if ($oldGrade !== '') {
            $query->where('grade_level', $oldGrade);
        }

        $schedules = $query->get();
        $updated = 0;

        foreach ($schedules as $schedule) {
            if ($oldSubject !== '' && ! self::subjectMatches($schedule->subject, $oldSubject)) {
                continue;
            }

            $changes = [];
            if ($newGrade !== '' && $schedule->grade_level !== $newGrade) {
                $changes['grade_level'] = $newGrade;
            }
            if ($newSubject !== '' && ! self::subjectMatches($schedule->subject, $newSubject)) {
                $changes['subject'] = $newSubject;
            }

            if ($changes !== []) {
                $schedule->update($changes);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @return list<string>
     */
    public static function parseSubjectCsv(?string $csv): array
    {
        $seen = [];
        $out = [];
        foreach (array_map('trim', explode(',', (string) $csv)) as $part) {
            if ($part === '') {
                continue;
            }
            $key = mb_strtolower($part);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $part;
        }

        return $out;
    }

    public static function normalizeSubjectsCsv(?string $csv): string
    {
        $parts = self::parseSubjectCsv($csv);

        return $parts === [] ? '' : implode(', ', $parts);
    }

    /**
     * After a GS faculty load row changes, align shared-teacher registry and kinder subject text.
     */
    public static function syncSharedTeacherAfterGsLoad(FacultyLoad $load): void
    {
        if ((int) ($load->faculty_id ?? 0) <= 0 || ! self::isSharedTeacher((int) $load->faculty_id)) {
            return;
        }

        $grade = trim((string) ($load->grade_level ?? ''));
        if (\App\Support\KinderScheduleSupport::isKinderGrade($grade)) {
            $normalized = self::normalizeSubjectsCsv(
                $load->subject ?: \App\Support\KinderScheduleSupport::subjectsCsv()
            );
            if ($normalized !== (string) $load->subject) {
                $load->subject = $normalized;
                $load->saveQuietly();
            }
        }

        if (! Schema::connection('mysql_gs')->hasTable('shared_teachers')) {
            return;
        }

        $schoolLevel = \App\Support\KinderScheduleSupport::isKinderGrade($grade)
            ? 'grade_school'
            : 'grade_school';

        DB::connection('mysql_gs')->table('shared_teachers')
            ->where('faculty_id', (int) $load->faculty_id)
            ->update([
                'school_level' => $schoolLevel,
                'updated_at'   => now(),
            ]);
    }

    public static function subjectMatches(?string $scheduleSubject, string $needle): bool
    {
        $needle = strtolower(trim($needle));
        if ($needle === '') {
            return true;
        }

        foreach (array_map('trim', explode(',', (string) $scheduleSubject)) as $part) {
            if ($part === '') {
                continue;
            }
            $partLower = strtolower($part);
            if ($partLower === $needle || str_contains($partLower, $needle) || str_contains($needle, $partLower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge faculty_loads into grade → subject → teacher_ids map (used by schedule create form).
     *
     * @param  array<string, array<string, array<int>>>  $map
     * @param  array<string, array<int, string>>|null  $subjectNorm  full name => [abbrev, ...]
     */
    public static function mergeTeachersByGradeAndSubjectFromLoads(array &$map, ?array $subjectNorm = null): void
    {
        $connection = FacultyLoad::query()->getConnection()->getName();

        if (! Schema::connection($connection)->hasTable('faculty_loads')
            || ! Schema::connection($connection)->hasColumn('faculty_loads', 'grade_level')
            || ! Schema::connection($connection)->hasColumn('faculty_loads', 'subject')) {
            return;
        }

        $schoolLevel = self::schoolLevelForConnection($connection);

        FacultyLoad::select('faculty_id', 'grade_level', 'subject')
            ->whereNotNull('grade_level')->where('grade_level', '!=', '')
            ->whereNotNull('subject')->where('subject', '!=', '')
            ->get()
            ->each(function ($load) use (&$map, $subjectNorm, $schoolLevel) {
                if (! self::facultyIdHasRegisteredAccount((int) $load->faculty_id, $schoolLevel)) {
                    return;
                }

                $grade = trim((string) $load->grade_level);
                if ($grade === '') {
                    return;
                }

                foreach (array_map('trim', explode(',', (string) $load->subject)) as $sub) {
                    if ($sub === '') {
                        continue;
                    }
                    $keys = [strtoupper($sub)];
                    if ($subjectNorm) {
                        $raw = strtoupper($sub);
                        $keys = array_merge($keys, $subjectNorm[$raw] ?? [$raw]);
                    }
                    foreach (array_unique($keys) as $key) {
                        if ($key === '') {
                            continue;
                        }
                        $map[$grade][$key][] = $load->faculty_id;
                    }
                }
            });

        foreach ($map as $g => $subjects) {
            foreach ($subjects as $s => $ids) {
                $map[$g][$s] = array_values(array_unique($ids));
            }
        }
    }

    /**
     * Flag shared-teacher overload when load row count exceeds limit; log conflict once.
     */
    public static function applySharedTeacherLoadConflict(int $facultyId, ?int $relatedLoadId = null): bool
    {
        if (! self::isSharedTeacher($facultyId)) {
            return false;
        }

        $count = self::countLoadsForTeacher($facultyId);
        if ($count <= self::SHARED_TEACHER_MAX_LOADS) {
            return false;
        }

        FacultyLoad::where('faculty_id', $facultyId)->update(['status' => 'overloaded']);

        if (Schema::hasTable('load_conflict_logs')) {
            $exists = LoadConflictLog::where('faculty_id', $facultyId)
                ->where('conflict_type', 'shared_teacher_load_limit')
                ->where('status', 'open')
                ->exists();

            if (! $exists) {
                LoadConflictLog::create([
                    'faculty_id'      => $facultyId,
                    'conflict_type'   => 'shared_teacher_load_limit',
                    'description'     => "Shared teacher has {$count} load assignments (limit is " . self::SHARED_TEACHER_MAX_LOADS . ').',
                    'severity'        => 'critical',
                    'related_load_id' => $relatedLoadId,
                    'detected_at'     => now(),
                    'status'          => 'open',
                ]);
            }
        }

        return true;
    }

    /**
     * Remove stale teacher_loading_schedules row when subject name changes.
     */
    public static function connectionForSchoolLevel(string $schoolLevel): string
    {
        return $schoolLevel === 'grade_school' ? 'mysql_gs' : 'mysql_jh';
    }

    public static function schoolLevelForConnection(?string $connection = null): string
    {
        $connection = $connection ?: config('database.school_connection', 'mysql_jh');

        return $connection === 'mysql_gs' ? 'grade_school' : 'junior_high';
    }

    /**
     * Empty row auto-created before registration was decoupled from faculty loads.
     *
     * @param  array<string, mixed>  $row
     */
    public static function isAutoProvisionedPlaceholder(array $row): bool
    {
        $subject = trim((string) ($row['subject'] ?? ''));
        $grade = trim((string) ($row['grade_level'] ?? ''));
        $notes = strtolower((string) ($row['notes'] ?? ''));

        return $subject === '' && $grade === '' && str_contains($notes, 'auto-created');
    }

    /**
     * Teacher account exists in User Accounts for this school (may be inactive).
     */
    public static function facultyIdHasRegisteredAccount(int $facultyId, string $schoolLevel): bool
    {
        if ($facultyId <= 0) {
            return false;
        }

        return User::where('id', $facultyId)
            ->where(function ($q) use ($schoolLevel) {
                $q->where('school_level', $schoolLevel)
                    ->orWhereHas('role', fn ($r) => $r->where('name', 'shared_teacher'));
            })
            ->whereHas('role', fn ($q) => $q->whereIn('name', AdminUserAccountsSupport::FACULTY_ASSIGNABLE_ROLE_NAMES))
            ->exists();
    }

    public static function assertFacultyLoadAccount(int $facultyId, string $schoolLevel): void
    {
        if (! self::facultyIdHasRegisteredAccount($facultyId, $schoolLevel)) {
            throw new \InvalidArgumentException(
                'Selected teacher must have a user account in User Accounts before assigning faculty load.'
            );
        }
    }

    /**
     * Faculty load assignment rules: regular teachers get one load row; shared teachers may span grades.
     *
     * @return string|null Error message when the assignment conflicts
     */
    public static function facultyLoadConflictMessage(
        ?int $facultyId,
        ?string $teacherName,
        ?string $gradeLevel,
        ?string $subject,
        ?int $excludeLoadId = null
    ): ?string {
        if (! $facultyId || $facultyId <= 0) {
            return null;
        }

        if (! self::isSharedTeacher($facultyId)) {
            $existing = FacultyLoad::query()
                ->where('faculty_id', $facultyId);
            if ($excludeLoadId) {
                $existing->where('id', '!=', $excludeLoadId);
            }

            if ($existing->exists()) {
                return 'This teacher already has a faculty load assignment. Remove the existing load first, or register them as a shared teacher to assign multiple grade levels.';
            }
        } else {
            $newGrade = trim((string) $gradeLevel);
            if ($newGrade !== '') {
                $others = FacultyLoad::query()
                    ->where('faculty_id', $facultyId);
                if ($excludeLoadId) {
                    $others->where('id', '!=', $excludeLoadId);
                }
                foreach ($others->get() as $load) {
                    $existingGrade = trim((string) ($load->grade_level ?? ''));
                    if ($existingGrade !== '' && strcasecmp($existingGrade, $newGrade) === 0) {
                        $dup = DuplicateSubmissionSupport::facultyLoadDuplicateMessage(
                            $facultyId,
                            $teacherName,
                            $gradeLevel,
                            $subject,
                            $excludeLoadId
                        );
                        if ($dup !== null) {
                            return $dup;
                        }
                    }
                }
            }
        }

        return DuplicateSubmissionSupport::facultyLoadDuplicateMessage(
            $facultyId,
            $teacherName,
            $gradeLevel,
            $subject,
            $excludeLoadId
        );
    }

    /**
     * Flexible grade match (e.g. "Grade 3" vs stored schedule grade variants).
     */
    public static function gradeLevelsMatch(?string $loadGrade, ?string $scheduleGrade): bool
    {
        $loadGrade = trim((string) $loadGrade);
        $scheduleGrade = trim((string) $scheduleGrade);

        if ($loadGrade === '') {
            return true;
        }

        if ($scheduleGrade === '') {
            return false;
        }

        if (strcasecmp($loadGrade, $scheduleGrade) === 0) {
            return true;
        }

        [$parsedLoad] = ScheduleDisplaySupport::parseGradeSection($loadGrade);
        [$parsedSchedule] = ScheduleDisplaySupport::parseGradeSection($scheduleGrade);

        if ($parsedLoad !== '' && $parsedSchedule !== '' && strcasecmp($parsedLoad, $parsedSchedule) === 0) {
            return true;
        }

        $normLoad = TeacherPortalSupport::normalizeGradeKey($loadGrade);
        $normSchedule = TeacherPortalSupport::normalizeGradeKey($scheduleGrade);

        return $normLoad !== '' && $normLoad === $normSchedule;
    }

    /**
     * Whether a class schedule belongs to this faculty load row (grade + subject scope).
     */
    public static function scheduleBelongsToFacultyLoad(ClassSchedule $schedule, FacultyLoad $load): bool
    {
        if (! self::gradeLevelsMatch($load->grade_level, $schedule->grade_level)) {
            return false;
        }

        $loadSubject = trim((string) $load->subject);
        if ($loadSubject === '') {
            return true;
        }

        foreach (array_map('trim', explode(',', $loadSubject)) as $part) {
            if ($part !== '' && self::subjectMatches($schedule->subject, $part)) {
                return true;
            }
        }

        return self::subjectMatches($schedule->subject, $loadSubject);
    }

    /**
     * Collect class schedules (pending + approved) tied to this faculty load / teacher name.
     */
    public static function schedulesForFacultyLoad(FacultyLoad $load, string $conn): \Illuminate\Support\Collection
    {
        $facultyId = (int) $load->faculty_id;
        if ($facultyId <= 0 || ! Schema::connection($conn)->hasTable('class_schedules')) {
            return collect();
        }

        $schedules = ClassSchedule::on($conn)->where('faculty_id', $facultyId)->get();
        $loadSubject = trim((string) $load->subject);
        $loadGrade = trim((string) $load->grade_level);

        return $schedules->filter(function (ClassSchedule $schedule) use ($load, $loadSubject, $loadGrade) {
            if ($loadGrade !== '' && ! self::gradeLevelsMatch($loadGrade, $schedule->grade_level)) {
                return false;
            }

            if ($loadSubject === '') {
                return true;
            }

            return self::scheduleBelongsToFacultyLoad($schedule, $load);
        })->values();
    }

    /**
     * Delete pending/approved class schedules, weekly timetable, and related rows for one faculty load.
     *
     * @return array{schedules: int, weekly: int, loading_rows: int, pending: int}
     */
    public static function cascadeDeleteForFacultyLoad(FacultyLoad $load): array
    {
        $facultyId = (int) $load->faculty_id;
        $counts = ['schedules' => 0, 'weekly' => 0, 'loading_rows' => 0, 'pending' => 0];

        if ($facultyId <= 0) {
            return $counts;
        }

        $conn = $load->getConnectionName() ?: config('database.school_connection', 'mysql_jh');

        DB::connection($conn)->transaction(function () use ($load, $conn, $facultyId, &$counts) {
            $hasOtherLoads = FacultyLoad::on($conn)
                ->where('faculty_id', $facultyId)
                ->where('id', '!=', $load->id)
                ->exists();

            if (! $hasOtherLoads) {
                self::purgeSchedulesAndRelatedForTeacher($facultyId, $conn, $counts);

                return;
            }

            $schedules = self::schedulesForFacultyLoad($load, $conn);
            $scheduleIds = $schedules->pluck('id')->filter()->values()->all();

            foreach ($schedules as $schedule) {
                self::deleteScheduleAndRelated($schedule, false);
                $counts['schedules']++;
            }

            $counts['pending'] += self::purgePendingSchedulesForFacultyLoad($load, $conn, $scheduleIds);
            self::purgeScheduleApprovalsAndRejectedForFacultyLoad($load, $conn, $scheduleIds);
            self::purgeRejectedSchedulesForFacultyLoadScope($load, $conn);

            $counts['weekly'] += self::purgeMasterWeeklyForFacultyLoad($load);
            $counts['loading_rows'] += self::purgeTeacherLoadingSchedulesForLoad($load);

            if (Schema::connection($conn)->hasTable('load_conflict_logs')) {
                DB::connection($conn)->table('load_conflict_logs')
                    ->where(function ($q) use ($load, $facultyId) {
                        $q->where('related_load_id', $load->id)
                            ->orWhere('faculty_id', $facultyId);
                    })
                    ->delete();
            }
        });

        return $counts;
    }

    /**
     * Remove all pending/approved schedules and related rows for a teacher (last faculty load removed).
     *
     * @param  array{schedules: int, weekly: int, loading_rows: int, pending: int}  $counts
     */
    private static function purgeSchedulesAndRelatedForTeacher(int $facultyId, string $conn, array &$counts): void
    {
        if (Schema::connection($conn)->hasTable('class_schedules')) {
            $schedules = ClassSchedule::on($conn)->where('faculty_id', $facultyId)->get();
            foreach ($schedules as $schedule) {
                self::deleteScheduleAndRelated($schedule, false);
                $counts['schedules']++;
            }
        }

        if (Schema::connection($conn)->hasTable('pending_schedules')) {
            $counts['pending'] += (int) DB::connection($conn)->table('pending_schedules')
                ->where('faculty_id', $facultyId)
                ->delete();
        }

        if (Schema::connection($conn)->hasTable('rejected_schedules')) {
            $rejected = DB::connection($conn)->table('rejected_schedules');
            if (Schema::connection($conn)->hasColumn('rejected_schedules', 'faculty_id')) {
                $rejected->where('faculty_id', $facultyId)->delete();
            }
        }

        $counts['weekly'] += self::purgeAllMasterWeeklyForTeacher($facultyId, $conn);

        if (Schema::connection($conn)->hasTable('teacher_loading_schedules')) {
            $counts['loading_rows'] += (int) DB::connection($conn)->table('teacher_loading_schedules')
                ->where('faculty_id', $facultyId)
                ->delete();
        }

        if (Schema::connection($conn)->hasTable('load_conflict_logs')) {
            DB::connection($conn)->table('load_conflict_logs')
                ->where('faculty_id', $facultyId)
                ->delete();
        }
    }

    private static function purgeRejectedSchedulesForFacultyLoadScope(FacultyLoad $load, string $conn): void
    {
        if (! Schema::connection($conn)->hasTable('rejected_schedules')) {
            return;
        }

        $facultyId = (int) $load->faculty_id;
        $grade = trim((string) $load->grade_level);
        $query = DB::connection($conn)->table('rejected_schedules')->where('faculty_id', $facultyId);

        if ($grade === '') {
            $query->delete();

            return;
        }

        foreach ($query->get() as $row) {
            if (! self::gradeLevelsMatch($grade, $row->grade_level ?? null)) {
                continue;
            }

            $loadSubject = trim((string) $load->subject);
            if ($loadSubject !== '' && ! self::subjectMatches($row->subject ?? '', $loadSubject)) {
                $matched = false;
                foreach (array_map('trim', explode(',', $loadSubject)) as $part) {
                    if ($part !== '' && self::subjectMatches($row->subject ?? '', $part)) {
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    continue;
                }
            }

            DB::connection($conn)->table('rejected_schedules')->where('id', $row->id)->delete();
        }
    }

    public static function deleteScheduleAndRelated(ClassSchedule $schedule, bool $removeWeeklyPerSlot = true): void
    {
        $conn = $schedule->getConnectionName();
        $scheduleId = $schedule->id;

        if ($removeWeeklyPerSlot) {
            self::removeMasterWeeklyRowsForSchedule($schedule);
        }

        if (Schema::connection($conn)->hasTable('pending_schedules')) {
            DB::connection($conn)->table('pending_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();
        }

        try {
            if (Schema::connection($conn)->hasTable('schedule_approvals')) {
                DB::connection($conn)->table('schedule_approvals')
                    ->where('schedule_id', $scheduleId)
                    ->delete();
            }
        } catch (\Exception) {
        }

        if (Schema::connection($conn)->hasTable('rejected_schedules')) {
            DB::connection($conn)->table('rejected_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();
        }

        $schedule->delete();
    }

    public static function removeMasterWeeklyRowsForSchedule(ClassSchedule $schedule): void
    {
        $conn = $schedule->getConnectionName();
        if (! Schema::connection($conn)->hasTable('master_weekly_schedules')) {
            return;
        }

        $query = MasterWeeklySchedule::on($conn)->where('faculty_id', $schedule->faculty_id);

        if ($schedule->day_of_week) {
            $query->where('day_of_week', $schedule->day_of_week);
        }
        if ($schedule->start_time) {
            $query->where('time_start', substr((string) $schedule->start_time, 0, 5));
        }
        if ($schedule->end_time) {
            $query->where('time_end', substr((string) $schedule->end_time, 0, 5));
        }
        if ($schedule->grade_level) {
            $query->where('grade_level', $schedule->grade_level);
        }
        if ($schedule->section_name) {
            $query->where('section_name', $schedule->section_name);
        }

        $query->delete();
    }

    /**
     * Remove the entire weekly timetable grid for a teacher (all days/slots).
     */
    private static function purgeAllMasterWeeklyForTeacher(int $facultyId, string $conn): int
    {
        if (! Schema::connection($conn)->hasTable('master_weekly_schedules')) {
            return 0;
        }

        return (int) MasterWeeklySchedule::on($conn)
            ->where('faculty_id', $facultyId)
            ->delete();
    }

    /**
     * Remove all weekly timetable rows for this teacher at the load's grade level.
     */
    private static function purgeMasterWeeklyForFacultyLoad(FacultyLoad $load): int
    {
        $conn = $load->getConnectionName() ?: config('database.school_connection', 'mysql_jh');
        if (! Schema::connection($conn)->hasTable('master_weekly_schedules')) {
            return 0;
        }

        $facultyId = (int) $load->faculty_id;
        $grade = trim((string) $load->grade_level);
        $deleted = 0;

        foreach (MasterWeeklySchedule::on($conn)->where('faculty_id', $facultyId)->get() as $row) {
            if ($grade !== '' && ! self::gradeLevelsMatch($grade, $row->grade_level)) {
                continue;
            }
            $row->delete();
            $deleted++;
        }

        return $deleted;
    }

    private static function purgePendingSchedulesForFacultyLoad(
        FacultyLoad $load,
        string $conn,
        array $deletedScheduleIds
    ): int {
        if (! Schema::connection($conn)->hasTable('pending_schedules')) {
            return 0;
        }

        $facultyId = (int) $load->faculty_id;
        $grade = trim((string) $load->grade_level);
        $deleted = 0;

        if (! empty($deletedScheduleIds)) {
            $deleted += (int) DB::connection($conn)->table('pending_schedules')
                ->whereIn('schedule_id', $deletedScheduleIds)
                ->delete();
        }

        $loadSubject = trim((string) $load->subject);

        $pendingRows = DB::connection($conn)->table('pending_schedules')
            ->where('faculty_id', $facultyId)
            ->get();

        if ($loadSubject === '' && $grade === '') {
            return $deleted + (int) DB::connection($conn)->table('pending_schedules')
                ->where('faculty_id', $facultyId)
                ->delete();
        }

        foreach ($pendingRows as $row) {
            if ($grade !== '' && ! self::gradeLevelsMatch($grade, $row->grade_level ?? null)) {
                continue;
            }

            $matched = $loadSubject === '' || self::subjectMatches($row->subject ?? '', $loadSubject);
            if (! $matched) {
                foreach (array_map('trim', explode(',', $loadSubject)) as $part) {
                    if ($part !== '' && self::subjectMatches($row->subject ?? '', $part)) {
                        $matched = true;
                        break;
                    }
                }
            }
            if (! $matched) {
                continue;
            }

            $deleted += (int) DB::connection($conn)->table('pending_schedules')
                ->where('id', $row->id)
                ->delete();
        }

        return $deleted;
    }

    private static function purgeScheduleApprovalsAndRejectedForFacultyLoad(
        FacultyLoad $load,
        string $conn,
        array $scheduleIds
    ): void {
        if (empty($scheduleIds)) {
            return;
        }

        try {
            if (Schema::connection($conn)->hasTable('schedule_approvals')) {
                DB::connection($conn)->table('schedule_approvals')
                    ->whereIn('schedule_id', $scheduleIds)
                    ->delete();
            }
        } catch (\Exception) {
        }

        if (Schema::connection($conn)->hasTable('rejected_schedules')) {
            DB::connection($conn)->table('rejected_schedules')
                ->whereIn('schedule_id', $scheduleIds)
                ->delete();
        }
    }

    private static function purgeTeacherLoadingSchedulesForLoad(FacultyLoad $load): int
    {
        $conn = $load->getConnectionName() ?: config('database.school_connection', 'mysql_jh');
        if (! Schema::connection($conn)->hasTable('teacher_loading_schedules')) {
            return 0;
        }

        $facultyId = (int) $load->faculty_id;
        $loadSubject = trim((string) $load->subject);

        if ($loadSubject === '') {
            return (int) DB::connection($conn)->table('teacher_loading_schedules')
                ->where('faculty_id', $facultyId)
                ->delete();
        }

        $deleted = 0;
        foreach (array_map('trim', explode(',', $loadSubject)) as $part) {
            if ($part === '') {
                continue;
            }
            $deleted += (int) DB::connection($conn)->table('teacher_loading_schedules')
                ->where('faculty_id', $facultyId)
                ->where(function ($q) use ($part) {
                    $q->where('subject_name', $part)
                        ->orWhere('subject_name', 'like', $part . '%')
                        ->orWhere('subject_name', 'like', '%' . $part . '%');
                })
                ->delete();
        }

        return $deleted;
    }

    /**
     * Remove all schedules, loads, weekly timetable, and related rows when a user account is deleted.
     */
    public static function purgeAllDataForFaculty(int $facultyId, string $connection): void
    {
        if ($facultyId <= 0 || ! in_array($connection, ['mysql_jh', 'mysql_gs'], true)) {
            return;
        }

        DB::connection($connection)->transaction(function () use ($facultyId, $connection) {
            if (Schema::connection($connection)->hasTable('class_schedules')) {
                $schedules = ClassSchedule::on($connection)->where('faculty_id', $facultyId)->get();
                foreach ($schedules as $schedule) {
                    self::deleteScheduleAndRelated($schedule, true);
                }
            }

            if (Schema::connection($connection)->hasTable('pending_schedules')) {
                DB::connection($connection)->table('pending_schedules')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('rejected_schedules')) {
                $query = DB::connection($connection)->table('rejected_schedules');
                if (Schema::connection($connection)->hasColumn('rejected_schedules', 'faculty_id')) {
                    $query->where('faculty_id', $facultyId)->delete();
                }
            }

            if (Schema::connection($connection)->hasTable('faculty_loads')) {
                DB::connection($connection)->table('faculty_loads')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('master_weekly_schedules')) {
                MasterWeeklySchedule::on($connection)
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('teacher_loading_schedules')) {
                DB::connection($connection)->table('teacher_loading_schedules')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('load_conflict_log')) {
                DB::connection($connection)->table('load_conflict_log')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }

            if (Schema::connection($connection)->hasTable('load_conflict_logs')) {
                DB::connection($connection)->table('load_conflict_logs')
                    ->where('faculty_id', $facultyId)
                    ->delete();
            }
        });
    }

    public static function refreshTeacherLoadingScheduleRow(FacultyLoad $load, ?string $oldSubject = null): void
    {
        $conn = $load->getConnectionName() ?: config('database.school_connection', 'mysql_jh');
        if (! Schema::connection($conn)->hasTable('teacher_loading_schedules')) {
            return;
        }

        $oldName = trim((string) $oldSubject) ?: null;
        $newName = trim((string) $load->subject) ?: null;

        if ($oldName && strcasecmp($oldName, $newName) !== 0) {
            DB::connection($conn)->table('teacher_loading_schedules')
                ->where('faculty_id', $load->faculty_id)
                ->where('subject_name', $oldName)
                ->delete();
        }

        [$parsedGrade, $parsedSection] = \App\Support\ScheduleDisplaySupport::parseGradeSection($load->grade_level);
        $gradeLevel = $parsedGrade !== '' ? $parsedGrade : trim((string) ($load->grade_level ?? ''));
        $section = $parsedSection !== '' ? $parsedSection : null;

        $dayOfWeek = null;
        $timeStart = null;
        $timeEnd = null;
        if (\App\Support\TeacherPortalSupport::hasClassSchedulesTable($conn)) {
            $schedules = \App\Models\ClassSchedule::on($conn)
                ->where('faculty_id', $load->faculty_id)
                ->whereNotIn('status', ['rejected', 'deleted'])
                ->orderBy('start_time')
                ->get();
            $match = $schedules->first(fn ($s) => \App\Support\TeacherPortalSupport::scheduleMatchesFacultyLoad($s, $load))
                ?? (trim((string) $load->subject) === '' || trim((string) $load->grade_level) === ''
                    ? $schedules->first()
                    : null);
            if ($match) {
                $resolved = \App\Support\ScheduleDisplaySupport::resolveGradeAndSection($match);
                $gradeLevel = $gradeLevel !== '' ? $gradeLevel : ($resolved['grade_level'] ?: '');
                $section = $section ?: ($resolved['section_name'] ?: null);
                $dayOfWeek = $match->day_of_week;
                $timeStart = $match->start_time;
                $timeEnd = $match->end_time;
                $scheduleSubject = trim((string) ($match->subject ?? ''));
                if ($newName === null && $scheduleSubject !== '') {
                    $newName = $scheduleSubject;
                }
            }
        }

        if ($newName === null || $newName === '') {
            $newName = 'Unassigned';
        }

        DB::connection($conn)->table('teacher_loading_schedules')->updateOrInsert(
            ['faculty_id' => $load->faculty_id, 'subject_name' => $newName],
            [
                'faculty_id'   => $load->faculty_id,
                'school_year'  => date('Y') . '-' . (date('Y') + 1),
                'semester'     => '1st',
                'subject_name' => $newName,
                'grade_level'  => $gradeLevel !== '' ? $gradeLevel : null,
                'section'      => $section,
                'day_of_week'  => $dayOfWeek,
                'time_start'   => $timeStart,
                'time_end'     => $timeEnd,
                'load_hours'   => $load->load_hours,
                'units'        => $load->classes_assigned,
                'status'       => $load->status === 'available' ? 'approved' : 'draft',
                'remarks'      => $load->notes,
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );
    }
}
