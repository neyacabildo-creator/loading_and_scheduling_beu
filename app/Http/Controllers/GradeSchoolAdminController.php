<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ClassSchedule;
use App\Models\ScheduleApproval;
use App\Models\User;
use App\Models\Room;
use App\Models\Role;
use App\Models\FacultyLoad;
use App\Models\ExportLog;
use App\Models\GeneratedReport;
use App\Support\FacultyLoadSupport;
use App\Support\ScheduleDisplaySupport;
use App\Support\ScheduleFormSupport;
use App\Support\ScheduleAudit;
use App\Support\ScheduleUpdateHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class GradeSchoolAdminController extends Controller
{
    private const REPORT_TYPES = [
        'master_loading_summary',
        'complete_schedule_listing',
        'workload_analytics',
        'compliance_report',
    ];

    /**
     * Get the authenticated admin's school level (Grade School)
     */
    private function getAdminSchoolLevel() {
        return 'grade_school';
    }

    /**
     * Display the Grade School admin dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();

            // Fetch Grade School data only
            $totalFaculty = User::where('school_level', $schoolLevel)
                ->whereHas('role', function($q) {
                    $q->where('name', 'like', '%teacher%');
                })->count();

            $totalClasses = ClassSchedule::whereNotIn('status', ['deleted', 'rejected'])->count();
            $approvedSchedules = ClassSchedule::where('admin_approved', true)->whereNotIn('status', ['deleted', 'rejected'])->count();
            $pendingApprovals = ClassSchedule::where(function($q) {
                $q->where('admin_approved', false)->orWhereNull('admin_approved');
            })->whereNotIn('status', ['deleted', 'rejected'])->count();

            $totalRooms = Room::count();
            
            $totalLoadHours = (float) FacultyLoad::sum('load_hours');

            $schedulingConflicts = 0;
            try {
                $activeSchedules = ClassSchedule::where('admin_approved', true)
                    ->whereNotIn('status', ['deleted', 'rejected'])
                    ->whereNotNull('faculty_id')
                    ->get(['id', 'faculty_id', 'day_of_week', 'start_time', 'end_time', 'status']);

                $conflictIds = collect();
                $activeSchedules
                    ->groupBy('faculty_id')
                    ->each(function ($group) use ($conflictIds) {
                        $sorted = $group->sortBy(fn($s) => $s->start_time)->values();
                        $count = $sorted->count();
                        for ($i = 0; $i < $count; $i++) {
                            for ($j = $i + 1; $j < $count; $j++) {
                                $a = $sorted[$i];
                                $b = $sorted[$j];
                                if (!$a->day_of_week || !$b->day_of_week) {
                                    continue;
                                }
                                if (strcasecmp($a->day_of_week, $b->day_of_week) !== 0) {
                                    continue;
                                }
                                if (!$a->start_time || !$a->end_time || !$b->start_time || !$b->end_time) {
                                    continue;
                                }
                                if ($a->start_time < $b->end_time && $b->start_time < $a->end_time) {
                                    $conflictIds->push($a->id, $b->id);
                                }
                            }
                        }
                    });

                $statusConflictIds = ClassSchedule::where('status', 'conflict')
                    ->whereNotIn('status', ['deleted', 'rejected'])
                    ->pluck('id');

                $schedulingConflicts = $conflictIds
                    ->merge($statusConflictIds)
                    ->unique()
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Error calculating scheduling conflicts: ' . $e->getMessage());
                $schedulingConflicts = ClassSchedule::where('status', 'conflict')->count();
            }

            $totalFacultyLoads    = FacultyLoad::count();
            $activeFacultyLoads   = FacultyLoad::where('status', 'active')->count();
            $availableFacultyLoads = FacultyLoad::whereIn('status', ['part-time', 'available'])->count();
            $overloadFacultyLoads = FacultyLoad::whereIn('status', ['overload', 'overloaded'])->count();

            $totalRoomsCount  = Room::count();
            $availableRooms   = Room::where('status', 'available')->count();
            $inUseRooms       = Room::where('status', 'in-use')->count();
            $maintenanceRooms = Room::where('status', 'maintenance')->count();

            $totalTeachers    = User::where('school_level', $schoolLevel)
                ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
                ->count();
            $activeTeachers   = User::where('school_level', $schoolLevel)
                ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
                ->where('is_active', true)->count();
            $inactiveTeachers = User::where('school_level', $schoolLevel)
                ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
                ->where('is_active', false)->count();

            // Shared teacher requests stats (GS DB)
            try {
                $stReqTotal    = DB::connection('mysql_gs')->table('shared_teacher_requests')->count();
                $stReqPending  = DB::connection('mysql_gs')->table('shared_teacher_requests')->where('status','pending')->count();
                $stReqApproved = DB::connection('mysql_gs')->table('shared_teacher_requests')->where('status','approved')->count();
                $stReqRejected = DB::connection('mysql_gs')->table('shared_teacher_requests')->where('status','rejected')->count();
            } catch (\Exception $e) {
                $stReqTotal = $stReqPending = $stReqApproved = $stReqRejected = 0;
            }

            $timetableSchedules = array_values(array_filter(
                \App\Support\CombinedScheduleService::fetchApproved(),
                fn ($s) => ($s['school'] ?? '') === 'GS'
            ));

            $sharedTeacherIds = DB::connection('mysql_gs')->table('shared_teachers')
                ->where('is_active', true)->pluck('faculty_id')->map(fn ($id) => (int) $id)->all();
            $leaveBanner = \App\Support\TeacherPresenceSupport::collectActiveLeaveBannerData('mysql_gs', $sharedTeacherIds);

            return view('grade-school-admin.dashboard', [
                'leaveBanner' => $leaveBanner,
                'timetableSchedules'    => $timetableSchedules,
                'totalFaculty'          => $totalFaculty,
                'totalClasses'          => $totalClasses,
                'approvedSchedules'     => $approvedSchedules,
                'pendingApprovals'      => $pendingApprovals,
                'totalRooms'            => $totalRooms,
                'totalLoadHours'        => $totalLoadHours,
                'schedulingConflicts'   => $schedulingConflicts,
                'totalFacultyLoads'     => $totalFacultyLoads,
                'activeFacultyLoads'    => $activeFacultyLoads,
                'availableFacultyLoads' => $availableFacultyLoads,
                'overloadFacultyLoads'  => $overloadFacultyLoads,
                'totalRoomsCount'       => $totalRoomsCount,
                'availableRooms'        => $availableRooms,
                'inUseRooms'            => $inUseRooms,
                'maintenanceRooms'      => $maintenanceRooms,
                'totalTeachers'         => $totalTeachers,
                'activeTeachers'        => $activeTeachers,
                'inactiveTeachers'      => $inactiveTeachers,
                'schoolLevel'           => $schoolLevel,
                // Shared teacher requests
                'stReqTotal'            => $stReqTotal,
                'stReqPending'          => $stReqPending,
                'stReqApproved'         => $stReqApproved,
                'stReqRejected'         => $stReqRejected,
            ]);
        } catch (\Exception $e) {
            Log::error('Grade School Admin Dashboard Error: ' . $e->getMessage());
            return response('<div style="font-family:sans-serif;padding:2rem;"><h2>Dashboard Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><a href="/logout">Logout</a></div>', 500);
        }
    }

    /**
     * Get all schedules (Grade School only)
     */
    public function getSchedules(\Illuminate\Http\Request $request) {
        try {
            $query = ClassSchedule::query();
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', (int) $request->faculty_id);
            }
            $schedules = $query->get();

            // Explicitly load related users from the default (shared) connection
            $userIds = $schedules->pluck('faculty_id')
                ->merge($schedules->pluck('approved_by'))
                ->filter()->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            // Rooms are in mysql_gs, already queried on correct connection
            $roomIds = $schedules->pluck('room_id')->filter()->unique();
            $rooms = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();

            $result = $schedules->map(function ($s) use ($users, $rooms) {
                $data = ScheduleDisplaySupport::enrichForApi(
                    $s->toArray(),
                    $s,
                    isset($rooms[$s->room_id]) ? $rooms[$s->room_id] : null,
                    isset($users[$s->faculty_id]) ? $users[$s->faculty_id] : null
                );
                $data['schedule_date'] = $s->getRawOriginal('schedule_date');
                $data['display_date'] = ScheduleDisplaySupport::formatScheduleDate($data['schedule_date']);
                if (isset($rooms[$s->room_id])) {
                    $data['room'] = $rooms[$s->room_id]->toArray();
                }
                $data['approver'] = isset($users[$s->approved_by])
                    ? ['id' => $s->approved_by, 'name' => $users[$s->approved_by]->name]
                    : null;
                $data['approved_by_name'] = ScheduleAudit::approverName($s->approved_by, $users);

                return $data;
            })->values();

            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('GS getSchedules: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => 'Error loading schedules'], 500);
        }
    }

    /**
     * Get combined GS + JH schedules (GS schedules + shared teachers' JH schedules)
     * Used by the timetable to show a unified view across both schools.
     */
    public function getCombinedSchedules(\Illuminate\Http\Request $request) {
        try {
            $data = collect(\App\Support\CombinedScheduleService::fetchApproved())
                ->filter(fn ($s) => ($s['school'] ?? '') === 'GS')
                ->values()
                ->all();

            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            Log::error('GS getCombinedSchedules: ' . $e->getMessage());

            return response()->json(['data' => []], 500);
        }
    }

    /**
     * Get a single schedule by ID (Grade School only)
     */
    public function getSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $room = $schedule->room_id ? Room::find($schedule->room_id) : null;
            $faculty = $schedule->faculty_id ? User::find($schedule->faculty_id) : null;
            $data = ScheduleDisplaySupport::enrichForApi($schedule->toArray(), $schedule, $room, $faculty);
            $data['schedule_date'] = $schedule->getRawOriginal('schedule_date');
            $data['display_date'] = ScheduleDisplaySupport::formatScheduleDate($data['schedule_date']);
            $data['faculty'] = $faculty ? ['id' => $faculty->id, 'name' => $faculty->name] : null;
            $data['room'] = $room?->toArray();
            $approver = $schedule->approved_by ? User::find($schedule->approved_by) : null;
            $data['approver'] = $approver ? ['id' => $approver->id, 'name' => $approver->name] : null;
            $data['approved_by_name'] = $approver?->name;

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }
    }

    /**
     * Get schedule change history (Grade School only)
     */
    public function getScheduleHistory($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $changeLog = json_decode($schedule->change_log, true) ?? [];
            return response()->json(['success' => true, 'change_log' => $changeLog]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }
    }

    /**
     * Approve a schedule (Grade School only)
     */
    public function approveSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

            // ── Conflict detection before approving ──────────────────────────────
            if ($schedule->faculty_id && $schedule->day_of_week && $schedule->start_time) {
                $existingConflict = ClassSchedule::where('faculty_id', $schedule->faculty_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('start_time', $schedule->start_time)
                    ->where('admin_approved', true)
                    ->where('id', '!=', $schedule->id)
                    ->first();
                if ($existingConflict) {
                    $teacher = User::find($schedule->faculty_id);
                    $name = $teacher
                        ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name)
                        : "Teacher #{$schedule->faculty_id}";
                    $schedule->update(['status' => 'conflict']);
                    return response()->json([
                        'success'  => false,
                        'conflict' => true,
                        'message'  => "⚠ Conflict: {$name} is already approved for {$existingConflict->grade_level} – {$existingConflict->section_name} at "
                                      . substr($schedule->start_time, 0, 5) . " on {$schedule->day_of_week}. Resolve the existing schedule first.",
                    ], 409);
                }
                // Cross-school check for shared teachers (JH schedules)
                $isShared = (new \App\Models\SharedTeacher)->setConnection('mysql_gs')
                    ->newQuery()->where('faculty_id', $schedule->faculty_id)->where('is_active', true)->exists();
                if ($isShared) {
                    $crossConflict = DB::connection('mysql_jh')
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
                            'message'  => "⚠ Conflict: {$name} (shared teacher) already has an approved Junior High schedule at "
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

            $schedule->update([
                'admin_approved' => true,
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'change_log'  => ScheduleAudit::appendChangeLog($schedule->change_log, 'approved', Auth::user()?->name),
                'last_modified_by_admin' => now(),
            ]);
            ScheduleApproval::updateOrCreate(
                ['schedule_id' => $schedule->id],
                ['submitted_by' => $schedule->faculty_id ?? 0, 'status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]
            );

            DB::connection($schedule->getConnectionName())->table('pending_schedules')
                ->where('schedule_id', $schedule->id)
                ->delete();

            return response()->json(['success' => true, 'message' => 'Schedule approved successfully', 'data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error approving schedule'], 400);
        }
    }

    /**
     * Reject a pending schedule: archive reason, then remove from class_schedules.
     */
    public function rejectSchedule(Request $request, $id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $reason   = $request->input('reason', 'No reason provided');
            $dbConn   = $schedule->getConnectionName();

            ScheduleAudit::setAuditUser($dbConn, Auth::user()?->name);

            if (Schema::connection($dbConn)->hasTable('rejected_schedules')) {
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

            \App\Models\MasterWeeklySchedule::where('faculty_id', $schedule->faculty_id)
                ->when($schedule->day_of_week, fn ($q) => $q->where('day_of_week', $schedule->day_of_week))
                ->when($schedule->start_time, fn ($q) => $q->where('time_start', substr($schedule->start_time, 0, 5)))
                ->when($schedule->end_time, fn ($q) => $q->where('time_end', substr($schedule->end_time, 0, 5)))
                ->when($schedule->grade_level, fn ($q) => $q->where('grade_level', $schedule->grade_level))
                ->when($schedule->section_name, fn ($q) => $q->where('section_name', $schedule->section_name))
                ->delete();

            $schedule->delete();

            return response()->json(['success' => true, 'message' => 'Schedule rejected and removed successfully']);
        } catch (\Exception $e) {
            Log::error('GS rejectSchedule: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error rejecting schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Update schedule (Grade School only)
     */
    public function updateSchedule(Request $request, $id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);
            ScheduleUpdateHelper::mergeNormalizedInput($request);
            $validated = $request->validate(ScheduleUpdateHelper::validationRules());

            $facultyId = (int) ($validated['faculty_id'] ?? $schedule->faculty_id);
            $dupMsg = \App\Support\DuplicateSubmissionSupport::scheduleDuplicateMessage(
                $facultyId,
                (string) ($validated['day_of_week'] ?? $schedule->day_of_week),
                (string) ($validated['start_time'] ?? $schedule->start_time),
                (string) ($validated['section_name'] ?? $schedule->section_name),
                (string) ($validated['subject'] ?? $schedule->subject),
                isset($validated['grade_level']) ? (string) $validated['grade_level'] : (string) $schedule->grade_level,
                (int) $schedule->id
            );
            if ($dupMsg !== null) {
                return response()->json(['success' => false, 'message' => $dupMsg], 409);
            }

            $changes = ScheduleAudit::collectChanges($schedule, $validated);
            $validated['version'] = (int) $schedule->version + 1;
            $validated['last_modified_by_admin'] = now();
            $validated['change_log'] = ScheduleAudit::appendChangeLog($schedule->change_log, 'updated', Auth::user()?->name, [
                'changes' => $changes,
                'details' => empty($changes) ? 'Schedule reviewed with no field changes' : null,
            ]);

            $schedule->update($validated);
            $schedule->setRelation('faculty', $schedule->faculty_id ? User::find($schedule->faculty_id) : null);
            $schedule->setRelation('room',    $schedule->room_id    ? Room::find($schedule->room_id)    : null);
            $schedule->setRelation('approver', $schedule->approved_by ? User::find($schedule->approved_by) : null);
            $schedule->setAttribute('approved_by_name', $schedule->approver?->name);
            return response()->json(['success' => true, 'message' => 'Schedule updated', 'data' => $schedule]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('GS updateSchedule: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error updating schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete schedule (Grade School only)
     */
    public function deleteSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

            $scheduleId = $schedule->id;
            $dbConn = $schedule->getConnectionName();

            // Remove related weekly schedule rows
            \App\Models\MasterWeeklySchedule::where('faculty_id', $schedule->faculty_id)
                ->when($schedule->day_of_week, fn($q) => $q->where('day_of_week', $schedule->day_of_week))
                ->when($schedule->start_time, fn($q) => $q->where('time_start', substr($schedule->start_time, 0, 5)))
                ->when($schedule->end_time, fn($q) => $q->where('time_end', substr($schedule->end_time, 0, 5)))
                ->when($schedule->grade_level, fn($q) => $q->where('grade_level', $schedule->grade_level))
                ->when($schedule->section_name, fn($q) => $q->where('section_name', $schedule->section_name))
                ->delete();

            // Delete from pending_schedules
            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();

            // Delete from schedule_approvals
            try {
                DB::connection($dbConn)->table('schedule_approvals')
                    ->where('schedule_id', $scheduleId)
                    ->delete();
            } catch (\Exception $ignored) {}

            // Physically delete the schedule record
            $schedule->delete();

            return response()->json(['success' => true, 'message' => 'Schedule deleted successfully']);
        } catch (\Exception $e) {
            Log::error('GS deleteSchedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting schedule'], 400);
        }
    }

    /**
     * Add a new teacher (Grade School only)
     */
    public function addTeacher(Request $request) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users',
                'position' => 'nullable|string|max:255',
            ]);

            $teacherRole = Role::where('name', 'teacher_grade_school')->first();

            if (!$teacherRole) {
                return response()->json(['success' => false, 'message' => 'Teacher role not found'], 400);
            }

            $user = User::create([
                'name' => $validated['name'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'position' => $validated['position'] ?? null,
                'school_level' => $schoolLevel,
                'password' => bcrypt('password'),
                'role_id' => $teacherRole->id,
                'is_active' => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Teacher added', 'data' => $user], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('GS addTeacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error adding teacher'], 400);
        }
    }

    /**
     * All teachers in User Accounts for Grade School.
     * Creating faculty loads does not remove or hide accounts from this list.
     */
    public function getTeacherAssignedSubjects(int $id)
    {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, $id);
            $subjects = ($user->role?->name === 'shared_teacher')
                ? \App\Support\SharedTeacherSupport::assignedSubjectsForFaculty('mysql_gs', $user->id, $schoolLevel)
                : [];

            return response()->json(['success' => true, 'subjects' => $subjects]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            Log::error('GS getTeacherAssignedSubjects: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error loading subjects'], 400);
        }
    }

    public function getTeachers(Request $request) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $forFacultyLoad = $request->boolean('faculty_only')
                || $request->query('context') === 'faculty';

            $query = $forFacultyLoad
                ? \App\Support\AdminUserAccountsSupport::scopeFacultyAssignable(User::query(), $schoolLevel)
                : \App\Support\AdminUserAccountsSupport::scopeUserAccounts(User::query(), $schoolLevel);

            $users = $query->with('role')->orderBy('first_name')->orderBy('last_name')->get();
            $teachers = $forFacultyLoad
                ? \App\Support\AdminUserAccountsSupport::mapUsersForFacultyApi($users, $schoolLevel)
                : \App\Support\AdminUserAccountsSupport::mapUsersForApi($users);

            return response()->json([
                'success' => true,
                'data' => $teachers,
                'count' => count($teachers),
                'school_level' => $schoolLevel
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching teachers'], 400);
        }
    }

    /**
     * Update teacher (Grade School only)
     */
    public function updateTeacher(Request $request, $id) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:100|unique:users,email,' . $id,
            ]);

            $validated = \App\Support\AdminUserAccountsSupport::withNormalizedNames($validated);
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, (int) $id);
            $user->update($validated);
            return response()->json(['success' => true, 'message' => 'Teacher updated', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating teacher'], 400);
        }
    }

    /**
     * Delete teacher (Grade School only)
     */
    public function deleteTeacher($id) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, (int) $id);
            \App\Support\UserSchoolDataPurge::purge($user);
            $user->delete();

            return response()->json(['success' => true, 'message' => 'Teacher deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting teacher'], 400);
        }
    }

    /**
     * Toggle teacher active/inactive status (Grade School only)
     */
    public function toggleTeacherActive($id) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, (int) $id);
            $user->is_active = !$user->is_active;
            $user->save();
            $status = $user->is_active ? 'activated' : 'deactivated';
            return response()->json(['success' => true, 'message' => "Account {$status} successfully", 'is_active' => $user->is_active]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating account status'], 400);
        }
    }

    /**
     * Add a new room (Grade School only)
     */
    public function addRoom(Request $request) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $validated = $request->validate([
                'capacity' => 'required|integer|min:1|max:200',
                'status'   => 'required|in:available,in-use,maintenance',
            ]);

            $room = Room::create($validated);
            return response()->json(['success' => true, 'message' => 'Room added', 'data' => $room], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error adding room'], 400);
        }
    }

    /**
     * Get all rooms (Grade School only)
     */
    public function getRooms() {
        try {
            $rooms = Room::all();
            return response()->json([
                'success' => true,
                'data' => $rooms,
                'count' => $rooms->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching rooms'], 400);
        }
    }

    /**
     * Update room (Grade School only)
     */
    public function updateRoom(Request $request, $id) {
        try {
            $validated = $request->validate([
                'capacity' => 'nullable|integer|min:1|max:200',
                'status'   => 'nullable|in:available,in-use,maintenance',
            ]);

            $room = Room::findOrFail($id);
            $room->update($validated);
            return response()->json(['success' => true, 'message' => 'Room updated', 'data' => $room]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating room'], 400);
        }
    }

    /**
     * Delete room (Grade School only)
     */
    public function deleteRoom($id) {
        try {
            $room = Room::findOrFail($id);
            $room->delete();
            return response()->json(['success' => true, 'message' => 'Room deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting room'], 400);
        }
    }

    /**
     * Show class schedule page
     */
    public function classSchedule() {
        return view('grade-school-admin.class-schedule');
    }

    /**
     * Show faculty loading page
     */
    public function facultyLoading() {
        $totalFaculty    = \App\Support\AdminUserAccountsSupport::scopeFacultyAssignable(User::query(), 'grade_school')->count();
        $totalClasses    = FacultyLoad::sum('classes_assigned') ?? 0;
        $totalLoadHours  = FacultyLoad::sum('load_hours') ?? 0;
        $avgLoad         = $totalFaculty > 0 ? round($totalLoadHours / $totalFaculty, 2) : 0;
        // Count teachers who have any day with >5 approved subjects (daily limit = 5)
        $overloaded = ClassSchedule::selectRaw('faculty_id')
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->whereNotNull('faculty_id')
            ->groupBy('faculty_id', 'day_of_week')
            ->havingRaw('COUNT(*) > 5')
            ->get()
            ->pluck('faculty_id')
            ->unique()
            ->count();

        $teachers = \App\Support\AdminUserAccountsSupport::scopeFacultyAssignable(User::query(), 'grade_school')
            ->with('role')
            ->orderBy('first_name')
            ->get();

        $sharedTeacherSubjectsMap = \App\Support\SharedTeacherSupport::subjectsMapForFacultyIds(
            'mysql_gs',
            $teachers->pluck('id')->all(),
            'grade_school'
        );

        // Shared teacher user IDs for badge rendering
        $sharedTeacherUserIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
            ->pluck('id')->map(fn($id) => (string) $id)->toArray();

        // Subjects: from class_schedules + sensible defaults
        $defaultSubjects = ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Computer Education','Edukasyon sa Pagpapakatao','Mother Tongue','Reading','Values Education'];
        $dbSubjects = ClassSchedule::distinct()->pluck('subject')->filter()->values()->toArray();
        $subjects   = collect(array_unique(array_merge($dbSubjects, $defaultSubjects)))->sort()->values()->toArray();

        $sharedTeacherIds = DB::connection('mysql_gs')->table('shared_teachers')
            ->where('is_active', true)->pluck('faculty_id')->map(fn ($id) => (int) $id)->all();
        $leaveBanner = \App\Support\TeacherPresenceSupport::collectActiveLeaveBannerData('mysql_gs', $sharedTeacherIds);

        return view('grade-school-admin.faculty-loading', compact(
            'totalFaculty', 'totalClasses', 'avgLoad', 'overloaded', 'teachers', 'subjects',
            'sharedTeacherUserIds', 'sharedTeacherSubjectsMap', 'leaveBanner'
        ));
    }

    /**
     * Get all faculty loads (Grade School only)
     */
    public function getFacultyLoads(Request $request) {
        try {
            $query = FacultyLoad::query();
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', (int) $request->faculty_id);
            }
            $loads = $query->get();

            // Explicitly load faculty users from the shared (default) connection
            $userIds = $loads->pluck('faculty_id')->filter()->unique();
            $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

            // Collect shared-teacher user IDs for badge rendering
            $sharedTeacherIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
                ->pluck('id')->map(fn($id) => (string) $id)->flip()->toArray();

            // Pre-fetch approved schedules for all faculty IDs to compute live stats
            $allFacultyIds = $loads->pluck('faculty_id')->filter()->unique()->values()->toArray();
            $today = now()->format('l'); // e.g. "Monday", "Tuesday"
            $approvedScheds = ClassSchedule::whereIn('faculty_id', $allFacultyIds)
                ->where('admin_approved', true)
                ->where('status', 'active')
                ->get(['faculty_id', 'subject', 'section_name', 'day_of_week', 'start_time', 'end_time'])
                ->groupBy('faculty_id');

            $result = $loads->map(function ($load) use ($users, $sharedTeacherIds, $approvedScheds, $today) {
                $data = $load->toArray();
                $user = $users[$load->faculty_id] ?? null;
                $data['faculty'] = $user
                    ? ['id' => $load->faculty_id, 'name' => $user->name, 'email' => $user->email]
                    : null;
                if (empty($data['teacher_name']) && $user) {
                    $data['teacher_name'] = trim($user->first_name . ' ' . $user->last_name) ?: $user->name;
                }
                $data['is_shared_teacher'] = isset($sharedTeacherIds[(string) $load->faculty_id]);
                $presence = $load->faculty_id
                    ? \App\Support\TeacherPresenceSupport::activeStatusForTeacherWithDays('mysql_gs', (int) $load->faculty_id)
                    : null;
                // Compute live classes_assigned, load_hours, and status from approved schedules
                if ($load->faculty_id) {
                    $allScheds = collect($approvedScheds->get($load->faculty_id, []));

                    // Today's schedules for display stats
                    $scheds = $allScheds->filter(fn($s) => strcasecmp($s->day_of_week ?? '', $today) === 0);
                    $data['classes_assigned'] = $scheds->count();
                    $totalMinutes = $scheds->sum(function ($s) {
                        $start = strtotime($s->start_time ?? '00:00');
                        $end   = strtotime($s->end_time   ?? '00:00');
                        return max(0, ($end - $start) / 60);
                    });
                    $data['load_hours'] = round($totalMinutes / 60, 1);

                    // Detect overload: any day with >5 approved subjects
                    $dayCounts     = $allScheds->groupBy('day_of_week')->map(fn($g) => $g->count());
                    $maxDayCount   = $dayCounts->max() ?? 0;
                    $overloadedDay = $dayCounts->filter(fn($c) => $c > 5)->keys()->first();
                    $data['max_day_count']  = $maxDayCount;
                    $data['overloaded_day'] = $overloadedDay;

                    if ($maxDayCount > 5) {
                        $data['status'] = 'overloaded';
                    } elseif ($scheds->count() > 0) {
                        $data['status'] = 'not_available';
                    } else {
                        $data['status'] = 'available';
                    }

                    $sharedCount = FacultyLoadSupport::countLoadsForTeacher((int) $load->faculty_id);
                    $data['shared_load_count'] = $sharedCount;
                    $data['shared_load_conflict'] = ($data['is_shared_teacher'] ?? false)
                        && $sharedCount > FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS;
                    if ($data['shared_load_conflict']) {
                        $data['status'] = 'overloaded';
                    }

                    if ($presence) {
                        $data = \App\Support\TeacherPresenceSupport::applyPresenceToFacultyLoadRow($data, $presence);
                    }
                }

                $data['load_hours_label'] = FacultyLoadSupport::formatHoursLabel($data['load_hours'] ?? 0);
                $data['has_user_account'] = FacultyLoadSupport::facultyIdHasRegisteredAccount(
                    (int) ($load->faculty_id ?? 0),
                    'grade_school'
                );

                return $data;
            })->filter(fn ($row) => ($row['has_user_account'] ?? false)
                && ! \App\Support\FacultyLoadSupport::isAutoProvisionedPlaceholder($row))->values();

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('GS getFacultyLoads: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching faculty loads'], 400);
        }
    }

    /**
     * Add a new faculty load (Grade School only)
     */
    public function addFacultyLoad(Request $request) {
        try {
            $validated = $request->validate([
                'faculty_id'       => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::with('role')->find($value);
                        if (!$user || $user->school_level !== 'grade_school') {
                            $fail('The selected teacher must have a Grade School user account in User Accounts.');
                            return;
                        }
                        if (!$user->role || stripos($user->role->name, 'teacher') === false) {
                            $fail('The selected faculty member must be a registered teacher or instructor.');
                        }
                    },
                ],
                'subject'          => 'nullable|string|max:255',
                'grade_level'      => 'nullable|string|max:50',
                'classes_assigned' => 'nullable|integer|min:0',
                'load_hours'       => 'required|numeric|min:0',
                'status'           => 'required|in:available,unavailable,not_available',
                'notes'            => 'nullable|string|max:1000',
            ]);

            $teacher = User::find($validated['faculty_id']);
            $validated['teacher_name'] = $teacher ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name) : null;

            $dupMsg = FacultyLoadSupport::facultyLoadConflictMessage(
                (int) $validated['faculty_id'],
                $validated['teacher_name'] ?? null,
                $validated['grade_level'] ?? null,
                $validated['subject'] ?? null
            );
            if ($dupMsg !== null) {
                return response()->json(['success' => false, 'message' => $dupMsg], 409);
            }

            try {
                FacultyLoadSupport::assertFacultyLoadAccount((int) $validated['faculty_id'], 'grade_school');
                FacultyLoadSupport::assertSharedTeacherLoadLimit((int) $validated['faculty_id']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            if (($validated['status'] ?? null) === 'unavailable') {
                $validated['status'] = 'not_available';
            }
            $validated['load_hours'] = $this->computeLoadHoursFromSchedules(
                (int) $validated['faculty_id'],
                $validated['grade_level'] ?? null,
                $validated['subject'] ?? null
            );
            $validated['classes_assigned'] = \App\Support\FacultyLoadStats::countOngoingClasses(
                (int) $validated['faculty_id'],
                $validated['grade_level'] ?? null
            );
            $validated['status'] = $this->computeAvailabilityStatus((int) $validated['faculty_id']);

            $load = FacultyLoad::create($validated);
            FacultyLoadSupport::refreshTeacherLoadingScheduleRow($load);
            FacultyLoadSupport::applySharedTeacherLoadConflict((int) $load->faculty_id, $load->id);

            $load->setRelation('faculty', null);
            return response()->json(['success' => true, 'message' => 'Faculty load added', 'data' => $load], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error adding faculty load'], 400);
        }
    }

    /**
     * Update a faculty load (Grade School only)
     */
    public function updateFacultyLoad(Request $request, $id) {
        try {
            $load = FacultyLoad::on('mysql_gs')->findOrFail($id);
            $oldGrade = $load->grade_level;
            $oldSubject = $load->subject;

            $validated = $request->validate([
                'faculty_id'       => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::with('role')->find($value);
                        if (!$user || $user->school_level !== 'grade_school') {
                            $fail('The selected teacher must have a Grade School user account in User Accounts.');
                            return;
                        }
                        if (!$user->role || stripos($user->role->name, 'teacher') === false) {
                            $fail('The selected faculty member must be a registered teacher or instructor.');
                        }
                    },
                ],
                'subject'          => 'nullable|string|max:255',
                'grade_level'      => 'nullable|string|max:50',
                'classes_assigned' => 'nullable|integer|min:0',
                'load_hours'       => 'nullable|numeric|min:0',
                'status'           => 'nullable|in:available,unavailable,not_available,overloaded,active,inactive',
                'notes'            => 'nullable|string|max:1000',
            ]);

            if (!empty($validated['faculty_id'])) {
                $teacher = User::find($validated['faculty_id']);
                $validated['teacher_name'] = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : null;
            }

            $dupMsg = FacultyLoadSupport::facultyLoadConflictMessage(
                (int) ($validated['faculty_id'] ?? $load->faculty_id),
                $validated['teacher_name'] ?? $load->teacher_name,
                $validated['grade_level'] ?? $load->grade_level,
                $validated['subject'] ?? $load->subject,
                (int) $load->id
            );
            if ($dupMsg !== null) {
                return response()->json(['success' => false, 'message' => $dupMsg], 409);
            }

            if (($validated['status'] ?? null) === 'unavailable') {
                $validated['status'] = 'not_available';
            }

            try {
                FacultyLoadSupport::assertFacultyLoadAccount((int) ($validated['faculty_id'] ?? 0), 'grade_school');
                if (!empty($validated['faculty_id']) && (int) $validated['faculty_id'] !== (int) $load->faculty_id) {
                    FacultyLoadSupport::assertSharedTeacherLoadLimit((int) $validated['faculty_id']);
                }
            } catch (\InvalidArgumentException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            if (!empty($validated['faculty_id'])) {
                $validated['load_hours'] = \App\Support\FacultyLoadStats::computeLoadHours(
                    (int) $validated['faculty_id'],
                    $validated['grade_level'] ?? null,
                    $validated['subject'] ?? null
                );
                $validated['classes_assigned'] = \App\Support\FacultyLoadStats::countOngoingClasses(
                    (int) $validated['faculty_id'],
                    $validated['grade_level'] ?? null
                );
                $validated['status'] = \App\Support\FacultyLoadStats::resolveStatus((int) $validated['faculty_id']);
            }

            $load->update($validated);
            $load->refresh();

            FacultyLoadSupport::syncSchedulesAfterLoadChange(
                (int) $load->faculty_id,
                $oldGrade,
                $oldSubject,
                $load->grade_level,
                $load->subject
            );
            FacultyLoadSupport::refreshTeacherLoadingScheduleRow($load, $oldSubject);
            FacultyLoadSupport::applySharedTeacherLoadConflict((int) $load->faculty_id, $load->id);

            $load->setRelation('faculty', User::find($load->faculty_id));
            return response()->json(['success' => true, 'message' => 'Faculty load updated', 'data' => $load]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => 'Validation failed'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating faculty load'], 400);
        }
    }

    private function resolveFacultyLoadStatus(float $loadHours, string $requestedStatus): string
    {
        if ($loadHours > 6) {
            return 'overloaded';
        }

        return $requestedStatus === 'overload' ? 'overloaded' : $requestedStatus;
    }

    private function computeAvailabilityStatus(int $facultyId): string
    {
        if ($facultyId <= 0) {
            return 'available';
        }

        $currentTime = now()->format('H:i');
        $currentDay = now()->format('l');

        $activeSchedule = ClassSchedule::where('faculty_id', $facultyId)
            ->where('day_of_week', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->exists();

        return $activeSchedule ? 'not_available' : 'available';
    }

    private function computeLoadHoursFromSchedules(int $facultyId, ?string $gradeLevel = null, ?string $subjectsCsv = null): float
    {
        if ($facultyId <= 0) {
            return 0.0;
        }

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->where('status', 'active');

        if (!empty($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        }

        $schedules = $query->get(['subject', 'start_time', 'end_time']);

        $selectedSubjects = collect(explode(',', (string) $subjectsCsv))
            ->map(fn($s) => strtolower(trim($s)))
            ->filter()
            ->values();

        $totalMins = 0;
        foreach ($schedules as $schedule) {
            $subject = strtolower(trim((string) $schedule->subject));
            if ($selectedSubjects->isNotEmpty() && !$selectedSubjects->contains($subject)) {
                continue;
            }

            $duration = $this->timeToMinutes($schedule->end_time) - $this->timeToMinutes($schedule->start_time);
            if ($duration > 0) {
                $totalMins += $duration;
            }
        }

        return round($totalMins / 60, 2);
    }

    private function timeToMinutes(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $parts = explode(':', $time);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }

    /**
     * Delete a faculty load (Grade School only)
     */
    public function deleteFacultyLoad($id) {
        try {
            $load = FacultyLoad::findOrFail($id);
            $removed = FacultyLoadSupport::cascadeDeleteForFacultyLoad($load);
            $load->delete();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Faculty load deleted. Removed %d schedule(s), %d pending record(s), and cleared %d weekly timetable row(s).',
                    $removed['schedules'],
                    $removed['pending'] ?? 0,
                    $removed['weekly']
                ),
                'removed' => $removed,
            ]);
        } catch (\Exception $e) {
            Log::error('GS deleteFacultyLoad: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error deleting faculty load'], 400);
        }
    }

    /**
     * Show rooms management page
     */
    public function rooms() {
        return view('grade-school-admin.rooms.index');
    }

    /**
     * Show users management page
     */
    public function users() {
        $users = User::where('school_level', 'grade_school')->with('role')->latest()->get();
        $accountRoleOptions = \App\Support\AdminUserRoleSupport::roleOptionsForPortal('grade_school');

        return view('grade-school-admin.users.index', compact('users', 'accountRoleOptions'));
    }

    public function storeUser(Request $request) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:191|unique:users,email',
            'role_id'    => 'required|integer',
            'password'   => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'This email address is already registered. A user with this email already exists and cannot be created again.',
        ]);

        $role = \App\Support\AdminUserRoleSupport::validateRoleForPortal('grade_school', (int) $validated['role_id']);
        $schoolLevel = \App\Support\AdminUserRoleSupport::schoolLevelForNewUser($role, 'grade_school');
        $validated = \App\Support\AdminUserAccountsSupport::withNormalizedNames($validated);

        $user = User::create([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'name'         => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email'        => $validated['email'],
            'role_id'      => $role->id,
            'password'     => bcrypt($validated['password']),
            'school_level' => $schoolLevel,
            'is_active'    => true,
        ]);

        // Store AES-encrypted password so admins can retrieve it:
        // SELECT AES_DECRYPT(password_encrypted, 'spup_ict_2026') AS plain_password FROM users;
        $aesKey = env('MYSQL_AES_KEY', 'spup_ict_2026');
        DB::statement(
            'UPDATE users SET password_encrypted = AES_ENCRYPT(?, ?) WHERE id = ?',
            [$validated['password'], $aesKey, $user->id]
        );

        if ($role->name === 'shared_teacher') {
            $request->validate([
                'subject1' => 'required|string|max:191',
                'subject2' => 'required|string|max:191|different:subject1',
            ]);
        }

        \App\Support\SharedTeacherRegistrySync::syncFromAdminRequest($user, $role, $request, 'grade_school');

        if ($request->wantsJson()) {
            $user->load('role');
            return response()->json([
                'success' => true,
                'message' => 'User ' . $user->name . ' created successfully.',
                'user'    => $user,
            ]);
        }

        return redirect()->route('grade-school-admin.users.index')
            ->with('success', 'User ' . $user->name . ' created successfully.');
    }

    /**
     * Show schedule approval page
     */
    public function scheduleApproval() {
        $query = ClassSchedule::query();
        $schedules = $query->orderBy('created_at', 'desc')->paginate(20);

        // Manually load faculty and room (avoid cross-connection eager loading)
        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $roomIds = $schedules->pluck('room_id')->filter()->unique();
        $rooms   = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
        $schedules->each(function ($s) use ($users, $rooms) {
            $s->setRelation('faculty', $users[$s->faculty_id] ?? null);
            $room = $rooms[$s->room_id] ?? null;
            $s->setRelation('room', $room);
            ScheduleDisplaySupport::applyToModel($s, $room);
        });

        $stats = [
            'pending'  => ClassSchedule::where('admin_approved', false)->where('status', 'pending')->count(),
            'approved' => ClassSchedule::where('admin_approved', true)->where('status', 'active')->count(),
            'rejected' => ClassSchedule::where('status', 'rejected')->count(),
            'total'    => ClassSchedule::count(),
        ];

        return view('grade-school-admin.schedule-approval.index', compact('schedules', 'stats'));
    }

    /**
     * Show reports page
     */
    public function reports() {
        return view('grade-school-admin.reports.index');
    }

    /**
     * Show rooms & sections management page (Grade School only)
     */
    public function roomsSections() {
        $rooms = Room::orderBy('id')->get();

        // Fixed sections for Grade School (Grade 1–6)
        $fixedSections = ['STEPHEN', 'PETER', 'ST.PAUL'];

        $sections = collect($fixedSections)->map(function ($sectionName) {
            $count = ClassSchedule::where('section_name', $sectionName)
                ->count();
            return [
                'section_name'   => $sectionName,
                'grade_level'    => 'Grade 1 – 6',
                'grade_section'  => $sectionName,
                'schedule_count' => $count,
            ];
        });

        return view('grade-school-admin.rooms-sections.index', compact('rooms', 'sections'));
    }

    /**
     * Store new schedules (Grade School only) – grid form submission.
     *
     * POST fields:
     *   grade_level  – e.g. "Grade 1"
     *   day_of_week  – Monday … Friday
     *   section_names[0/1/2]              – section display names set by JS
     *   slots[<timeKey>][<idx>][subject]   – free text
     *   slots[<timeKey>][<idx>][faculty_id] – user id
     *
     * <timeKey> : "HHMM_HHMM"  e.g. "0745_0835"
     * <idx>     : 0 | 1 | 2   (section column index, mapped to section_names)
     */
    public function storeSchedule(Request $request) {
        // Fixed time-slot definitions (matches the schedule grid form)
        $timeSlotMap = \App\Support\SchoolScheduleSlots::scheduleSlotKeyMap('grade_school');

        // Section key → display / stored name
        // Read from request (JS populates hidden inputs based on selected grade);
        // fall back to the original Grade-1 defaults.
        $sectionNamesInput = $request->input('section_names', []);
        $sectionDisplayMap = [
            '0' => $sectionNamesInput[0] ?? 'STEPHEN',
            '1' => $sectionNamesInput[1] ?? 'PETER',
            '2' => $sectionNamesInput[2] ?? 'ST. PAUL',
        ];

        try {
            $request->validate([
                'grade_level' => 'required|string|in:Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
                'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
                'slots'       => 'nullable|array',
            ]);

            $gradeLevel = $request->input('grade_level');
            $dayOfWeek  = $request->input('day_of_week');
            $slots      = $request->input('slots', []);
            $scheduleDate = $request->input('schedule_date') ?: null;
            $sectionRooms = $request->input('section_rooms', []);

            // ── Conflict detection – block save before writing anything ──────────
            $seenTeacherSlots = []; // "$facultyId|$timeKey" => first sectionKey
            $dailyNewCounts   = []; // "$facultyId|$dayOfWeek" => new entries in this batch
            $gsConflicts = [];
            // Shared teacher faculty_ids for cross-school (JH) conflict check
            $gsSharedFacultyIds = \App\Support\ScheduleStoreSupport::sharedFacultyIdStrings('mysql_gs');

            foreach ($slots as $timeKey => $sectionData) {
                if (!isset($timeSlotMap[$timeKey])) continue;
                $startTime = $timeSlotMap[$timeKey]['start'];

                foreach ($sectionData as $sectionKey => $data) {
                    $displaySec = $sectionDisplayMap[$sectionKey] ?? $sectionKey;

                    // Collect primary + extra rows for conflict checking
                    $cellRows = [];
                    $ps = trim($data['subject'] ?? '');
                    $pf = !empty($data['faculty_id']) ? (string) $data['faculty_id'] : null;
                    if ($ps !== '') {
                        $cellRows[] = ['subject' => $ps, 'faculty_id' => $pf];
                    }
                    foreach ($data['extra'] ?? [] as $extra) {
                        $s = trim($extra['subject'] ?? '');
                        $f = !empty($extra['faculty_id']) ? (string) $extra['faculty_id'] : null;
                        if ($s !== '') {
                            $cellRows[] = ['subject' => $s, 'faculty_id' => $f];
                        }
                    }
                    if (empty($cellRows)) continue;

                    $cellDup = \App\Support\ScheduleFormConflictSupport::duplicateSubjectTeacherInCell($cellRows);
                    if ($cellDup) {
                        $gsConflicts[] = "{$displaySec} at {$startTime}: {$cellDup}";

                        continue;
                    }

                    foreach ($cellRows as $row) {
                        $primarySubject = $row['subject'];
                        $primaryFaculty = $row['faculty_id'];

                        if (!$primaryFaculty) {
                            $gsConflicts[] = "{$displaySec}: subject \"{$primarySubject}\" has no teacher assigned — please select a teacher or clear the subject field.";
                            continue;
                        }
                        $slotKey = $primaryFaculty . '|' . $timeKey;

                        // 1) Same teacher in multiple sections at same time within this form
                        if (isset($seenTeacherSlots[$slotKey])) {
                            $teacher = User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $gsConflicts[] = "{$name} is assigned to multiple sections at {$startTime} ({$seenTeacherSlots[$slotKey]} and {$displaySec})";
                        }
                        $seenTeacherSlots[$slotKey] = $displaySec;

                        // 2) Teacher already has an approved schedule at same day+time (GS)
                        $existing = ClassSchedule::where('faculty_id', (int) $primaryFaculty)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('start_time', $startTime)
                            ->where('admin_approved', true)
                            ->first();
                        if ($existing) {
                            $teacher = User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $gsConflicts[] = "{$name} already has an approved schedule at {$startTime} on {$dayOfWeek} ({$existing->grade_level} – {$existing->section_name})";
                        }

                        // 3) Exact duplicate — same record already exists (approved or pending)
                        if (!$existing) {
                            $duplicate = ClassSchedule::where('faculty_id', (int) $primaryFaculty)
                                ->where('day_of_week', $dayOfWeek)
                                ->where('start_time', $startTime)
                                ->where('section_name', $displaySec)
                                ->where('subject', $primarySubject)
                                ->whereIn('status', ['pending', 'active'])
                                ->first();
                            if ($duplicate) {
                                $teacher = User::find((int) $primaryFaculty);
                                $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                                $statusLabel = $duplicate->admin_approved ? 'approved' : 'pending';
                                $gsConflicts[] = "{$name} – \"{$primarySubject}\" for {$displaySec} on {$dayOfWeek} at {$startTime} already exists ({$statusLabel})";
                            }
                        }

                        // 4) Cross-school check for shared teachers (JH schedules)
                        if (in_array($primaryFaculty, $gsSharedFacultyIds, true)) {
                            $crossExisting = \App\Support\ScheduleStoreSupport::crossSchoolApprovedConflict(
                                (int) $primaryFaculty,
                                $dayOfWeek,
                                $startTime,
                                'mysql_gs'
                            );
                            if ($crossExisting) {
                                $teacher = User::find((int) $primaryFaculty);
                                $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                                $gsConflicts[] = "{$name} (shared teacher) already has an approved Junior High schedule at {$startTime} on {$dayOfWeek} ({$crossExisting->grade_level} – {$crossExisting->section_name})";
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
                            $teacher = User::find((int) $primaryFaculty);
                            $name = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) : "Teacher #{$primaryFaculty}";
                            $gsConflicts[] = "{$name} would exceed the 5-subject daily limit on {$dayOfWeek} (currently {$existingDayCount} approved subject(s) — max is 5)";
                        }
                    }
                }
            }

            if (!empty($gsConflicts)) {
                return back()->withInput()
                    ->with('error', 'Schedule not saved — conflict(s) detected: ' . implode(' | ', $gsConflicts));
            }
            // ── End conflict detection ───────────────────────────────────────────

            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);
            $created = 0;

            foreach ($slots as $timeKey => $sectionData) {
                if (!isset($timeSlotMap[$timeKey])) {
                    continue;
                }
                $startTime = $timeSlotMap[$timeKey]['start'];
                $endTime   = $timeSlotMap[$timeKey]['end'];

                foreach ($sectionData as $sectionKey => $data) {
                    if (!isset($sectionDisplayMap[$sectionKey])) {
                        continue;
                    }

                    // Collect primary + extra subject rows
                    $allRows = [];
                    $primarySubject = trim($data['subject'] ?? '');
                    $primaryFaculty = $data['faculty_id'] ?? null;
                    if ($primarySubject !== '' && !empty($primaryFaculty)) {
                        $allRows[] = ['subject' => $primarySubject, 'faculty_id' => $primaryFaculty];
                    }
                    foreach ($data['extra'] ?? [] as $extra) {
                        $s = trim($extra['subject'] ?? '');
                        $f = $extra['faculty_id'] ?? null;
                        if ($s !== '' && !empty($f)) {
                            $allRows[] = ['subject' => $s, 'faculty_id' => $f];
                        }
                    }

                    foreach ($allRows as $row) {
                        $subject   = $row['subject'];
                        $facultyId = $row['faculty_id'];

                        // Ensure the teacher belongs to grade school
                        $teacher = User::find((int) $facultyId);
                        if (!$teacher || $teacher->school_level !== 'grade_school') {
                            continue;
                        }

                        $sectionName  = $sectionDisplayMap[$sectionKey];
                        $roomId       = !empty($sectionRooms[$sectionKey]) ? (int) $sectionRooms[$sectionKey] : null;

                        $changeLog = ScheduleAudit::appendChangeLog(
                            [],
                            'created',
                            Auth::user()?->name,
                            ['details' => 'Grade School schedule submitted for approval']
                        );

                        \App\Support\ScheduleStoreSupport::createPendingSchedule([
                            'faculty_id'    => (int) $facultyId,
                            'subject'       => $subject,
                            'grade_level'   => $gradeLevel,
                            'section_name'  => $sectionName,
                            'room_id'       => $roomId,
                            'day_of_week'   => $dayOfWeek,
                            'schedule_date' => $scheduleDate,
                            'start_time'    => $startTime,
                            'end_time'      => $endTime,
                            'student_count' => 0,
                            'status'        => 'pending',
                            'admin_approved'=> false,
                            'version'       => 1,
                            'change_log'    => $changeLog,
                        ], (int) $facultyId);

                        $created++;
                    } // end foreach allRows
                }
            }

            if ($created === 0) {
                return back()->withInput()->withErrors(['slots' => 'No schedule entries were filled in. Please fill at least one time slot.']);
            }

            return redirect()->to(route('grade-school-admin.class-schedule') . '#pending-schedules')
                ->with('success', $created . ' schedule entr' . ($created === 1 ? 'y' : 'ies') . ' created. Review them in Pending Schedules below.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('GS storeSchedule: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error creating schedule: ' . $e->getMessage()]);
        }
    }

    /**
     * Show generate/create schedule form
     */
    public function scheduleCreate()
    {
        return view('grade-school-admin.schedule-form', ScheduleFormSupport::buildGradeSchool());
    }

    /**
     * Show Print / Export page
     */
    public function printExport(Request $request) {
        $gradeLevel   = $request->input('grade_level');
        $dayOfWeek    = $request->input('day_of_week');
        $scheduleDate = $request->input('schedule_date');

        $sectionsByGrade = [
            'Grade 1' => ['STEPHEN', 'PETER', 'ST. PAUL'],
            'Grade 2' => ['ST. LUKE', 'ST. MARK', 'ST. MATTHEW'],
            'Grade 3' => ['ST. JOHN', 'ST. JAMES', 'ST. JOSEPH'],
            'Grade 4' => ['ST. FRANCIS', 'ST. AQUINAS', 'ST. LORENZO'],
            'Grade 5' => ['ST. MARGARETTE', 'ST. THERESE', 'ST. AGATHA'],
            'Grade 6' => ['ST. MA. GORETTI', 'ST. CATHERINE', 'ST. CLAIRE'],
        ];

        $timeSlots = \App\Support\SchoolScheduleSlots::printExportSlots('grade_school');

        $scheduleGrid  = [];
        $sections      = [];

        if ($gradeLevel) {
            $sections = $sectionsByGrade[$gradeLevel] ?? [];

            $query = ClassSchedule::where('grade_level', $gradeLevel)
                ->where('admin_approved', true);

            if ($dayOfWeek)    $query->where('day_of_week', $dayOfWeek);
            if ($scheduleDate) $query->where('schedule_date', $scheduleDate);

            $rawSchedules = $query->orderBy('day_of_week')->orderBy('start_time')->get();

            $userIds = $rawSchedules->pluck('faculty_id')->filter()->unique();
            $users   = $userIds->isNotEmpty() ? \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

            foreach ($rawSchedules as $s) {
                $teacher = $users[$s->faculty_id] ?? null;
                $teacherDisplay = '';
                if ($teacher) {
                    $last  = $teacher->last_name  ?? '';
                    $first = $teacher->first_name ?? '';
                    if ($last) {
                        $teacherDisplay = $last . ($first ? ' ' . substr($first, 0, 1) . '.' : '');
                    } else {
                        $parts = explode(' ', trim($teacher->name ?? ''));
                        $teacherDisplay = count($parts) > 1
                            ? implode(' ', array_slice($parts, 1)) . ' ' . substr($parts[0], 0, 1) . '.'
                            : ($teacher->name ?? '');
                    }
                }
                $day       = $s->day_of_week  ?? 'Unknown';
                $startTime = $s->start_time   ? substr($s->start_time, 0, 5) : '';
                $section   = $s->section_name ?? '';

                if (!isset($scheduleGrid[$day][$section][$startTime])) {
                    $scheduleGrid[$day][$section][$startTime] = [];
                }
                $scheduleGrid[$day][$section][$startTime][] = [
                    'subject' => $s->subject ?? '',
                    'teacher' => $teacherDisplay,
                    'date'    => $s->schedule_date,
                ];
            }
        }

        $gradeLevels = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $days        = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('grade-school-admin.print-export', compact(
            'gradeLevel', 'dayOfWeek', 'scheduleDate',
            'sections', 'scheduleGrid', 'timeSlots',
            'gradeLevels', 'days', 'sectionsByGrade'
        ));
    }

    public function exportCsv(Request $request)
    {
        $query = ClassSchedule::orderBy('day_of_week')->orderBy('start_time')
            ->where('admin_approved', true);
        if ($request->filled('grade_level')) {
            $query->where('grade_level', 'like', '%' . $request->grade_level . '%');
        }
        if ($request->filled('month')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('schedule_date')
                  ->orWhereRaw("DATE_FORMAT(schedule_date, '%Y-%m') = ?", [$request->month]);
            });
        }
        $schedules = $query->get();
        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty() ? \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

        $suffix  = $request->filled('grade_level') ? '_grade' . $request->grade_level : '';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="schedules' . $suffix . '_' . now()->format('Y_m_d') . '.csv"',
        ];

        $callback = function () use ($schedules, $users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Day', 'Start Time', 'End Time', 'Grade Level', 'Section', 'Subject', 'Teacher', 'Status']);
            foreach ($schedules as $s) {
                $teacher = $users[$s->faculty_id] ?? null;
                fputcsv($handle, [
                    $s->day_of_week, $s->start_time, $s->end_time,
                    $s->grade_level, $s->section_name, $s->subject,
                    $teacher ? $teacher->name : '', $s->status,
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $query = ClassSchedule::orderBy('day_of_week')->orderBy('start_time')
            ->where('admin_approved', true);
        if ($request->filled('grade_level')) {
            $query->where('grade_level', 'like', '%' . $request->grade_level . '%');
        }
        if ($request->filled('month')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('schedule_date')
                  ->orWhereRaw("DATE_FORMAT(schedule_date, '%Y-%m') = ?", [$request->month]);
            });
        }
        $schedules = $query->get();
        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty() ? \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

        $suffix  = $request->filled('grade_level') ? '_grade' . $request->grade_level : '';
        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="schedules' . $suffix . '_' . now()->format('Y_m_d') . '.xls"',
        ];

        $callback = function () use ($schedules, $users) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Day', 'Start Time', 'End Time', 'Grade Level', 'Section', 'Subject', 'Teacher', 'Status'], "\t");
            foreach ($schedules as $s) {
                $teacher = $users[$s->faculty_id] ?? null;
                fputcsv($handle, [
                    $s->day_of_week, $s->start_time, $s->end_time,
                    $s->grade_level, $s->section_name, $s->subject,
                    $teacher ? $teacher->name : '', $s->status,
                ], "\t");
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show System Logs page
     */
    public function systemLogs() {
        try {
            $connection = (new ClassSchedule)->getConnectionName();
            $rawLogs = DB::connection($connection)
                ->table('audit_logs')
                ->orderByDesc('changed_at')
                ->limit(50)
                ->get();

            $userIds = $rawLogs->flatMap(function ($log) {
                $ids = [];
                foreach (['old_data', 'new_data'] as $column) {
                    $payload = json_decode($log->{$column} ?? '', true);
                    if (!is_array($payload)) {
                        continue;
                    }

                    foreach (['approved_by', 'faculty_id', 'submitted_by', 'reviewed_by'] as $field) {
                        if (isset($payload[$field]) && is_numeric($payload[$field])) {
                            $ids[] = (int) $payload[$field];
                        }
                    }
                }

                if (is_numeric($log->changed_by)) {
                    $ids[] = (int) $log->changed_by;
                }

                return $ids;
            })->unique()->values();

            $users = $userIds->isNotEmpty()
                ? User::whereIn('id', $userIds)->get()->keyBy('id')
                : collect();

            $logs = $rawLogs->map(function ($log) use ($users) {
                $action = strtoupper((string) $log->action);

                return [
                    'timestamp' => $log->changed_at,
                    'user' => ScheduleAudit::resolveUserDisplay($log->changed_by, $users),
                    'table' => Str::headline(str_replace('_', ' ', (string) $log->table_name)),
                    'action' => $action,
                    'record' => $log->record_id ? '#' . $log->record_id : 'N/A',
                    'details' => ScheduleAudit::summarizeAuditLog((array) $log, $users),
                    'level_class' => match ($action) {
                        'DELETE' => 'level-error',
                        'UPDATE' => 'level-warning',
                        default => 'level-info',
                    },
                ];
            });

            return view('grade-school-admin.system-logs.index', ['logs' => $logs]);
        } catch (\Exception $e) {
            Log::error('Grade School system logs error: ' . $e->getMessage());

            return view('grade-school-admin.system-logs.index', [
                'logs' => collect(),
                'error' => 'Unable to load audit logs right now.',
            ]);
        }
    }

    /**
     * Show System Settings page
     */
    public function settings() {
        $backupDir = storage_path('app/backups/gs');
        $backupFiles = [];
        if (is_dir($backupDir)) {
            foreach (array_reverse(glob($backupDir . '/*.json')) as $f) {
                $backupFiles[] = [
                    'name' => basename($f),
                    'size' => round(filesize($f) / 1024, 1) . ' KB',
                    'date' => date('M d, Y H:i', filemtime($f)),
                ];
            }
        }
        return view('grade-school-admin.settings.index', compact('backupFiles'));
    }

    // -------------------------------------------------------------------------
    // Teacher portal data (subject_assignments, etc.) on mysql_gs admin DB
    // GS admin needs to see what teachers are requesting / assigning.
    // -------------------------------------------------------------------------

    /**
     * GET api/grade-school-admin/teacher/adjustment-requests
     * List all schedule adjustment requests submitted by GS teachers.
     */
    public function getTeacherAdjustmentRequests(Request $request)
    {
        try {
            $query = DB::connection('mysql_gs')
                ->table('teacher_requests')
                ->orderBy('created_at', 'desc');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $requests = $query->get()->filter(
                fn ($r) => ! \App\Support\TeacherPresenceSupport::isAbsenceLeaveType($r->request_type ?? null)
            );

            // Enrich with requester name from main DB
            $userIds = $requests->pluck('faculty_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $requests = $requests->map(function ($r) use ($users) {
                $user = $users->get($r->faculty_id);
                $r->requested_by = $r->faculty_id;
                $r->teacher_name = $r->teacher_name ?: ($user
                    ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                    : 'Unknown');
                return $r;
            });

            return response()->json(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getTeacherLeaveRequests(Request $request)
    {
        try {
            $data = \App\Support\TeacherLeaveRequestSupport::listForAdmin('mysql_gs');

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveTeacherLeaveRequest(Request $request, $id)
    {
        $result = \App\Support\TeacherLeaveRequestSupport::reviewApi(
            'mysql_gs',
            (int) $id,
            'approved',
            $request->input('admin_notes')
        );

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function rejectTeacherLeaveRequest(Request $request, $id)
    {
        $result = \App\Support\TeacherLeaveRequestSupport::reviewApi(
            'mysql_gs',
            (int) $id,
            'rejected',
            $request->input('admin_notes')
        );

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * POST api/grade-school-admin/teacher/adjustment-requests/{id}/approve
     */
    public function approveTeacherAdjustmentRequest(Request $request, $id)
    {
        try {
            $row = DB::connection('mysql_gs')->table('teacher_requests')->where('id', $id)->first();
            if (! $row) {
                return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
            }

            $applyMsg = '';
            if (! \App\Support\TeacherPresenceSupport::isAbsenceLeaveType($row->request_type ?? null)) {
                $result = \App\Support\TeacherAdjustmentRequestSupport::applyApprovedToSchedule(
                    'mysql_gs',
                    $row,
                    \App\Support\TeacherAdjustmentRequestSupport::reviewerDisplayName(Auth::id())
                );
                $applyMsg = $result['applied'] ? ' ' . $result['message'] : ' (' . $result['message'] . ')';
            }

            DB::connection('mysql_gs')
                ->table('teacher_requests')
                ->where('id', $id)
                ->update([
                    'status'      => 'approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'admin_notes' => $request->input('admin_notes'),
                    'updated_at'  => now(),
                ]);

            \App\Support\TeacherPortalNotificationSupport::notifyTeacherRequestDecision(
                'mysql_gs',
                $row,
                'approved',
                $request->input('admin_notes')
            );

            return response()->json(['success' => true, 'message' => 'Request approved.' . $applyMsg]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST api/grade-school-admin/teacher/adjustment-requests/{id}/reject
     */
    public function rejectTeacherAdjustmentRequest(Request $request, $id)
    {
        try {
            $row = DB::connection('mysql_gs')->table('teacher_requests')->where('id', $id)->first();

            DB::connection('mysql_gs')
                ->table('teacher_requests')
                ->where('id', $id)
                ->update([
                    'status'      => 'rejected',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'admin_notes' => $request->input('admin_notes'),
                    'updated_at'  => now(),
                ]);

            if ($row) {
                \App\Support\TeacherPortalNotificationSupport::notifyTeacherRequestDecision(
                    'mysql_gs',
                    $row,
                    'rejected',
                    $request->input('admin_notes')
                );
            }

            return response()->json(['success' => true, 'message' => 'Request rejected.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET api/grade-school-admin/teacher/subject-assignments
     * List all subject assignments made by GS teachers.
     */
    public function getTeacherSubjectAssignments(Request $request)
    {
        try {
            $assignments = DB::connection('mysql_gs')
                ->table('subject_assignments')
                ->orderBy('created_at', 'desc')
                ->get();

            // Enrich with teacher names from main DB
            $userIds = $assignments->pluck('faculty_id')->merge($assignments->pluck('assigned_by'))->unique()->filter();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $assignments = $assignments->map(function ($a) use ($users) {
                $faculty  = $users->get($a->faculty_id);
                $assigner = $users->get($a->assigned_by);
                $a->faculty_name      = $faculty  ? trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? '')) : 'Unknown';
                $a->assigned_by_name  = $assigner ? trim(($assigner->first_name ?? '') . ' ' . ($assigner->last_name ?? '')) : null;
                return $a;
            });

            return response()->json(['success' => true, 'data' => $assignments]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate institution-wide report output for Grade School admin.
     */
    public function generateInstitutionReport(Request $request, string $type)
    {
        if (!in_array($type, self::REPORT_TYPES, true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported report type.'], 422);
        }

        $format = strtolower((string) $request->query('format', 'csv'));
        if (!in_array($format, ['csv', 'json'], true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported format.'], 422);
        }

        try {
            $rows = $this->buildReportRows($type);

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'type' => $type,
                    'generated_at' => now()->toDateTimeString(),
                    'rows' => $rows,
                ]);
            }

            $csv = $this->buildCsvContent($rows);
            $filename = sprintf('%s_grade_school_%s.csv', $type, now()->format('Ymd_His'));

            $this->logExport($type, $filename, strlen($csv), count($rows));

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('GS report generation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate report.'], 500);
        }
    }

    /**
     * Return recent generated reports for monitoring/record-keeping.
     */
    public function reportHistory()
    {
        try {
            $logs = GeneratedReport::query()->orderByDesc('created_at')->limit(20)->get();
            $userIds = $logs->pluck('created_by')->filter()->unique();
            $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

            $items = $logs->map(function ($log) use ($users) {
                $creator = $users[$log->created_by] ?? null;

                return [
                    'id' => $log->id,
                    'report_type' => $log->report_type,
                    'format' => strtoupper((string) $log->format),
                    'filename' => $log->filename,
                    'file_size' => (int) ($log->file_size ?? 0),
                    'status' => $log->status,
                    'generated_at' => optional($log->created_at)->toDateTimeString(),
                    'generated_by' => $creator?->name ?? 'System',
                ];
            });

            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Exception $e) {
            Log::error('GS report history error: ' . $e->getMessage());
            return response()->json(['success' => false, 'data' => []], 500);
        }
    }

    private function buildReportRows(string $type): array
    {
        return match ($type) {
            'master_loading_summary' => $this->buildMasterLoadingSummaryRows(),
            'complete_schedule_listing' => $this->buildCompleteScheduleListingRows(),
            'workload_analytics' => $this->buildWorkloadAnalyticsRows(),
            'compliance_report' => $this->buildComplianceRows(),
            default => [],
        };
    }

    private function buildMasterLoadingSummaryRows(): array
    {
        $loads = FacultyLoad::orderBy('faculty_id')->get();
        $userIds = $loads->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

        $byFaculty = [];
        foreach ($loads as $load) {
            $facultyId = $load->faculty_id ?? 0;
            if (!isset($byFaculty[$facultyId])) {
                $user = $users[$facultyId] ?? null;
                $byFaculty[$facultyId] = [
                    'faculty_id' => $facultyId,
                    'faculty_name' => $user?->name ?? ($load->teacher_name ?? 'Unknown'),
                    'subjects_count' => 0,
                    'classes_assigned' => 0,
                    'total_load_hours' => 0,
                    'status' => 'active',
                ];
            }

            $byFaculty[$facultyId]['subjects_count']++;
            $byFaculty[$facultyId]['classes_assigned'] += (int) ($load->classes_assigned ?? 0);
            $byFaculty[$facultyId]['total_load_hours'] += (float) ($load->load_hours ?? 0);
            $byFaculty[$facultyId]['status'] = $load->status ?? 'active';
        }

        foreach ($byFaculty as &$row) {
            $row['total_load_hours'] = round($row['total_load_hours'], 2);
        }

        return array_values($byFaculty);
    }

    private function buildCompleteScheduleListingRows(): array
    {
        $schedules = ClassSchedule::orderBy('day_of_week')->orderBy('start_time')->get();

        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $roomIds = $schedules->pluck('room_id')->filter()->unique();
        $rooms = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();

        return $schedules->map(function ($schedule) use ($users, $rooms) {
            $faculty = $users[$schedule->faculty_id] ?? null;
            $room = $rooms[$schedule->room_id] ?? null;

            return [
                'schedule_id' => $schedule->id,
                'subject' => $schedule->subject,
                'grade_level' => $schedule->grade_level,
                'section_name' => $schedule->section_name,
                'faculty_name' => $faculty?->name ?? 'Unassigned',
                'room' => $room ? 'Room #' . $room->id : 'TBD',
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'student_count' => (int) ($schedule->student_count ?? 0),
                'status' => $schedule->status,
                'admin_approved' => $schedule->admin_approved ? 'YES' : 'NO',
            ];
        })->toArray();
    }

    private function buildWorkloadAnalyticsRows(): array
    {
        $loads = FacultyLoad::all();
        $facultyCount = User::where('school_level', 'grade_school')
            ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
            ->count();

        $totalHours = (float) $loads->sum('load_hours');
        $avgHours = $facultyCount > 0 ? $totalHours / $facultyCount : 0;
        $overloaded = $loads->filter(fn($l) => (float) ($l->load_hours ?? 0) > 30)->count();
        $underloaded = $loads->filter(fn($l) => (float) ($l->load_hours ?? 0) < 12)->count();

        return [
            [
                'metric' => 'Total Faculty Members',
                'value' => $facultyCount,
                'notes' => 'Institution-wide teacher count',
            ],
            [
                'metric' => 'Total Load Hours',
                'value' => round($totalHours, 2),
                'notes' => 'Sum of all assigned load hours',
            ],
            [
                'metric' => 'Average Load Hours',
                'value' => round($avgHours, 2),
                'notes' => 'Average per faculty member',
            ],
            [
                'metric' => 'Overloaded Faculty',
                'value' => $overloaded,
                'notes' => 'Faculty with load hours greater than 30',
            ],
            [
                'metric' => 'Underloaded Faculty',
                'value' => $underloaded,
                'notes' => 'Faculty with load hours less than 12',
            ],
        ];
    }

    private function buildComplianceRows(): array
    {
        $schedules = ClassSchedule::all();

        $missingRoom = $schedules->filter(fn($s) => empty($s->room_id))->count();
        $pendingApproval = $schedules->filter(fn($s) => !$s->admin_approved)->count();
        $inactiveSchedules = $schedules->where('status', '!=', 'active')->count();

        $facultyConflicts = 0;
        $roomConflicts = 0;

        $indexed = $schedules->values();
        for ($i = 0; $i < $indexed->count(); $i++) {
            for ($j = $i + 1; $j < $indexed->count(); $j++) {
                $a = $indexed[$i];
                $b = $indexed[$j];

                if ($a->day_of_week !== $b->day_of_week) {
                    continue;
                }

                $overlaps = $a->start_time < $b->end_time && $b->start_time < $a->end_time;
                if (!$overlaps) {
                    continue;
                }

                if (!empty($a->faculty_id) && $a->faculty_id === $b->faculty_id) {
                    $facultyConflicts++;
                }

                if (!empty($a->room_id) && $a->room_id === $b->room_id) {
                    $roomConflicts++;
                }
            }
        }

        return [
            ['indicator' => 'Schedules Missing Room', 'count' => $missingRoom, 'status' => $missingRoom > 0 ? 'Needs Attention' : 'Compliant'],
            ['indicator' => 'Pending Admin Approvals', 'count' => $pendingApproval, 'status' => $pendingApproval > 0 ? 'Needs Attention' : 'Compliant'],
            ['indicator' => 'Inactive/Non-Active Schedules', 'count' => $inactiveSchedules, 'status' => $inactiveSchedules > 0 ? 'Review Required' : 'Compliant'],
            ['indicator' => 'Faculty Time Conflicts', 'count' => $facultyConflicts, 'status' => $facultyConflicts > 0 ? 'Violation' : 'Compliant'],
            ['indicator' => 'Room Time Conflicts', 'count' => $roomConflicts, 'status' => $roomConflicts > 0 ? 'Violation' : 'Compliant'],
        ];
    }

    private function buildCsvContent(array $rows): string
    {
        if (empty($rows)) {
            return "No data available\n";
        }

        $handle = fopen('php://temp', 'r+');
        $headers = array_keys($rows[0]);
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    private function logExport(string $reportType, string $filename, int $fileSize, int $rowCount): void
    {
        GeneratedReport::create([
            'report_type' => $reportType,
            'format' => 'csv',
            'scope' => 'grade_school_admin',
            'filename' => $filename,
            'row_count' => $rowCount,
            'file_size' => $fileSize,
            'status' => 'completed',
            'metadata' => [
                'generated_at' => now()->toDateTimeString(),
                'institution_scope' => 'all_system_data',
            ],
            'created_by' => Auth::id(),
        ]);

        ExportLog::create([
            'format' => 'csv',
            'data_selected' => json_encode([
                'report_type' => $reportType,
                'scope' => 'grade_school_admin',
                'generated_at' => now()->toDateTimeString(),
            ]),
            'filename' => $filename,
            'file_path' => null,
            'file_size' => $fileSize,
            'status' => 'completed',
            'created_by' => Auth::id(),
        ]);
    }

    // ─── Backup & Recovery ────────────────────────────────────────────────────

    public function backupDownload()
    {
        $connection = (new \App\Models\ClassSchedule)->getConnectionName();

        $schedules    = DB::connection($connection)->table('class_schedules')->get()->toArray();
        $facultyLoads = DB::connection($connection)->table('faculty_loads')->get()->toArray();

        $data = [
            'exported_at'   => now()->toISOString(),
            'school'        => 'grade_school',
            'schedules'     => array_map(fn($r) => (array) $r, $schedules),
            'faculty_loads' => array_map(fn($r) => (array) $r, $facultyLoads),
        ];

        $filename = 'gs_backup_' . now()->format('Y-m-d_His') . '.json';
        $dir      = storage_path('app/backups/gs');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fullPath = $dir . '/' . $filename;
        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT));

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function backupRestore(\Illuminate\Http\Request $request)
    {
        $request->validate(['backup_file' => 'required|file|mimes:json,txt|max:51200']);

        $content = json_decode(
            file_get_contents($request->file('backup_file')->getRealPath()),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE || !isset($content['schedules'])) {
            return back()->withErrors(['backup_file' => 'Invalid or corrupted backup file.']);
        }

        $connection = (new \App\Models\ClassSchedule)->getConnectionName();
        $db         = DB::connection($connection);

        $restoredSchedules = 0;
        foreach (($content['schedules'] ?? []) as $row) {
            if (empty($row['id'])) {
                continue;
            }
            $id  = $row['id'];
            $row = array_diff_key($row, ['id' => true]);
            if ($db->table('class_schedules')->where('id', $id)->exists()) {
                $db->table('class_schedules')->where('id', $id)->update($row);
            } else {
                $db->table('class_schedules')->insert(array_merge(['id' => $id], $row));
            }
            $restoredSchedules++;
        }

        $restoredLoads = 0;
        foreach (($content['faculty_loads'] ?? []) as $row) {
            if (empty($row['id'])) {
                continue;
            }
            $id  = $row['id'];
            $row = array_diff_key($row, ['id' => true]);
            if ($db->table('faculty_loads')->where('id', $id)->exists()) {
                $db->table('faculty_loads')->where('id', $id)->update($row);
            } else {
                $db->table('faculty_loads')->insert(array_merge(['id' => $id], $row));
            }
            $restoredLoads++;
        }

        return redirect()->route('grade-school-admin.settings', ['tab' => 'backup'])
            ->with('success', "Restore complete — {$restoredSchedules} schedule records and {$restoredLoads} faculty-load records restored.");
    }
}
