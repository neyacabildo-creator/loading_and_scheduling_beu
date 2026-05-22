<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TeacherAdjustmentRequestSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SharedTeacherPortalController extends Controller
{
    // ── Helper: the table name ───────────────────────────────────────────────
    // The table lives in mysql_jh AND mysql_gs — NOT in the main DB.
    private const TBL = 'shared_teacher_requests';

    /**
     * Dashboard: show this shared teacher's schedules in both JH and GS.
     */
    public function dashboard()
    {
        $userId = Auth::id();

        // JH class schedules
        $jhSchedules = DB::connection('mysql_jh')
            ->table('class_schedules')
            ->where('faculty_id', $userId)
            ->where('admin_approved', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // GS class schedules
        $gsSchedules = DB::connection('mysql_gs')
            ->table('class_schedules')
            ->where('faculty_id', $userId)
            ->where('admin_approved', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Count pending requests from both school-level DBs
        $pendingRequests =
            DB::connection('mysql_jh')->table(self::TBL)
                ->where('faculty_id', $userId)->where('status', 'pending')->count()
            + DB::connection('mysql_gs')->table(self::TBL)
                ->where('faculty_id', $userId)->where('status', 'pending')->count();

        return view('shared-teacher.dashboard', compact(
            'jhSchedules',
            'gsSchedules',
            'pendingRequests'
        ));
    }

    /**
     * List the current user's schedule requests (merged from both DBs).
     */
    public function requests()
    {
        $userId = Auth::id();

        $jhReqs = DB::connection('mysql_jh')->table(self::TBL)
            ->where('faculty_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($r) { $r->level = 'jh'; });

        $gsReqs = DB::connection('mysql_gs')->table(self::TBL)
            ->where('faculty_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($r) { $r->level = 'gs'; });

        // Merge and sort by created_at descending
        $requests = $jhReqs->concat($gsReqs)->sortByDesc('created_at')->values();

        return view('shared-teacher.requests', compact('requests'));
    }

    /**
     * Approved schedules for the logged-in shared teacher (JH or GS only).
     */
    public function requestSchedules(Request $request)
    {
        $validated = $request->validate([
            'school_level' => 'required|in:jh,gs',
        ]);

        $conn = $validated['school_level'] === 'jh' ? 'mysql_jh' : 'mysql_gs';
        $schoolLevel = $validated['school_level'] === 'jh' ? 'junior_high' : 'grade_school';

        try {
            $schedules = TeacherAdjustmentRequestSupport::approvedSchedulesForTeacher(
                $conn,
                (int) Auth::id()
            );

            $subjects = collect($schedules)
                ->pluck('subject')
                ->filter()
                ->unique(fn ($s) => strtolower(trim((string) $s)))
                ->sort()
                ->values()
                ->all();

            return response()->json([
                'success'       => true,
                'school_level'  => $schoolLevel,
                'subjects'      => $subjects,
                'schedules'     => $schedules,
            ]);
        } catch (\Throwable $e) {
            Log::error('Shared teacher requestSchedules: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not load schedules for this school level.',
            ], 422);
        }
    }

    /**
     * Submit a new schedule request to the appropriate school-level DB.
     */
    public function storeRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'school_level'          => 'required|in:jh,gs',
                'subject'               => 'required|string|max:100',
                'grade_level'           => 'nullable|string|max:50',
                'section_name'          => 'nullable|string|max:100',
                'day_of_week'           => 'nullable|string|max:20',
                'schedule_date'         => 'nullable|date',
                'schedule_id'           => 'nullable|integer',
                'preferred_start_time'  => 'nullable|date_format:H:i',
                'preferred_end_time'    => 'nullable|date_format:H:i',
                'notes'                 => 'nullable|string|max:1000',
            ]);

            $user = Auth::user();
            $conn        = $validated['school_level'] === 'jh' ? 'mysql_jh' : 'mysql_gs';
            $schoolLevel = $validated['school_level'] === 'jh' ? 'junior_high' : 'grade_school';

            $insert = [
                'faculty_id'            => $user->id,
                'teacher_name'          => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? ''),
                'request_type'          => 'schedule_request',
                'school_level'          => $schoolLevel,
                'subject'               => $validated['subject'],
                'grade_level'           => $validated['grade_level'] ?? null,
                'section_name'          => $validated['section_name'] ?? null,
                'day_of_week'           => $validated['day_of_week'] ?? null,
                'preferred_start_time'  => $validated['preferred_start_time'] ?? null,
                'preferred_end_time'    => $validated['preferred_end_time'] ?? null,
                'description'           => $validated['notes'] ?? null,
                'status'                => 'pending',
                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            if (Schema::connection($conn)->hasColumn(self::TBL, 'schedule_date')) {
                $insert['schedule_date'] = $validated['schedule_date'] ?? null;
            }
            if (Schema::connection($conn)->hasColumn(self::TBL, 'schedule_id')) {
                $insert['schedule_id'] = $validated['schedule_id'] ?? null;
            }

            DB::connection($conn)->table(self::TBL)->insertGetId($insert);

            return redirect()->route('shared-teacher.requests')
                ->with('success', 'Your schedule request has been submitted.');
        } catch (\Exception $e) {
            Log::error('Shared teacher storeRequest error: ' . $e->getMessage());
            return redirect()->route('shared-teacher.requests')
                ->with('error', 'Error submitting request: ' . $e->getMessage());
        }
    }

    // ── Admin-side: JH Admin reviews requests ──────────────────────────────

    public function adminJhRequests()
    {
        $sharedTeacherIds = $this->sharedTeacherFacultyIds('mysql_jh');

        $allRequests = DB::connection('mysql_jh')->table(self::TBL)
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('created_at')
            ->get();

        $requests = $allRequests->filter(fn ($r) => in_array((int) $r->faculty_id, $sharedTeacherIds, true))->values();
        $sharedPresenceMap = \App\Support\TeacherPresenceSupport::activeStatusMapForTeachers('mysql_jh', $sharedTeacherIds);
        $requests = $requests->map(function ($r) use ($sharedPresenceMap) {
            $r->presence = $sharedPresenceMap[(int) $r->faculty_id] ?? null;

            return $r;
        });
        $reviewers = $this->loadReviewers($requests);
        $teacherScheduleRequests = $this->loadTeacherAdjustmentRequests('mysql_jh', 'mysql_jh_teacher', $sharedTeacherIds);
        $teacherLeaveRequests = \App\Support\TeacherLeaveRequestSupport::listForAdmin('mysql_jh', $sharedTeacherIds);
        $absentToday = $this->collectAbsentTodaySummary('mysql_jh', $sharedTeacherIds);

        return view('junior-high-admin.shared-teacher-requests', compact('requests', 'reviewers', 'teacherScheduleRequests', 'teacherLeaveRequests', 'absentToday'));
    }

    public function adminJhApprove(Request $request, int $id)
    {
        return $this->reviewSharedTeacherRequest('mysql_jh', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminJhReject(Request $request, int $id)
    {
        return $this->reviewSharedTeacherRequest('mysql_jh', $id, 'rejected', $request->input('admin_notes'));
    }

    // ── Admin-side: GS Admin reviews requests ──────────────────────────────

    public function adminGsRequests()
    {
        $sharedTeacherIds = $this->sharedTeacherFacultyIds('mysql_gs');

        $allRequests = DB::connection('mysql_gs')->table(self::TBL)
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('created_at')
            ->get();

        $requests = $allRequests->filter(fn ($r) => in_array((int) $r->faculty_id, $sharedTeacherIds, true))->values();
        $reviewers = $this->loadReviewers($requests);
        $teacherScheduleRequests = $this->loadTeacherAdjustmentRequests('mysql_gs', 'mysql_gs_teacher', $sharedTeacherIds);
        $teacherLeaveRequests = \App\Support\TeacherLeaveRequestSupport::listForAdmin('mysql_gs', $sharedTeacherIds);

        return view('grade-school-admin.shared-teacher-requests', compact('requests', 'reviewers', 'teacherScheduleRequests', 'teacherLeaveRequests'));
    }

    public function adminGsApprove(Request $request, int $id)
    {
        return $this->reviewSharedTeacherRequest('mysql_gs', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminGsReject(Request $request, int $id)
    {
        return $this->reviewSharedTeacherRequest('mysql_gs', $id, 'rejected', $request->input('admin_notes'));
    }

    // ── Admin: Teacher schedule adjustment requests (teacher_requests) ───────

    public function adminJhApproveScheduleRequest(Request $request, int $id)
    {
        return $this->reviewTeacherAdjustmentRequest('mysql_jh', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminJhRejectScheduleRequest(Request $request, int $id)
    {
        return $this->reviewTeacherAdjustmentRequest('mysql_jh', $id, 'rejected', $request->input('admin_notes'));
    }

    public function adminGsApproveScheduleRequest(Request $request, int $id)
    {
        return $this->reviewTeacherAdjustmentRequest('mysql_gs', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminGsRejectScheduleRequest(Request $request, int $id)
    {
        return $this->reviewTeacherAdjustmentRequest('mysql_gs', $id, 'rejected', $request->input('admin_notes'));
    }

    public function adminJhApproveLeaveRequest(Request $request, int $id)
    {
        return \App\Support\TeacherLeaveRequestSupport::review('mysql_jh', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminJhRejectLeaveRequest(Request $request, int $id)
    {
        return \App\Support\TeacherLeaveRequestSupport::review('mysql_jh', $id, 'rejected', $request->input('admin_notes'));
    }

    public function adminGsApproveLeaveRequest(Request $request, int $id)
    {
        return \App\Support\TeacherLeaveRequestSupport::review('mysql_gs', $id, 'approved', $request->input('admin_notes'));
    }

    public function adminGsRejectLeaveRequest(Request $request, int $id)
    {
        return \App\Support\TeacherLeaveRequestSupport::review('mysql_gs', $id, 'rejected', $request->input('admin_notes'));
    }

    // ── Private helper ───────────────────────────────────────────────────────

    /**
     * Fetch reviewer User models from the main DB keyed by ID.
     */
    private function loadReviewers($requests): \Illuminate\Support\Collection
    {
        $ids = $requests->pluck('reviewed_by')->filter()->unique()->values()->all();
        if (empty($ids)) {
            return collect();
        }
        return User::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Fetch requester User models from the main DB keyed by faculty_id.
     */
    private function loadRequestUsers($requests): \Illuminate\Support\Collection
    {
        $ids = $requests->pluck('faculty_id')->filter()->unique()->values()->all();
        if (empty($ids)) {
            return collect();
        }
        return User::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Faculty IDs for shared teachers (role + school shared_teachers table).
     */
    private function sharedTeacherFacultyIds(string $schoolConn): array
    {
        $fromRole = User::whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))->pluck('id');
        $fromTable = collect();
        try {
            $fromTable = DB::connection($schoolConn)->table('shared_teachers')->pluck('faculty_id');
        } catch (\Throwable $e) {
            // table may not exist on older installs
        }

        return $fromRole->merge($fromTable)->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Regular teacher schedule adjustment requests (excludes shared teachers).
     */
    private function loadTeacherAdjustmentRequests(string $adminConn, string $teacherConn, array $sharedTeacherIds): \Illuminate\Support\Collection
    {
        $excludeIds = collect($sharedTeacherIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $rows = collect();

        try {
            if (Schema::connection($adminConn)->hasTable('teacher_requests')) {
                $rows = DB::connection($adminConn)
                    ->table('teacher_requests')
                    ->orderByRaw("FIELD(status,'pending','approved','rejected')")
                    ->orderByDesc('created_at')
                    ->get()
                    ->filter(fn ($r) => ! in_array((int) $r->faculty_id, $excludeIds, true))
                    ->filter(fn ($r) => ! \App\Support\TeacherPresenceSupport::isAbsenceLeaveType($r->request_type ?? null));
            }
        } catch (\Throwable $e) {
            Log::warning('loadTeacherAdjustmentRequests [teacher_requests]: ' . $e->getMessage());
        }

        if ($rows->isEmpty()) {
            $rows = $this->loadLegacyTeacherAdjustmentRows($teacherConn, $excludeIds);
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        $userIds = $rows->pluck('faculty_id')
            ->merge($rows->pluck('requested_by'))
            ->merge($rows->pluck('reviewed_by'))
            ->filter()
            ->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $presenceMap = \App\Support\TeacherPresenceSupport::activeStatusMapForTeachers(
            $adminConn,
            $rows->pluck('faculty_id')->filter()->map(fn ($id) => (int) $id)->all()
        );

        $scheduleIds = $rows->pluck('schedule_id')->filter()->unique();
        $schedules = $scheduleIds->isNotEmpty()
            ? DB::connection($adminConn)->table('class_schedules')->whereIn('id', $scheduleIds)->get()->keyBy('id')
            : collect();

        return $rows->map(function ($r) use ($users, $schedules, $presenceMap) {
            $facultyId = (int) ($r->faculty_id ?? $r->requested_by ?? 0);
            $user = $users->get($facultyId);
            $schedule = $r->schedule_id ? $schedules->get($r->schedule_id) : null;
            $reviewer = $users->get($r->reviewed_by);
            $display = $this->resolveAdjustmentRequestDisplay($r, $schedule);

            $teacherName = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? null)
                : null;
            if (! $teacherName && ! empty($r->teacher_name)) {
                $teacherName = $r->teacher_name;
            }

            return (object) [
                'id'                   => $r->id,
                'source'               => 'teacher_requests',
                'status'               => $r->status,
                'teacher_name'         => $teacherName,
                'request_type'         => $r->request_type,
                'request_type_label'   => $display['request_type_label'] ?? null,
                'subject'              => $display['subject'] ?? $r->subject,
                'grade_level'          => $display['grade_level'] ?? $r->grade_level,
                'section_name'         => $display['section_name'] ?? $r->section_name,
                'grade_section'        => $display['grade_section'],
                'day_of_week'          => $display['day_of_week'] ?? $r->day_of_week,
                'preferred_start_time' => $display['preferred_start_time'] ?? $r->preferred_start_time,
                'preferred_end_time'   => $display['preferred_end_time'] ?? $r->preferred_end_time,
                'reason'               => $r->reason ?? $r->description ?? null,
                'description'          => $r->reason ?? $r->description ?? null,
                'proposed_changes'     => $r->proposed_changes,
                'admin_notes'          => $r->admin_notes,
                'created_at'           => $r->created_at,
                'reviewed_at'          => $r->reviewed_at,
                'user'                 => $user,
                'reviewer'             => $reviewer,
                'presence'             => $presenceMap[$facultyId] ?? null,
            ];
        })->sortByDesc('created_at')->values();
    }

    /**
     * Legacy rows still stored in mysql_*_teacher.schedule_adjustment_requests.
     */
    private function loadLegacyTeacherAdjustmentRows(string $teacherConn, array $excludeIds): \Illuminate\Support\Collection
    {
        try {
            if (! Schema::connection($teacherConn)->hasTable('schedule_adjustment_requests')) {
                return collect();
            }

            return DB::connection($teacherConn)
                ->table('schedule_adjustment_requests')
                ->orderByRaw("FIELD(status,'pending','approved','rejected')")
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn ($r) => ! in_array((int) $r->requested_by, $excludeIds, true))
                ->map(function ($r) {
                    $r->faculty_id = $r->requested_by;

                    return $r;
                });
        } catch (\Throwable $e) {
            Log::warning('loadTeacherAdjustmentRequests [legacy]: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * Build admin-facing labels for a schedule adjustment row.
     */
    private function resolveAdjustmentRequestDisplay(object $row, ?object $schedule): array
    {
        $parsed = $this->parseAdjustmentProposed($row->proposed_changes ?? null);
        $typeLabel = $this->adjustmentTypeLabel($row->request_type ?? null);

        $subject = $schedule->subject ?? ($parsed['subject'] ?? null) ?? ($row->subject ?? null);

        $gradeLevel = $schedule->grade_level ?? ($parsed['grade_level'] ?? null) ?? ($row->grade_level ?? null);
        $sectionName = $schedule->section_name ?? ($parsed['section_name'] ?? null) ?? ($row->section_name ?? null);

        $gradeSection = trim(($gradeLevel ?? '') . ($sectionName ? ' – ' . $sectionName : ''));

        $day = $schedule->day_of_week ?? ($parsed['day_of_week'] ?? null) ?? ($row->day_of_week ?? null);
        $start = $schedule->start_time ?? ($parsed['preferred_start_time'] ?? $parsed['preferred_time'] ?? null) ?? ($row->preferred_start_time ?? null);
        $end = $schedule->end_time ?? ($parsed['preferred_end_time'] ?? null) ?? ($row->preferred_end_time ?? null);

        if (!$day && !empty($parsed['preferred_day'])) {
            $day = $parsed['preferred_day'];
        }

        return [
            'subject'              => $subject,
            'request_type_label'   => $typeLabel,
            'grade_level'          => $gradeLevel,
            'section_name'         => $sectionName,
            'grade_section'        => $gradeSection !== '' ? $gradeSection : null,
            'day_of_week'          => $day,
            'preferred_start_time' => $start,
            'preferred_end_time'   => $end,
        ];
    }

    private function adjustmentTypeLabel(?string $type): string
    {
        return \App\Support\AdminRequestDisplay::requestTypeLabel($type);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAdjustmentProposed(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['detail' => $raw];
    }

    private function reviewTeacherAdjustmentRequest(string $adminConn, int $id, string $status, ?string $notes)
    {
        try {
            $row = DB::connection($adminConn)->table('teacher_requests')->where('id', $id)->first();

            if (! $row) {
                $teacherConn = str_contains($adminConn, 'gs') ? 'mysql_gs_teacher' : 'mysql_jh_teacher';
                $legacy = DB::connection($teacherConn)->table('schedule_adjustment_requests')->where('id', $id)->first();
                if (! $legacy) {
                    return back()->with('error', 'Request not found.');
                }
            }

            $applyNote = '';
            if ($status === 'approved' && $row && ! \App\Support\TeacherPresenceSupport::isAbsenceLeaveType($row->request_type ?? null)) {
                $result = \App\Support\TeacherAdjustmentRequestSupport::applyApprovedToSchedule(
                    $adminConn,
                    $row,
                    \App\Support\TeacherAdjustmentRequestSupport::reviewerDisplayName(Auth::id())
                );
                $applyNote = $result['applied'] ? ' ' . $result['message'] : ' Warning: ' . $result['message'];
            }

            $updated = DB::connection($adminConn)
                ->table('teacher_requests')
                ->where('id', $id)
                ->update([
                    'status'      => $status,
                    'admin_notes' => $notes,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'updated_at'  => now(),
                ]);

            if ($updated > 0 && $row) {
                \App\Support\TeacherPortalNotificationSupport::notifyTeacherRequestDecision(
                    $adminConn,
                    $row,
                    $status,
                    $notes
                );
            }

            if ($updated === 0) {
                $teacherConn = str_contains($adminConn, 'gs') ? 'mysql_gs_teacher' : 'mysql_jh_teacher';
                DB::connection($teacherConn)
                    ->table('schedule_adjustment_requests')
                    ->where('id', $id)
                    ->update([
                        'status'      => $status,
                        'admin_notes' => $notes,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'updated_at'  => now(),
                    ]);
            }

            return back()->with('success', 'Teacher request ' . $status . '.' . $applyNote);
        } catch (\Throwable $e) {
            Log::error('reviewTeacherAdjustmentRequest: ' . $e->getMessage());
            return back()->with('error', 'Could not update request.');
        }
    }

    /**
     * Teachers / shared teachers with approved leave covering today.
     *
     * @return array{regular: array<int, array<string, string>>, shared: array<int, array<string, string>>}
     */
    private function collectAbsentTodaySummary(string $adminConn, array $sharedTeacherIds): array
    {
        $sharedSet = array_flip(array_map('intval', $sharedTeacherIds));
        $regular = [];
        $shared = [];

        if (! \Illuminate\Support\Facades\Schema::connection($adminConn)->hasTable(\App\Support\TeacherLeaveRequestSupport::TABLE)) {
            return ['regular' => [], 'shared' => []];
        }

        $today = now()->toDateString();
        $rows = DB::connection($adminConn)
            ->table(\App\Support\TeacherLeaveRequestSupport::TABLE)
            ->where('status', 'approved')
            ->where('date_from', '<=', $today)
            ->where('date_to', '>=', $today)
            ->get();

        $userIds = $rows->pluck('teacher_id')->unique()->filter();
        $users = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        foreach ($rows as $row) {
            $tid = (int) $row->teacher_id;
            $user = $users->get($tid);
            $name = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher')
                : 'Teacher #' . $tid;
            $entry = [
                'id'    => $tid,
                'name'  => $name,
                'label' => ($row->leave_type ?? '') === 'absent' ? 'Absent' : 'On Leave',
                'type'  => (string) ($row->leave_type ?? ''),
            ];
            if (isset($sharedSet[$tid])) {
                $shared[$tid] = $entry;
            } else {
                $regular[$tid] = $entry;
            }
        }

        return ['regular' => array_values($regular), 'shared' => array_values($shared)];
    }

    private function reviewSharedTeacherRequest(string $connection, int $id, string $status, ?string $notes)
    {
        try {
            $row = DB::connection($connection)->table(self::TBL)->where('id', $id)->first();
            if (! $row) {
                return back()->with('error', 'Request not found.');
            }

            DB::connection($connection)->table(self::TBL)->where('id', $id)->update([
                'status'      => $status,
                'admin_notes' => $notes,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at'  => now(),
            ]);

            \App\Support\TeacherPortalNotificationSupport::notifySharedTeacherRequestDecision(
                $connection,
                $row,
                $status,
                $notes
            );

            return back()->with('success', 'Request ' . $status . '.');
        } catch (\Throwable $e) {
            Log::error('reviewSharedTeacherRequest: ' . $e->getMessage());

            return back()->with('error', 'Could not update request.');
        }
    }
}
