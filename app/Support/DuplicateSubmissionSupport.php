<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central duplicate checks (same pattern as unique email on user create).
 */
class DuplicateSubmissionSupport
{
    public static function normalize(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
    }

    public static function normalizeTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr((string) $time, 0, 5);
    }

    /**
     * Pending teacher adjustment request with the same details.
     */
    public static function pendingTeacherAdjustmentMessage(
        string $connection,
        int $facultyId,
        array $fields,
        ?int $excludeId = null
    ): ?string {
        if (! Schema::connection($connection)->hasTable('teacher_requests')) {
            return null;
        }

        $query = DB::connection($connection)
            ->table('teacher_requests')
            ->where('faculty_id', $facultyId)
            ->where('status', 'pending');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if (! empty($fields['schedule_id'])) {
            if ((clone $query)->where('schedule_id', (int) $fields['schedule_id'])->exists()) {
                return 'You already have a pending request for this class schedule. Please wait for admin review.';
            }
        }

        $match = clone $query;
        $match->where('request_type', $fields['request_type'] ?? 'other');
        self::whereNullableEquals($match, 'subject', $fields['subject'] ?? null);
        self::whereNullableEquals($match, 'grade_level', $fields['grade_level'] ?? null);
        self::whereNullableEquals($match, 'section_name', $fields['section_name'] ?? null);
        self::whereNullableEquals($match, 'day_of_week', $fields['day_of_week'] ?? null);
        self::whereNullableTimeEquals($match, 'preferred_start_time', $fields['preferred_start_time'] ?? null);
        self::whereNullableTimeEquals($match, 'preferred_end_time', $fields['preferred_end_time'] ?? null);

        if ($match->exists()) {
            return 'A pending request with the same details already exists. You cannot submit a duplicate.';
        }

        return null;
    }

    /**
     * Pending shared-teacher schedule request duplicate.
     */
    public static function pendingSharedTeacherRequestMessage(
        string $connection,
        int $facultyId,
        array $fields,
        ?int $excludeId = null
    ): ?string {
        if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
            return null;
        }

        $query = DB::connection($connection)
            ->table('shared_teacher_requests')
            ->where('faculty_id', $facultyId)
            ->where('status', 'pending');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if (! empty($fields['schedule_id']) && Schema::connection($connection)->hasColumn('shared_teacher_requests', 'schedule_id')) {
            if ((clone $query)->where('schedule_id', (int) $fields['schedule_id'])->exists()) {
                return 'You already have a pending request for this schedule. Please wait for admin review.';
            }
        }

        $match = clone $query;
        if (! empty($fields['school_level'])) {
            $match->where('school_level', $fields['school_level']);
        }
        self::whereNullableEquals($match, 'subject', $fields['subject'] ?? null);
        self::whereNullableEquals($match, 'grade_level', $fields['grade_level'] ?? null);
        self::whereNullableEquals($match, 'section_name', $fields['section_name'] ?? null);
        self::whereNullableEquals($match, 'day_of_week', $fields['day_of_week'] ?? null);
        self::whereNullableTimeEquals($match, 'preferred_start_time', $fields['preferred_start_time'] ?? null);
        self::whereNullableTimeEquals($match, 'preferred_end_time', $fields['preferred_end_time'] ?? null);

        if (! empty($fields['description'])) {
            self::whereNullableEquals($match, 'description', $fields['description']);
        }

        if ($match->exists()) {
            return 'A pending schedule request with the same details already exists. You cannot submit a duplicate.';
        }

        return null;
    }

    /**
     * Pending leave overlapping the same date range.
     */
    public static function pendingLeaveRequestMessage(
        string $connection,
        int $teacherId,
        string $dateFrom,
        string $dateTo,
        ?int $excludeId = null
    ): ?string {
        if (! Schema::connection($connection)->hasTable('teacher_leave_requests')) {
            return null;
        }

        $from = $dateFrom;
        $to = $dateTo;

        $query = DB::connection($connection)
            ->table('teacher_leave_requests')
            ->where('teacher_id', $teacherId)
            ->where('status', 'pending')
            ->where(function ($q) use ($from, $to) {
                $q->where('date_from', '<=', $to)
                    ->where('date_to', '>=', $from);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            return 'You already have a pending absence/leave request for overlapping dates. Please wait for admin review.';
        }

        return null;
    }

    /**
     * Faculty load: same teacher (id or name) + grade + subject.
     */
    public static function facultyLoadDuplicateMessage(
        ?int $facultyId,
        ?string $teacherName,
        ?string $gradeLevel,
        ?string $subject,
        ?int $excludeId = null
    ): ?string {
        $grade = trim((string) $gradeLevel);
        $subj = trim((string) $subject);

        $base = FacultyLoad::query();
        if ($excludeId) {
            $base->where('id', '!=', $excludeId);
        }

        if ($facultyId) {
            $byId = (clone $base)
                ->where('faculty_id', $facultyId)
                ->where('grade_level', $grade)
                ->where('subject', $subj)
                ->exists();
            if ($byId) {
                return 'A faculty load for this teacher with the same grade level and subject already exists.';
            }
        }

        $normalizedName = self::normalize($teacherName);
        if ($normalizedName !== '') {
            $byName = (clone $base)
                ->whereRaw('LOWER(TRIM(teacher_name)) = ?', [$normalizedName])
                ->where('grade_level', $grade)
                ->where('subject', $subj)
                ->exists();
            if ($byName) {
                return 'A faculty load for this teacher name with the same grade level and subject already exists.';
            }
        }

        return null;
    }

    /**
     * Class schedule exact duplicate (approved or pending).
     */
    public static function scheduleDuplicateMessage(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        string $sectionName,
        string $subject,
        ?string $gradeLevel = null,
        ?int $excludeScheduleId = null
    ): ?string {
        $duplicate = self::findDuplicateSchedule(
            $facultyId,
            $dayOfWeek,
            $startTime,
            $sectionName,
            $subject,
            $gradeLevel,
            $excludeScheduleId
        );

        if (! $duplicate) {
            return null;
        }

        $statusLabel = ($duplicate->admin_approved ?? false) ? 'approved' : 'pending';

        return "This schedule already exists ({$statusLabel}): same teacher, subject, section, day, and time.";
    }

    public static function findDuplicateSchedule(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        string $sectionName,
        string $subject,
        ?string $gradeLevel = null,
        ?int $excludeScheduleId = null
    ): ?ClassSchedule {
        $start = self::normalizeTime($startTime) ?? $startTime;

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', 'like', $start . '%')
            ->where('section_name', $sectionName)
            ->where('subject', $subject)
            ->whereIn('status', ['pending', 'active']);

        if ($gradeLevel !== null && $gradeLevel !== '') {
            $query->where('grade_level', $gradeLevel);
        }

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->first();
    }

    private static function whereNullableEquals(Builder $query, string $column, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $query->where(function ($q) use ($column) {
                $q->whereNull($column)->orWhere($column, '');
            });

            return;
        }

        $query->where($column, $value);
    }

    private static function whereNullableTimeEquals(Builder $query, string $column, ?string $value): void
    {
        $normalized = self::normalizeTime($value);
        if ($normalized === null) {
            $query->where(function ($q) use ($column) {
                $q->whereNull($column)->orWhere($column, '');
            });

            return;
        }

        $query->where($column, 'like', $normalized . '%');
    }
}
