<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\ScheduleApproval;
use App\Models\Room;
use App\Models\User;
use App\Support\ScheduleAudit;
use App\Support\ScheduleDisplaySupport;
use App\Support\TeacherPortalSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ScheduleController extends Controller
{
    /**
     * Get all schedules (for admin)
     */
    public function index()
    {
        try {
            $connection = config('database.school_connection') ?: 'mysql_jh';
            $schedules = ClassSchedule::on($connection)->orderBy('created_at', 'desc')->get();

            $userIds = $schedules->pluck('faculty_id')
                ->merge($schedules->pluck('approved_by'))
                ->filter()->unique();
            $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

            $roomIds = $schedules->pluck('room_id')->filter()->unique();
            $rooms = $roomIds->isNotEmpty()
                ? Room::on($connection)->whereIn('id', $roomIds)->get()->keyBy('id')
                : collect();

            $result = $schedules->map(function (ClassSchedule $schedule) use ($users, $rooms) {
                $data = ScheduleDisplaySupport::enrichForApi(
                    $schedule->toArray(),
                    $schedule,
                    isset($rooms[$schedule->room_id]) ? $rooms[$schedule->room_id] : null,
                    isset($users[$schedule->faculty_id]) ? $users[$schedule->faculty_id] : null
                );
                $data['time_start'] = $schedule->start_time;
                $data['time_end'] = $schedule->end_time;
                $data['schedule_date'] = $schedule->getRawOriginal('schedule_date');
                $data['display_date'] = ScheduleDisplaySupport::formatScheduleDate($data['schedule_date']);
                if (isset($rooms[$schedule->room_id])) {
                    $data['room'] = $rooms[$schedule->room_id]->toArray();
                }
                $data['approver'] = isset($users[$schedule->approved_by])
                    ? ['id' => $schedule->approved_by, 'name' => $users[$schedule->approved_by]->name]
                    : null;
                $data['approved_by_name'] = ScheduleAudit::approverName($schedule->approved_by, $users);

                return $data;
            })->values();

            return response()->json([
                'data' => $result,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Schedule index error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'Error loading schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedules for a specific teacher
     */
    public function getTeacherSchedules()
    {
        try {
            $facultyId = Auth::id();
            
            $schedules = ClassSchedule::where('faculty_id', $facultyId)
                ->where('admin_approved', true)
                ->where('status', 'active')
                ->orderBy('day_of_week')
                ->get();

            $roomIds = $schedules->pluck('room_id')->filter()->unique();
            $rooms   = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
            $result = $schedules->map(function ($s) use ($facultyId, $rooms) {
                $s->setRelation('faculty', User::find($facultyId));
                $s->setRelation('room', $rooms[$s->room_id] ?? null);
                $data = $s->toArray();
                $data['grade_section'] = trim(($s->grade_level ?? '') . ($s->section_name ? ' – ' . $s->section_name : ''));
                $data['room_label'] = TeacherPortalSupport::roomLabel($s);

                return $data;
            });

            return response()->json($result->values());
        } catch (\Exception $e) {
            Log::error('Get teacher schedules error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'Error loading schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approved schedules only
     */
    public function getApprovedSchedules()
    {
        $schedules = ClassSchedule::where('admin_approved', true)
            ->where('status', 'active')
            ->orderBy('day_of_week')
            ->get();

        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $roomIds = $schedules->pluck('room_id')->filter()->unique();
        $rooms   = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
        $schedules->each(function($s) use ($users, $rooms) {
            $s->setRelation('faculty', $users[$s->faculty_id] ?? null);
            $s->setRelation('room',    $rooms[$s->room_id]    ?? null);
        });

        return response()->json($schedules);
    }

    /**
     * Store schedules — supports both the batch grid form (slots[timeKey][sectionIndex])
     * and legacy single-record JSON API calls.
     */
    public function store(Request $request)
    {
        // ── Batch grid mode (from the schedule-form grid) ────────────────
        if ($request->has('slots')) {
            $request->validate([
                'grade_level' => 'required|string|max:20',
                'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            ]);

            $gradeLevel = $request->input('grade_level');
            $dayOfWeek  = $request->input('day_of_week');
            $slots      = $request->input('slots', []);
            $scheduleDate = $request->input('schedule_date') ?: null;
            $sectionRooms = $request->input('section_rooms', []);

            // Section names indexed by grade
            $sectionsByGrade = [
                'Grade 7'  => ['SERAPHIM','CHERUBIM','MICHAEL','RAPHAEL','GABRIEL'],
                'Grade 8'  => ['THERESE','ALOYSIUS','AGNES','JOHN','GORETTI'],
                'Grade 9'  => ['CHARTRES','PIAT','FATIMA','CARMEL','LOURDES'],
                'Grade 10' => ['PAUL','PLC','MBF','MICHEAU','MARIA'],
            ];
            $sections = $sectionsByGrade[$gradeLevel] ?? ['SECTION 1','SECTION 2','SECTION 3','SECTION 4','SECTION 5'];

            // Time key → [start, end]
            $timeMap = [
                '0745_0845' => ['07:45','08:45'],
                '0915_1015' => ['09:15','10:15'],
                '1015_1115' => ['10:15','11:15'],
                '1115_1215' => ['11:15','12:15'],
                '1315_1415' => ['13:15','14:15'],
                '1415_1515' => ['14:15','15:15'],
                '1515_1615' => ['15:15','16:15'],
                '1615_1715' => ['16:15','17:15'],
            ];

            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

            // ── Conflict detection – block save before writing anything ──────────
            $seenTeacherSlots = []; // "$facultyId|$timeKey" => first sectionName
            $dailyNewCounts   = []; // "$facultyId|$dayOfWeek" => new entries in this batch
            $conflicts = [];
            // Shared teacher faculty_ids for cross-school (GS) conflict check
            $jhSharedFacultyIds = \App\Models\SharedTeacher::where('is_active', true)
                ->pluck('faculty_id')->map(fn($id) => (string) $id)->all();

            foreach ($slots as $timeKey => $sectionSlots) {
                if (!isset($timeMap[$timeKey])) continue;
                [$startTime] = $timeMap[$timeKey];

                foreach ($sectionSlots as $idx => $cell) {
                    $sectionName = $sections[$idx] ?? ('SECTION ' . ($idx + 1));

                    // Collect primary + extra rows for conflict checking
                    $cellRows = [];
                    $ps = trim($cell['subject'] ?? '');
                    $pf = !empty($cell['faculty_id']) ? (string) $cell['faculty_id'] : null;
                    if ($ps !== '') {
                        $cellRows[] = ['subject' => $ps, 'faculty_id' => $pf];
                    }
                    foreach ($cell['extra'] ?? [] as $extra) {
                        $s = trim($extra['subject'] ?? '');
                        $f = !empty($extra['faculty_id']) ? (string) $extra['faculty_id'] : null;
                        if ($s !== '') {
                            $cellRows[] = ['subject' => $s, 'faculty_id' => $f];
                        }
                    }
                    if (empty($cellRows)) continue;

                    $cellDup = \App\Support\ScheduleFormConflictSupport::duplicateSubjectTeacherInCell($cellRows);
                    if ($cellDup) {
                        $conflicts[] = "{$sectionName} at {$startTime}: {$cellDup}";

                        continue;
                    }

                    foreach ($cellRows as $row) {
                        $primarySubject = $row['subject'];
                        $primaryFaculty = $row['faculty_id'];

                        if (!$primaryFaculty) {
                            $conflicts[] = "{$sectionName}: subject \"{$primarySubject}\" has no teacher assigned — please select a teacher or clear the subject field.";
                            continue;
                        }
                        $slotKey = $primaryFaculty . '|' . $timeKey;

                        // 1) Same teacher in multiple sections at same time within this form
                        if (isset($seenTeacherSlots[$slotKey])) {
                            $teacher = \App\Models\User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $conflicts[] = "{$name} is assigned to multiple sections at {$startTime} ({$seenTeacherSlots[$slotKey]} and {$sectionName})";
                        }
                        $seenTeacherSlots[$slotKey] = $sectionName;

                        // 2) Teacher already has an approved schedule at same day+time (JH)
                        $existing = ClassSchedule::where('faculty_id', (int) $primaryFaculty)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('start_time', $startTime)
                            ->where('admin_approved', true)
                            ->first();
                        if ($existing) {
                            $teacher = \App\Models\User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $conflicts[] = "{$name} already has an approved schedule at {$startTime} on {$dayOfWeek} ({$existing->grade_level} – {$existing->section_name})";
                        }

                        // 3) Exact duplicate — same record already exists (approved or pending)
                        if (!$existing) {
                            $duplicate = ClassSchedule::where('faculty_id', (int) $primaryFaculty)
                                ->where('day_of_week', $dayOfWeek)
                                ->where('start_time', $startTime)
                                ->where('section_name', $sectionName)
                                ->where('subject', $primarySubject)
                                ->whereIn('status', ['pending', 'active'])
                                ->first();
                            if ($duplicate) {
                                $teacher = \App\Models\User::find((int) $primaryFaculty);
                                $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                                $statusLabel = $duplicate->admin_approved ? 'approved' : 'pending';
                                $conflicts[] = "{$name} – \"{$primarySubject}\" for {$sectionName} on {$dayOfWeek} at {$startTime} already exists ({$statusLabel})";
                            }
                        }

                        // 4) Cross-school check for shared teachers (GS schedules)
                        if (in_array($primaryFaculty, $jhSharedFacultyIds, true)) {
                            $crossExisting = DB::connection('mysql_gs')
                                ->table('class_schedules')
                                ->where('faculty_id', (int) $primaryFaculty)
                                ->where('day_of_week', $dayOfWeek)
                                ->where('start_time', $startTime)
                                ->where('admin_approved', true)
                                ->first();
                            if ($crossExisting) {
                                $teacher = \App\Models\User::find((int) $primaryFaculty);
                                $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                                $conflicts[] = "{$name} (shared teacher) already has an approved Grade School schedule at {$startTime} on {$dayOfWeek} ({$crossExisting->grade_level} – {$crossExisting->section_name})";
                            }
                        }

                        // 5) Daily subject limit — max 5 subjects per teacher per day
                        $dailyKey = $primaryFaculty . '|' . $dayOfWeek;
                        $dailyNewCounts[$dailyKey] = ($dailyNewCounts[$dailyKey] ?? 0) + 1;
                        $existingDayCount = ClassSchedule::where('faculty_id', (int) $primaryFaculty)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('admin_approved', true)
                            ->count();
                        if (($existingDayCount + $dailyNewCounts[$dailyKey]) > 5) {
                            $teacher = \App\Models\User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $conflicts[] = "{$name} would exceed the 5-subject daily limit on {$dayOfWeek} (currently {$existingDayCount} approved subject(s) — max is 5)";
                        }
                    }
                }
            }

            if (!empty($conflicts)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Schedule not saved — conflict(s) detected: ' . implode(' | ', $conflicts));
            }
            // ── End conflict detection ───────────────────────────────────────────

            $created = 0;
            foreach ($slots as $timeKey => $sectionSlots) {
                if (!isset($timeMap[$timeKey])) continue;
                [$startTime, $endTime] = $timeMap[$timeKey];

                foreach ($sectionSlots as $idx => $cell) {
                    // Collect all subject rows: primary + any extra added via "+ Add Subject"
                    $allRows = [];
                    $primarySubject = trim($cell['subject'] ?? '');
                    $primaryFaculty = $cell['faculty_id'] ?? null;
                    if ($primarySubject !== '' && !empty($primaryFaculty)) {
                        $allRows[] = ['subject' => $primarySubject, 'faculty_id' => $primaryFaculty];
                    }
                    foreach ($cell['extra'] ?? [] as $extra) {
                        $s = trim($extra['subject'] ?? '');
                        $f = $extra['faculty_id'] ?? null;
                        if ($s !== '' && !empty($f)) {
                            $allRows[] = ['subject' => $s, 'faculty_id' => $f];
                        }
                    }

                    foreach ($allRows as $row) {
                    $subject   = $row['subject'];
                    $facultyId = $row['faculty_id'];

                    $sectionName  = $sections[$idx] ?? ('SECTION ' . ($idx + 1));
                    $gradeSection = $gradeLevel . ' - ' . $sectionName;

                    $changeLog = ScheduleAudit::appendChangeLog(
                        [],
                        'created',
                        Auth::user()?->name,
                        ['details' => 'Batch grid schedule created by admin']
                    );

                    $scheduleData = [
                        'faculty_id'    => $facultyId ?: null,
                        'subject'       => strtoupper($subject),
                        'grade_level'   => $gradeLevel,
                        'section_name'  => $sectionName,
                        'day_of_week'   => $dayOfWeek,
                        'schedule_date' => $scheduleDate,
                        'start_time'    => $startTime,
                        'end_time'      => $endTime,
                        'status'        => 'pending',
                        'admin_approved'=> false,
                        'version'       => 1,
                        'change_log'    => $changeLog,
                    ];

                    $schedule = ClassSchedule::create($scheduleData);

                    ScheduleApproval::create([
                        'schedule_id'  => $schedule->id,
                        'submitted_by' => Auth::id(),
                        'status'       => 'pending',
                    ]);

                    $created++;
                    } // end foreach allRows
                }
            }

            return redirect()->route('admin.class-schedule')
                ->with('success', "$created schedule(s) created for $gradeLevel on $dayOfWeek. Approve them in the Pending Schedules section.");
        }

        // ── Legacy single-record API mode ─────────────────────────────────
        $facultyId = $request->input('faculty_id') ?? Auth::id();

        $validated = $request->validate([
            'subject'       => 'required|string|max:255',
            'grade_level'   => 'sometimes|string|max:20',
            'section_name'  => 'sometimes|string|max:30',
            'day_of_week'   => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedule_date' => 'nullable|date',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'student_count' => 'nullable|integer|min:1|max:100',
        ]);

        $validated['faculty_id']    = $facultyId;
        $validated['status']        = 'pending';
        $validated['admin_approved']= false;
        $validated['version']       = 1;
        $validated['change_log']    = ScheduleAudit::appendChangeLog(
            [],
            'created',
            Auth::user()?->name,
            ['details' => 'Schedule created and submitted for approval']
        );

        ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

        $schedule = ClassSchedule::create($validated);

        ScheduleApproval::create([
            'schedule_id'  => $schedule->id,
            'submitted_by' => $validated['faculty_id'],
            'status'       => 'pending',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Schedule created successfully.',
                'schedule' => tap($schedule, function ($s) {
                    $s->setRelation('faculty', $s->faculty_id ? User::find($s->faculty_id) : null);
                    $s->setRelation('room',    $s->room_id    ? Room::find($s->room_id)    : null);
                    $s->setAttribute('approved_by_name', null);
                }),
            ], 201);
        }

        return redirect()->route('admin.class-schedule')
            ->with('success', 'Schedule created successfully! Review it in the Pending Schedules section to approve or reject.');
    }

    /**
     * Get a single schedule for editing
     */
    public function show(ClassSchedule $schedule)
    {
        $schedule->setRelation('faculty', $schedule->faculty_id ? User::find($schedule->faculty_id) : null);
        $schedule->setRelation('room', $schedule->room_id ? Room::find($schedule->room_id) : null);
        $schedule->setRelation('approver', $schedule->approved_by ? User::find($schedule->approved_by) : null);
        $schedule->setAttribute('approved_by_name', $schedule->approver?->name);
        return response()->json($schedule);
    }

    /**
     * Approve a schedule (admin action)
     */
    public function approve(Request $request, ClassSchedule $schedule)
    {
        try {
            // Check if user is admin
            $userRole = Auth::user()->role?->name;
            if ($userRole !== 'admin' && strpos($userRole, 'admin') === false) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

            // ── Conflict detection: prevent approving if teacher already has an active schedule at same day+time ──
            if ($schedule->faculty_id && $schedule->day_of_week && $schedule->start_time) {
                $existingConflict = ClassSchedule::where('faculty_id', $schedule->faculty_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('start_time', $schedule->start_time)
                    ->where('admin_approved', true)
                    ->whereIn('status', ['active'])
                    ->where('id', '!=', $schedule->id)
                    ->first();

                if ($existingConflict) {
                    $teacher = User::find($schedule->faculty_id);
                    $name = $teacher
                        ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name)
                        : "Teacher #{$schedule->faculty_id}";
                    // Mark the pending schedule as conflict so it appears in conflict stats
                    $schedule->update(['status' => 'conflict']);
                    return response()->json([
                        'success'  => false,
                        'conflict' => true,
                        'message'  => "⚠ Conflict: {$name} is already approved for {$existingConflict->grade_level} – {$existingConflict->section_name} at "
                                      . substr($schedule->start_time, 0, 5) . " on {$schedule->day_of_week}. Resolve the existing schedule first.",
                    ], 409);
                }
                // Cross-school check for shared teachers (GS schedules)
                $isSharedJH = \App\Models\SharedTeacher::where('faculty_id', $schedule->faculty_id)
                    ->where('is_active', true)->exists();
                if ($isSharedJH) {
                    $crossConflict = DB::connection('mysql_gs')
                        ->table('class_schedules')
                        ->where('faculty_id', $schedule->faculty_id)
                        ->where('day_of_week', $schedule->day_of_week)
                        ->where('start_time', $schedule->start_time)
                        ->where('admin_approved', true)
                        ->first();
                    if ($crossConflict) {
                        $teacher = User::find($schedule->faculty_id);
                        $name = $teacher
                            ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name)
                            : "Teacher #{$schedule->faculty_id}";
                        $schedule->update(['status' => 'conflict']);
                        return response()->json([
                            'success'  => false,
                            'conflict' => true,
                            'message'  => "⚠ Conflict: {$name} (shared teacher) already has an approved Grade School schedule at "
                                          . substr($schedule->start_time, 0, 5) . " on {$schedule->day_of_week} ({$crossConflict->grade_level} – {$crossConflict->section_name}).",
                        ], 409);
                    }
                }

                // Daily subject limit — max 5 subjects per teacher per day
                $dayCount = ClassSchedule::where('faculty_id', $schedule->faculty_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('admin_approved', true)
                    ->where('id', '!=', $schedule->id)
                    ->count();
                if ($dayCount >= 5) {
                    $teacher = User::find($schedule->faculty_id);
                    $name = $teacher
                        ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name)
                        : "Teacher #{$schedule->faculty_id}";
                    $schedule->update(['status' => 'conflict']);
                    return response()->json([
                        'success'  => false,
                        'conflict' => true,
                        'message'  => "⚠ Overloaded: {$name} already has {$dayCount} approved class(es) on {$schedule->day_of_week} (maximum is 5 subjects per day). Resolve the schedule before approving.",
                    ], 409);
                }
            }
            // ── End conflict detection ──

            // Update schedule
            $schedule->update([
                'admin_approved' => true,
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'change_log' => ScheduleAudit::appendChangeLog($schedule->change_log, 'approved', Auth::user()?->name),
                'last_modified_by_admin' => now(),
            ]);

            ScheduleApproval::updateOrCreate(
                ['schedule_id' => $schedule->id],
                ['submitted_by' => $schedule->faculty_id ?? 0, 'status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]
            );

            // Explicitly remove from pending_schedules (triggers handle it, but ensure consistency)
            $dbConn = $schedule->getConnectionName();
            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $schedule->id)
                ->delete();

            // Auto-calculate and update weekly load_hours for this faculty
            if ($schedule->faculty_id) {
                $loadResult = DB::connection($dbConn)->selectOne(
                    "SELECT COALESCE(SUM(
                         HOUR(TIMEDIFF(end_time, start_time)) + MINUTE(TIMEDIFF(end_time, start_time)) / 60.0
                     ), 0) AS total_hours
                     FROM class_schedules
                     WHERE faculty_id = ?
                       AND admin_approved = 1
                       AND status NOT IN ('rejected')",
                    [$schedule->faculty_id]
                );
                $totalHours = $loadResult ? round((float) $loadResult->total_hours, 2) : 0;
                $loadStatus = $totalHours > 6 ? 'overloaded' : 'active';

                FacultyLoad::where('faculty_id', $schedule->faculty_id)
                    ->whereIn('status', ['active', 'overloaded', 'inactive'])
                    ->update(['load_hours' => $totalHours, 'status' => $loadStatus]);
            }

            $fresh = $schedule->fresh();
            if ($fresh) {
                $fresh->setRelation('faculty', $fresh->faculty_id ? User::find($fresh->faculty_id) : null);
                $fresh->setRelation('room', $fresh->room_id ? Room::find($fresh->room_id) : null);
                $fresh->setRelation('approver', $fresh->approved_by ? User::find($fresh->approved_by) : null);
                $fresh->setAttribute('approved_by_name', $fresh->approver?->name);
            }
            return response()->json([
                'success' => true,
                'message' => 'Schedule approved successfully.',
                'schedule' => $fresh,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Schedule approval error: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Error approving schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject/Disapprove a schedule (admin action)
     */
    public function reject(Request $request, ClassSchedule $schedule)
    {
        try {
            $userRole = Auth::user()->role?->name;
            if ($userRole !== 'admin' && strpos((string) $userRole, 'admin') === false) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $reason = $validated['reason'] ?? 'No reason provided';
            $dbConn = $schedule->getConnectionName();

            ScheduleAudit::setAuditUser($dbConn, Auth::user()?->name);

            if (\Illuminate\Support\Facades\Schema::connection($dbConn)->hasTable('rejected_schedules')) {
                DB::connection($dbConn)->table('rejected_schedules')->updateOrInsert(
                    ['schedule_id' => $schedule->id],
                    [
                        'faculty_id'       => $schedule->faculty_id,
                        'subject'          => $schedule->subject,
                        'grade_level'      => $schedule->grade_level,
                        'section_name'     => $schedule->section_name,
                        'day_of_week'      => $schedule->day_of_week,
                        'schedule_date'    => $schedule->schedule_date,
                        'start_time'       => $schedule->start_time,
                        'end_time'         => $schedule->end_time,
                        'rejection_reason' => $reason,
                        'rejected_by'      => Auth::id(),
                        'rejected_by_name' => Auth::user()?->name,
                        'rejected_at'      => now(),
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );
            }

            ScheduleApproval::updateOrCreate(
                ['schedule_id' => $schedule->id],
                [
                    'submitted_by' => $schedule->faculty_id ?? 0,
                    'status'       => 'rejected',
                    'reviewed_by'  => Auth::id(),
                    'reviewed_at'  => now(),
                    'admin_notes'  => $reason,
                ]
            );

            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $schedule->id)
                ->delete();

            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule rejected and removed successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Schedule rejection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a schedule (admin can edit approved schedules)
     */
    public function update(Request $request, ClassSchedule $schedule)
    {
        // Check if user is admin
        $userRole = Auth::user()->role?->name;
        if ($userRole !== 'admin' && strpos($userRole, 'admin') === false) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'grade_level'  => 'sometimes|string|max:20',
            'section_name' => 'sometimes|string|max:30',
            'day_of_week' => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedule_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'student_count' => 'sometimes|nullable|integer|min:1',
            'status' => 'sometimes|in:pending,active,completed',
        ]);

        ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

        $facultyId = (int) ($validated['faculty_id'] ?? $schedule->faculty_id);
        $dayOfWeek = $validated['day_of_week'] ?? $schedule->day_of_week;
        $startTime = $validated['start_time'] ?? $schedule->start_time;
        $sectionName = $validated['section_name'] ?? $schedule->section_name;
        $subject = $validated['subject'] ?? $schedule->subject;
        $gradeLevel = $validated['grade_level'] ?? $schedule->grade_level;

        $dupMsg = \App\Support\DuplicateSubmissionSupport::scheduleDuplicateMessage(
            $facultyId,
            (string) $dayOfWeek,
            (string) $startTime,
            (string) $sectionName,
            (string) $subject,
            $gradeLevel ? (string) $gradeLevel : null,
            (int) $schedule->id
        );
        if ($dupMsg !== null) {
            return response()->json(['success' => false, 'message' => $dupMsg], 409);
        }

        // Store original data for change log
        $changes = ScheduleAudit::collectChanges($schedule, $validated);
        
        // Increment version
        $validated['version'] = $schedule->version + 1;
        $validated['last_modified_by_admin'] = now();

        // Create change log entry
        $validated['change_log'] = ScheduleAudit::appendChangeLog($schedule->change_log, 'updated', Auth::user()?->name, [
            'changes' => $changes,
            'details' => empty($changes) ? 'Schedule reviewed with no field changes' : null,
        ]);

        $schedule->update($validated);

        $schedule->setRelation('faculty', $schedule->faculty_id ? User::find($schedule->faculty_id) : null);
        $schedule->setRelation('room', $schedule->room_id ? Room::find($schedule->room_id) : null);
        $schedule->setRelation('approver', $schedule->approved_by ? User::find($schedule->approved_by) : null);
        $schedule->setAttribute('approved_by_name', $schedule->approver?->name);
        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Delete a schedule (admin action)
     */
    public function destroy(Request $request, ClassSchedule $schedule)
    {
        $role = Auth::user()?->role?->name;
        if (! in_array($role, ['admin', 'admin_junior_high', 'admin_grade_school', 'principal'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

        try {
            // Capture data before deleting for related cleanup
            $facultyId   = $schedule->faculty_id;
            $dayOfWeek   = $schedule->day_of_week;
            $startTime   = $schedule->start_time;
            $endTime     = $schedule->end_time;
            $gradeLevel  = $schedule->grade_level;
            $sectionName = $schedule->section_name;
            $scheduleId  = $schedule->id;
            $dbConn      = $schedule->getConnectionName();

            // Delete related master weekly schedule entries for the same teacher and slot
            $masterQuery = \App\Models\MasterWeeklySchedule::where('faculty_id', $facultyId);
            if ($dayOfWeek)   { $masterQuery->where('day_of_week', $dayOfWeek); }
            if ($startTime)   { $masterQuery->where('time_start', substr($startTime, 0, 5)); }
            if ($endTime)     { $masterQuery->where('time_end', substr($endTime, 0, 5)); }
            if ($gradeLevel)  { $masterQuery->where('grade_level', $gradeLevel); }
            if ($sectionName) { $masterQuery->where('section_name', $sectionName); }
            $masterQuery->delete();

            // Remove from pending_schedules view
            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();

            // Remove from schedule_approvals
            try {
                DB::connection($dbConn)->table('schedule_approvals')
                    ->where('schedule_id', $scheduleId)
                    ->delete();
            } catch (\Exception $ignored) {}

            // Physically delete the schedule record
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting schedule: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'schedule_id' => $schedule->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting schedule: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get change history for a schedule
     */
    public function getHistory(ClassSchedule $schedule)
    {
        $faculty  = $schedule->faculty_id  ? User::find($schedule->faculty_id)  : null;
        $approver = $schedule->approved_by ? User::find($schedule->approved_by) : null;
        return response()->json([
            'schedule_id'      => $schedule->id,
            'version'          => $schedule->version,
            'created_at'       => $schedule->created_at,
            'created_by'       => $faculty?->name,
            'approved_at'      => $schedule->approved_at,
            'approved_by'      => $approver?->name,
            'last_modified_at' => $schedule->last_modified_by_admin,
            'change_log'       => json_decode($schedule->change_log, true) ?? [],
        ]);
    }

    /**
     * Get pending schedules for admin review
     */
    public function getPendingSchedules()
    {
        $schedules = ClassSchedule::where('admin_approved', false)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $roomIds = $schedules->pluck('room_id')->filter()->unique();
        $rooms   = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
        $schedules->each(function($s) use ($users, $rooms) {
            $s->setRelation('faculty', $users[$s->faculty_id] ?? null);
            $s->setRelation('room',    $rooms[$s->room_id]    ?? null);
        });

        return response()->json(['data' => $schedules]);
    }

    /**
     * Resolve the homeroom for a grade level + section from existing schedules or room name.
     */
    public function getRoomForSection(Request $request)
    {
        $request->validate([
            'grade_level'   => 'required|string|max:50',
            'section_name'  => 'required|string|max:50',
        ]);

        $grade   = trim($request->query('grade_level'));
        $section = trim($request->query('section_name'));

        $schedule = ClassSchedule::query()
            ->where('grade_level', $grade)
            ->where('section_name', $section)
            ->whereNotNull('room_id')
            ->orderByDesc('admin_approved')
            ->orderByDesc('id')
            ->first();

        if (!$schedule) {
            $schedule = ClassSchedule::query()
                ->where('grade_level', $grade)
                ->where('section_name', $section)
                ->whereNotNull('room_id')
                ->orderByDesc('id')
                ->first();
        }

        $room = null;
        if ($schedule?->room_id) {
            $room = Room::find($schedule->room_id);
        }

        if (!$room) {
            $room = Room::query()
                ->where(function ($q) use ($section) {
                    $q->where('room_number', $section)
                        ->orWhere('room_number', 'like', $section . '%');
                })
                ->orderBy('id')
                ->first();
        }

        if (!$room) {
            return response()->json([
                'success' => true,
                'room_id' => null,
                'label'   => 'No room assigned for this section yet',
            ]);
        }

        $label = $room->room_number . ($room->building ? ' – ' . $room->building : '');

        return response()->json([
            'success' => true,
            'room_id' => $room->id,
            'label'   => $label,
            'source'  => $schedule ? 'schedule' : 'room_name',
        ]);
    }

    /**
     * Return rooms not booked during the requested day/time slot.
     * GET api/admin/available-rooms?day=Monday&start_time=08:00&end_time=09:00
     */
    public function getAvailableRooms(Request $request)
    {
        $request->validate([
            'day'        => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
        ]);

        $day       = $request->query('day');
        $startTime = $request->query('start_time');
        $endTime   = $request->query('end_time');

        $rooms = Room::where('status', 'available')->orderBy('room_number')->get();

        $hasRoomIdColumn = Schema::connection((new ClassSchedule)->getConnectionName())
            ->hasColumn('class_schedules', 'room_id');

        if ($hasRoomIdColumn && $day && $startTime && $endTime && $startTime < $endTime) {
            // Overlap check: existing.start_time < requested.end AND existing.end_time > requested.start
            $bookedRoomIds = ClassSchedule::whereIn('status', ['active', 'pending'])
                ->where('day_of_week', $day)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->whereNotNull('room_id')
                ->pluck('room_id')
                ->unique()
                ->toArray();

            $rooms = $rooms->filter(fn($room) => !in_array($room->id, $bookedRoomIds))->values();
        }

        return response()->json([
            'available' => $rooms->map(fn($r) => [
                'id'          => $r->id,
                'label'       => $r->room_number
                    . ($r->building ? ' - ' . $r->building : '')
                    . ' (Capacity: ' . $r->capacity . ')',
                'capacity'    => (int) $r->capacity,
                'room_number' => $r->room_number,
                'building'    => $r->building,
            ]),
            'total' => $rooms->count(),
        ]);
    }

    /**
     * Get scheduling conflicts summary for stat card display
     */
    public function getConflictsSummary()
    {
        try {
            // Get schedules with conflict status or duplicate checks
            $conflictingSchedules = ClassSchedule::whereIn('status', ['conflict', 'duplicate'])
                ->orWhere(function($q) {
                    // Find teachers with overlapping time slots
                    $q->where('admin_approved', true)
                      ->where('status', 'active');
                })
                ->get();

            $conflictCount = ClassSchedule::where('status', 'conflict')->count();
            $duplicateCount = ClassSchedule::where('status', 'duplicate')->count();

            $hasConflicts = $conflictCount > 0 || $duplicateCount > 0;
            
            $message = $hasConflicts
                ? "⚠ {$conflictCount} scheduling conflict(s) and {$duplicateCount} duplicate(s) detected."
                : "✓ Schedule is clear. No conflicts or issues detected by system analysis.";

            return response()->json([
                'has_conflicts' => $hasConflicts,
                'conflict_count' => $conflictCount,
                'duplicate_count' => $duplicateCount,
                'message' => $message,
                'details' => [
                    'conflicts' => ClassSchedule::where('status', 'conflict')->get(),
                    'duplicates' => ClassSchedule::where('status', 'duplicate')->get(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting conflicts summary: ' . $e->getMessage());
            return response()->json([
                'has_conflicts' => false,
                'conflict_count' => 0,
                'duplicate_count' => 0,
                'message' => '✓ Schedule is clear. No conflicts or issues detected by system analysis.'
            ]);
        }
    }

    /**
     * Get teachers filtered by grade level and subject from faculty loads
     * Used to populate teacher dropdown in create schedules form
     */
    public function getTeachersByGradeAndSubject(Request $request)
    {
        try {
            $gradeLevel = $request->query('grade_level');
            $subject = $request->query('subject');

            if (!$gradeLevel && !$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade level or subject is required'
                ], 400);
            }

            // Get faculty loads matching the criteria
            $query = FacultyLoad::query();

            if ($gradeLevel) {
                $query->where('grade_level', $gradeLevel);
            }

            $facultyLoads = $query->get();

            if ($subject) {
                $needle = strtolower(trim($subject));
                $facultyLoads = $facultyLoads->filter(function ($load) use ($needle) {
                    $parts = array_map('trim', explode(',', (string) ($load->subject ?? '')));
                    foreach ($parts as $part) {
                        if ($part !== '' && (strtolower($part) === $needle || str_contains(strtolower($part), $needle))) {
                            return true;
                        }
                    }
                    return false;
                });
            }
            $teacherIds = $facultyLoads->pluck('faculty_id')->unique()->toArray();

            if (empty($teacherIds)) {
                return response()->json([
                    'success' => true,
                    'teachers' => [],
                    'message' => 'No teachers available for the selected criteria'
                ]);
            }

            // Get user details for these teachers
            $teachers = User::whereIn('id', $teacherIds)
                ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => trim($u->first_name . ' ' . $u->last_name) ?: $u->name,
                    'email' => $u->email,
                ]);

            return response()->json([
                'success' => true,
                'teachers' => $teachers,
                'count' => $teachers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting teachers by grade/subject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a schedule already exists (duplicate detection)
     */
    public function checkDuplicate(Request $request)
    {
        try {
            $request->validate([
                'faculty_id' => 'required|exists:users,id',
                'grade_level' => 'required|string',
                'section_name' => 'required|string',
                'day_of_week' => 'required|string',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
            ]);

            $duplicate = ClassSchedule::where('faculty_id', $request->faculty_id)
                ->where('grade_level', $request->grade_level)
                ->where('section_name', $request->section_name)
                ->where('day_of_week', $request->day_of_week)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->where('status', '!=', 'deleted')
                ->where('admin_approved', true)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'exists' => true,
                    'message' => 'This schedule already exists.',
                    'schedule' => $duplicate
                ]);
            }

            return response()->json([
                'exists' => false,
                'message' => 'Schedule is unique'
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking duplicate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking duplicate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get faculty load availability status
     * Checks if teacher is available (not having class) based on class_schedules
     */
    public function getFacultyLoadStatus(Request $request)
    {
        try {
            $facultyLoadId = $request->query('faculty_load_id');
            $facultyId = $request->query('faculty_id');

            if (!$facultyLoadId && !$facultyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty load ID or faculty ID is required'
                ], 400);
            }

            if ($facultyLoadId) {
                $load = FacultyLoad::findOrFail($facultyLoadId);
                $facultyId = $load->faculty_id;
            }

            // Check if teacher has any active class schedule at this moment
            $currentTime = now()->format('H:i');
            $currentDay = now()->format('l'); // e.g., 'Monday'

            $activeSchedule = ClassSchedule::where('faculty_id', $facultyId)
                ->where('day_of_week', $currentDay)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>', $currentTime)
                ->where('admin_approved', true)
                ->where('status', 'active')
                ->first();

            $status = $activeSchedule ? 'not_available' : 'available';

            return response()->json([
                'success' => true,
                'faculty_id' => $facultyId,
                'status' => $status,
                'is_available' => $status === 'available',
                'current_class' => $activeSchedule ? [
                    'subject' => $activeSchedule->subject,
                    'grade_level' => $activeSchedule->grade_level,
                    'section_name' => $activeSchedule->section_name,
                    'end_time' => $activeSchedule->end_time
                ] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting faculty load status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting status: ' . $e->getMessage()
            ], 500);
        }
    }
}
