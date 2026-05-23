<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Support\FacultyLoadSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherAdjustmentRequestSupport
{
    public const TABLE = 'teacher_requests';

    public static function adminConnectionForSchool(string $schoolLevel): string
    {
        return TeacherDatabaseSupport::adminConnectionForSchool($schoolLevel);
    }

    public static function connectionForSchool(string $schoolLevel): string
    {
        return TeacherDatabaseSupport::adminConnectionForSchool($schoolLevel);
    }

    public static function ensureTable(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable(self::TABLE)) {
            throw new \RuntimeException(
                'Teacher requests table is missing. Run database migrations for the admin database (' . $connection . ').'
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildProposedPayload(Request $request, ?string $freeText): ?array
    {
        $payload = array_filter([
            'subject'                 => $request->input('subject'),
            'grade_level'             => $request->input('grade_level'),
            'section_name'            => $request->input('section_name'),
            'day_of_week'             => $request->input('day_of_week'),
            'preferred_start_time'    => $request->input('preferred_start_time'),
            'preferred_end_time'      => $request->input('preferred_end_time'),
            'substitute_faculty_id'   => $request->input('substitute_faculty_id'),
            'substitute_teacher_name' => $request->input('substitute_teacher_name'),
            'detail'                  => $freeText,
        ], fn ($v) => $v !== null && $v !== '');

        return $payload !== [] ? $payload : null;
    }

    public static function listForTeacher(string $connection, int $teacherId): Collection
    {
        self::ensureTable($connection);

        return DB::connection($connection)
            ->table(self::TABLE)
            ->where('faculty_id', $teacherId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                $r->requested_by = $r->faculty_id;

                return $r;
            });
    }

    /**
     * Approved class schedules for adjustment form (subject dropdown + slot lookup).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function approvedSchedulesForTeacher(string $connection, int $facultyId): array
    {
        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            return [];
        }

        $rows = DB::connection($connection)
            ->table('class_schedules')
            ->where('faculty_id', $facultyId)
            ->where(function ($q) {
                $q->where('admin_approved', true)->orWhere('status', 'active');
            })
            ->orderBy('grade_level')
            ->orderBy('subject')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'            => $row->id,
                'subject'       => $row->subject,
                'grade_level'   => $row->grade_level,
                'section_name'  => $row->section_name,
                'day_of_week'   => $row->day_of_week,
                'start_time'    => self::formatTimeForInput($row->start_time),
                'end_time'      => self::formatTimeForInput($row->end_time),
                'schedule_date' => $row->schedule_date ? substr((string) $row->schedule_date, 0, 10) : null,
            ];
        })->values()->all();
    }

    /**
     * @return array{success: bool, id?: int, message: string}
     */
    /**
     * Teachers and shared teachers who can cover a subject (and grade) for reassignment.
     *
     * @return array<int, array{id: int, name: string, kind: string}>
     */
    public static function availableTeachersForReassignment(
        string $connection,
        int $excludeFacultyId,
        string $subject,
        ?string $gradeLevel = null
    ): array {
        $subjectNorm = strtolower(trim($subject));
        if ($subjectNorm === '') {
            return [];
        }

        $facultyIds = [];

        if (Schema::connection($connection)->hasTable('faculty_loads')) {
            $loads = DB::connection($connection)->table('faculty_loads')
                ->whereNotNull('faculty_id')
                ->where('faculty_id', '!=', $excludeFacultyId)
                ->get();
            foreach ($loads as $load) {
                if (! self::subjectMatches($load->subject ?? '', $subjectNorm)) {
                    continue;
                }
                if ($gradeLevel && ! self::gradeMatches($load->grade_level ?? '', $gradeLevel)) {
                    continue;
                }
                $facultyIds[] = (int) $load->faculty_id;
            }
        }

        if (Schema::connection($connection)->hasTable('class_schedules')) {
            $schedules = DB::connection($connection)->table('class_schedules')
                ->whereNotNull('faculty_id')
                ->where('faculty_id', '!=', $excludeFacultyId)
                ->where(function ($q) {
                    $q->where('admin_approved', true)->orWhereIn('status', ['active', 'approved']);
                })
                ->get();
            foreach ($schedules as $row) {
                if (! self::subjectMatches($row->subject ?? '', $subjectNorm)) {
                    continue;
                }
                if ($gradeLevel && ! self::gradeMatches($row->grade_level ?? '', $gradeLevel)) {
                    continue;
                }
                $facultyIds[] = (int) $row->faculty_id;
            }
        }

        if (Schema::connection($connection)->hasTable('shared_teachers')) {
            $shared = DB::connection($connection)->table('shared_teachers')
                ->where('is_active', true)
                ->whereNotNull('faculty_id')
                ->where('faculty_id', '!=', $excludeFacultyId)
                ->get();
            foreach ($shared as $st) {
                $subjects = $st->subjects ?? null;
                if (is_string($subjects)) {
                    $subjects = json_decode($subjects, true);
                }
                if (! is_array($subjects)) {
                    continue;
                }
                foreach ($subjects as $sub) {
                    if (self::subjectMatches((string) $sub, $subjectNorm)) {
                        $facultyIds[] = (int) $st->faculty_id;
                        break;
                    }
                }
            }
        }

        $facultyIds = array_values(array_unique(array_filter($facultyIds)));

        $results = [];
        foreach ($facultyIds as $fid) {
            $user = User::find($fid);
            $name = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher')
                : null;

            if (! $name && Schema::connection($connection)->hasTable('shared_teachers')) {
                $st = DB::connection($connection)->table('shared_teachers')->where('faculty_id', $fid)->first();
                $name = $st->teacher_name ?? null;
            }

            if (! $name) {
                continue;
            }

            $isShared = FacultyLoadSupport::isSharedTeacher($fid);
            $results[] = [
                'id'   => $fid,
                'name' => $name,
                'kind' => $isShared ? 'shared_teacher' : 'teacher',
            ];
        }

        usort($results, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $results;
    }

    private static function subjectMatches(string $candidate, string $subjectNorm): bool
    {
        return strtolower(trim($candidate)) === $subjectNorm
            || str_contains(strtolower(trim($candidate)), $subjectNorm)
            || str_contains($subjectNorm, strtolower(trim($candidate)));
    }

    private static function gradeMatches(string $candidate, string $gradeLevel): bool
    {
        $a = preg_match('/\d+/', $candidate, $ma) ? $ma[0] : strtolower(trim($candidate));
        $b = preg_match('/\d+/', $gradeLevel, $mb) ? $mb[0] : strtolower(trim($gradeLevel));

        return $a === $b
            || str_contains(strtolower(trim($candidate)), strtolower(trim($gradeLevel)))
            || str_contains(strtolower(trim($gradeLevel)), strtolower(trim($candidate)));
    }

    public static function store(Request $request, string $connection): array
    {
        self::ensureTable($connection);

        $requestType = $request->input('request_type');
        if ($requestType === 'time_change') {
            $request->merge(['request_type' => 'schedule_change']);
        }

        $validated = $request->validate([
            'schedule_id'             => 'nullable|integer',
            'request_type'            => 'required|in:schedule_change,time_change,room_change,teacher_reassignment,section_change,other',
            'reason'                  => 'required|string|min:3|max:1000',
            'proposed_changes'        => 'nullable|string|max:1000',
            'subject'                 => 'nullable|string|max:120',
            'grade_level'             => 'nullable|string|max:80',
            'section_name'            => 'nullable|string|max:80',
            'day_of_week'             => 'nullable|string|max:20',
            'preferred_start_time'    => 'nullable|string|max:20',
            'preferred_end_time'      => 'nullable|string|max:20',
            'substitute_faculty_id'   => 'nullable|integer',
            'substitute_teacher_name' => 'nullable|string|max:200',
        ]);

        $validated['request_type'] = $validated['request_type'] === 'time_change'
            ? 'schedule_change'
            : $validated['request_type'];

        if (in_array($validated['request_type'], ['schedule_change', 'room_change', 'teacher_reassignment'], true)) {
            $request->validate([
                'subject'     => 'required|string|max:120',
                'grade_level' => 'required|string|max:80',
            ]);
            $validated['subject'] = $request->input('subject');
            $validated['grade_level'] = $request->input('grade_level');
        }

        if ($validated['request_type'] === 'teacher_reassignment') {
            $request->validate(['substitute_faculty_id' => 'required|integer']);
            $validated['substitute_faculty_id'] = (int) $request->input('substitute_faculty_id');
            if (! $request->filled('substitute_teacher_name')) {
                $sub = User::find($validated['substitute_faculty_id']);
                $request->merge([
                    'substitute_teacher_name' => $sub
                        ? trim(($sub->first_name ?? '') . ' ' . ($sub->last_name ?? '')) ?: ($sub->name ?? '')
                        : '',
                ]);
            }
        }

        if (TeacherPresenceSupport::isAbsenceLeaveType($validated['request_type'] ?? null)) {
            return TeacherLeaveRequestSupport::store($request, $connection);
        }

        $proposed = self::buildProposedPayload($request, $validated['proposed_changes'] ?? null);

        $dupMsg = DuplicateSubmissionSupport::pendingTeacherAdjustmentMessage($connection, (int) Auth::id(), [
            'schedule_id'          => $validated['schedule_id'] ?? null,
            'request_type'         => $validated['request_type'],
            'subject'              => $proposed['subject'] ?? $validated['subject'] ?? null,
            'grade_level'          => $proposed['grade_level'] ?? $validated['grade_level'] ?? null,
            'section_name'         => $proposed['section_name'] ?? $validated['section_name'] ?? null,
            'day_of_week'          => $proposed['day_of_week'] ?? $validated['day_of_week'] ?? null,
            'preferred_start_time' => $proposed['preferred_start_time'] ?? $validated['preferred_start_time'] ?? null,
            'preferred_end_time'   => $proposed['preferred_end_time'] ?? $validated['preferred_end_time'] ?? null,
        ]);
        if ($dupMsg !== null) {
            return ['success' => false, 'message' => $dupMsg];
        }

        $scheduleId = $validated['schedule_id'] ?? null;
        if ($scheduleId === '' || $scheduleId === 0) {
            $scheduleId = null;
        }

        $user = Auth::user();
        $teacherName = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher')
            : 'Teacher';

        $insert = [
            'faculty_id'           => Auth::id(),
            'teacher_name'         => $teacherName,
            'schedule_id'          => $scheduleId,
            'request_type'         => $validated['request_type'],
            'reason'               => $validated['reason'],
            'proposed_changes'     => $proposed ? json_encode($proposed) : null,
            'subject'              => $proposed['subject'] ?? $validated['subject'] ?? null,
            'grade_level'          => $proposed['grade_level'] ?? $validated['grade_level'] ?? null,
            'section_name'         => $proposed['section_name'] ?? $validated['section_name'] ?? null,
            'day_of_week'          => $proposed['day_of_week'] ?? $validated['day_of_week'] ?? null,
            'preferred_start_time' => $proposed['preferred_start_time'] ?? $validated['preferred_start_time'] ?? null,
            'preferred_end_time'   => $proposed['preferred_end_time'] ?? $validated['preferred_end_time'] ?? null,
            'status'               => 'pending',
            'created_at'           => now(),
            'updated_at'           => now(),
        ];

        $id = DB::connection($connection)->table(self::TABLE)->insertGetId($insert);

        AdminPortalNotificationSupport::notifyNewTeacherRequest(
            $connection,
            $teacherName,
            'schedule adjustment request',
            self::TABLE,
            (int) $id,
            (int) Auth::id()
        );

        return [
            'success' => true,
            'id'      => $id,
            'message' => 'Adjustment request submitted.',
        ];
    }

    /**
     * Apply approved adjustment to class_schedules.
     *
     * @return array{applied: bool, message: string}
     */
    public static function applyApprovedToSchedule(string $connection, object $requestRow, ?string $reviewerName = null): array
    {
        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            return ['applied' => false, 'message' => 'Class schedules table not found.'];
        }

        $schedule = self::resolveTargetSchedule($connection, $requestRow);
        if (! $schedule) {
            return ['applied' => false, 'message' => 'No matching approved schedule found to update.'];
        }

        $updates = self::buildScheduleUpdates($requestRow);
        if ($updates === []) {
            return ['applied' => false, 'message' => 'Request has no schedule fields to apply.'];
        }

        $reviewer = $reviewerName ?: 'admin';
        ScheduleAudit::setAuditUser($connection, $reviewer);

        $model = (new ClassSchedule)->setConnection($connection);
        $record = $model->newQuery()->find($schedule->id);
        if (! $record) {
            return ['applied' => false, 'message' => 'Schedule record no longer exists.'];
        }

        $changes = ScheduleAudit::collectChanges($record, $updates);
        $updates['version'] = ($record->version ?? 1) + 1;
        $updates['last_modified_by_admin'] = now();
        $updates['change_log'] = ScheduleAudit::appendChangeLog($record->change_log, 'updated', $reviewer, [
            'changes' => $changes,
            'details' => 'Applied from approved teacher adjustment request #' . ($requestRow->id ?? ''),
        ]);

        $record->update($updates);

        return ['applied' => true, 'message' => 'Schedule updated from approved request.'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payloadFromRequestRow(object $row): array
    {
        $parsed = self::parseProposed($row->proposed_changes ?? null);

        return array_merge($parsed, array_filter([
            'subject'              => $row->subject ?? null,
            'grade_level'          => $row->grade_level ?? null,
            'section_name'         => $row->section_name ?? null,
            'day_of_week'          => $row->day_of_week ?? null,
            'preferred_start_time' => $row->preferred_start_time ?? null,
            'preferred_end_time'   => $row->preferred_end_time ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildScheduleUpdates(object $requestRow): array
    {
        $payload = self::payloadFromRequestRow($requestRow);
        $updates = [];

        if (! empty($payload['subject'])) {
            $updates['subject'] = $payload['subject'];
        }
        if (! empty($payload['grade_level'])) {
            $updates['grade_level'] = $payload['grade_level'];
        }
        if (! empty($payload['section_name'])) {
            $updates['section_name'] = $payload['section_name'];
        }
        if (! empty($payload['day_of_week'])) {
            $updates['day_of_week'] = $payload['day_of_week'];
        }
        if (! empty($payload['preferred_start_time'])) {
            $updates['start_time'] = self::normalizeTimeForDb($payload['preferred_start_time']);
        }
        if (! empty($payload['preferred_end_time'])) {
            $updates['end_time'] = self::normalizeTimeForDb($payload['preferred_end_time']);
        }

        return $updates;
    }

    private static function resolveTargetSchedule(string $connection, object $requestRow): ?object
    {
        $facultyId = (int) ($requestRow->faculty_id ?? 0);
        if ($facultyId <= 0) {
            return null;
        }

        $query = DB::connection($connection)
            ->table('class_schedules')
            ->where('faculty_id', $facultyId)
            ->where(function ($q) {
                $q->where('admin_approved', true)->orWhere('status', 'active');
            });

        if (! empty($requestRow->schedule_id)) {
            return $query->where('id', (int) $requestRow->schedule_id)->first();
        }

        $payload = self::payloadFromRequestRow($requestRow);

        if (! empty($payload['subject'])) {
            $subject = trim((string) $payload['subject']);
            $query->where(function ($q) use ($subject) {
                $q->where('subject', $subject)
                    ->orWhereRaw('LOWER(subject) = ?', [strtolower($subject)]);
            });
        }

        if (! empty($payload['grade_level'])) {
            $grade = trim((string) $payload['grade_level']);
            $query->where(function ($q) use ($grade) {
                $q->where('grade_level', $grade)
                    ->orWhere('grade_level', 'like', '%' . $grade . '%');
            });
        }

        if (! empty($payload['section_name'])) {
            $section = trim((string) $payload['section_name']);
            $query->where(function ($q) use ($section) {
                $q->where('section_name', $section)
                    ->orWhereRaw('LOWER(section_name) = ?', [strtolower($section)]);
            });
        }

        return $query->orderBy('id')->first();
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

        return is_array($decoded) ? $decoded : ['detail' => $raw];
    }

    public static function formatTimeForInput(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $t = substr((string) $time, 0, 8);

        return strlen($t) >= 5 ? substr($t, 0, 5) : $t;
    }

    public static function normalizeTimeForDb(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $t = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
            return $t . ':00';
        }

        return substr($t, 0, 8);
    }

    public static function reviewerDisplayName(?int $userId): string
    {
        if (! $userId) {
            return 'admin';
        }

        $user = User::find($userId);

        return $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'admin')
            : 'admin';
    }
}

