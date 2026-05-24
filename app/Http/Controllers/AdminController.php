<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use App\Models\ScheduleApproval;
use App\Models\User;
use App\Models\Room;
use App\Models\Role;
use App\Models\FacultyLoad;
use App\Models\ExportLog;
use App\Models\GeneratedReport;
use App\Notifications\ScheduleRemovedNotification;
use App\Support\CombinedScheduleService;
use App\Support\ScheduleDisplaySupport;
use App\Support\ScheduleFormSupport;
use App\Support\ScheduleAudit;
use App\Support\ScheduleUpdateHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminController extends Controller {
    private const REPORT_TYPES = [
        'master_loading_summary',
        'complete_schedule_listing',
        'workload_analytics',
        'compliance_report',
    ];

    /**
     * Get the authenticated admin's school level
     */
    private function getAdminSchoolLevel() {
        return Auth::user()->school_level ?? 'grade_school';
    }

    /**
     * Show create schedule form (Junior High admin).
     */
    public function scheduleCreate()
    {
        return view('junior-high-admin.schedule-form', ScheduleFormSupport::buildJuniorHigh());
    }

    private function removeRelatedWeeklyScheduleRows(ClassSchedule $schedule): void
    {
        $masterQuery = \App\Models\MasterWeeklySchedule::where('faculty_id', $schedule->faculty_id);

        if ($schedule->day_of_week) {
            $masterQuery->where('day_of_week', $schedule->day_of_week);
        }

        if ($schedule->start_time) {
            $masterQuery->where('time_start', substr($schedule->start_time, 0, 5));
        }

        if ($schedule->end_time) {
            $masterQuery->where('time_end', substr($schedule->end_time, 0, 5));
        }

        if ($schedule->grade_level) {
            $masterQuery->where('grade_level', $schedule->grade_level);
        }

        if ($schedule->section_name) {
            $masterQuery->where('section_name', $schedule->section_name);
        }

        $masterQuery->delete();
    }

    private function userCanManageSchoolSchedules(): bool
    {
        return in_array(Auth::user()?->role?->name, [
            'admin',
            'admin_junior_high',
            'admin_grade_school',
            'principal',
        ], true);
    }

    private function notifyPrincipalAboutRemoval(ClassSchedule $schedule, string $action, ?string $reason = null): void
    {
        try {
            $principals = User::whereHas('role', function ($query) {
                $query->where('name', 'principal');
            })->get();

            if ($principals->isEmpty()) {
                return;
            }

            $teacherName = 'Unknown teacher';
            if ($schedule->faculty_id) {
                $teacher = User::find($schedule->faculty_id);
                if ($teacher) {
                    $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: $teacher->name;
                }
            }

            $principals->each(fn (User $principal) => $principal->notify(
                new ScheduleRemovedNotification($schedule, $action, $reason, $teacherName)
            ));
        } catch (\Exception $e) {
            Log::warning('notifyPrincipalAboutRemoval: ' . $e->getMessage());
        }
    }

    /**
     * Approved schedules for the JH dashboard weekly timetable.
     */
    public function getCombinedSchedules(\Illuminate\Http\Request $request)
    {
        try {
            $data = collect(CombinedScheduleService::fetchApproved())
                ->filter(fn ($s) => ($s['school'] ?? '') === 'JH')
                ->values()
                ->all();

            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            Log::error('JH getCombinedSchedules: ' . $e->getMessage());

            return response()->json(['data' => []], 500);
        }
    }

    /**
     * Get all schedules with relationships (filtered by school_level)
     */
    public function getSchedules(\Illuminate\Http\Request $request) {
        try {
            $query = ClassSchedule::query();
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', (int) $request->faculty_id);
            }
            $schedules = $query->get();
            $userIds = $schedules->pluck('faculty_id')
                ->merge($schedules->pluck('approved_by'))
                ->filter()->unique()->values();
            $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
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
                    $room = $rooms[$s->room_id];
                    $data['room'] = method_exists($room, 'toArray') ? $room->toArray() : (array) $room;
                }
                $data['approver'] = isset($users[$s->approved_by]) ? $users[$s->approved_by]->toArray() : null;
                $data['approved_by_name'] = ScheduleAudit::approverName($s->approved_by, $users);

                return $data;
            })->values();

            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('Get schedules error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching schedules: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a single schedule by ID (with school_level verification)
     */
    public function getSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $room = $schedule->room_id ? Room::find($schedule->room_id) : null;
            $faculty = $schedule->faculty_id ? User::find($schedule->faculty_id) : null;
            $data = ScheduleDisplaySupport::enrichForApi($schedule->toArray(), $schedule, $room, $faculty);
            $data['schedule_date'] = $schedule->getRawOriginal('schedule_date');
            $data['display_date'] = ScheduleDisplaySupport::formatScheduleDate($data['schedule_date']);
            $data['faculty'] = $faculty?->toArray();
            $data['room'] = $room?->toArray();
            $data['approver'] = $schedule->approved_by ? User::find($schedule->approved_by)?->toArray() : null;
            $data['approved_by_name'] = $data['approver']['name'] ?? null;

            return response()->json(['data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            Log::error('Get schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Get schedule change history (with school_level verification)
     */
    public function getScheduleHistory($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $changeLog = json_decode($schedule->change_log, true) ?? [];
            return response()->json(['success' => true, 'change_log' => $changeLog]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            Log::error('Get schedule history error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching history: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Approve a schedule (with school_level verification)
     */
    public function approveSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

            // Conflict detection: check for existing approved schedules at the same time for the same teacher
            if ($schedule->faculty_id && $schedule->day_of_week && $schedule->start_time) {
                $conflict = ClassSchedule::where('faculty_id', $schedule->faculty_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('admin_approved', true)
                    ->where('id', '!=', $schedule->id)
                    ->get()
                    ->first(function ($existing) use ($schedule) {
                        $newStart  = strtotime($schedule->start_time);
                        $newEnd    = strtotime($schedule->end_time ?? '23:59:59');
                        $exStart   = strtotime($existing->start_time);
                        $exEnd     = strtotime($existing->end_time ?? '23:59:59');
                        return $newStart < $exEnd && $exStart < $newEnd;
                    });

                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot approve: this teacher already has an approved schedule on ' .
                            $schedule->day_of_week . ' from ' .
                            substr($conflict->start_time, 0, 5) . ' to ' .
                            substr($conflict->end_time ?? '', 0, 5) . '. Resolve the conflict first.',
                    ], 409);
                }
            }

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

            $schedule = $schedule->fresh();
            return response()->json(['success' => true, 'message' => 'Schedule approved successfully', 'data' => $schedule]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            Log::error('Approve schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error approving schedule: ' . $e->getMessage()], 400);
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

            $this->archiveRejectedSchedule($schedule, $reason, $dbConn);

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

            $this->removeRelatedWeeklyScheduleRows($schedule);

            $schedule->delete();

            return response()->json(['success' => true, 'message' => 'Schedule rejected and removed successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            Log::error('Reject schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error rejecting schedule: ' . $e->getMessage()], 400);
        }
    }

    private function archiveRejectedSchedule(ClassSchedule $schedule, string $reason, string $dbConn): void
    {
        if (!Schema::connection($dbConn)->hasTable('rejected_schedules')) {
            return;
        }

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

    /**
     * Update schedule details (with school_level verification)
     */
    public function updateSchedule(Request $request, $id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);
            ScheduleUpdateHelper::mergeNormalizedInput($request);
            $validated = $request->validate(ScheduleUpdateHelper::validationRules());

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
            return response()->json(['success' => true, 'message' => 'Schedule updated successfully', 'data' => $schedule]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Update schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete a schedule (admin action)
     */
    public function destroy(Request $request, ClassSchedule $schedule)
    {
        if (! $this->userCanManageSchoolSchedules()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        ScheduleAudit::setAuditUser((new ClassSchedule)->getConnectionName(), Auth::user()?->name);

        try {
            $scheduleId = $schedule->id;
            $dbConn     = $schedule->getConnectionName();

            $this->removeRelatedWeeklyScheduleRows($schedule);
            $this->notifyPrincipalAboutRemoval($schedule, 'deleted', $validated['reason'] ?? 'No reason provided');

            DB::connection($dbConn)->table('pending_schedules')
                ->where('schedule_id', $scheduleId)
                ->delete();

            try {
                DB::connection($dbConn)->table('schedule_approvals')
                    ->where('schedule_id', $scheduleId)
                    ->delete();
            } catch (\Exception $ignored) {}

            // Physically delete the schedule record to avoid ENUM issues
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
     * Delete a schedule by numeric ID (called from DELETE api/admin/schedules/{id}).
     */
    public function deleteSchedule(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);
        return $this->destroy($request, $schedule);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => ['required', 'email', 'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'position'   => 'nullable|string|max:100',
            'role_id'    => 'nullable|exists:roles,id',
            'password'   => 'nullable|string|min:8|confirmed',
        ]);

        $data = \App\Support\AdminUserAccountsSupport::withNormalizedNames($data);
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'];
        $user->name       = trim($data['first_name'] . ' ' . $data['last_name']);
        $user->email      = $data['email'];
        if (array_key_exists('position', $data)) {
            $user->position = $data['position'];
        }
        if (!empty($data['role_id'])) {
            $newRole = Role::find((int) $data['role_id']);
            if ($newRole && $newRole->name !== 'principal') {
                $user->role_id = $newRole->id;
            }
        }
        if (!empty($data['password'])) {
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
            $aesKey = env('MYSQL_AES_KEY', 'spup_ict_2026');
            DB::statement('UPDATE users SET password_encrypted = AES_ENCRYPT(?, ?) WHERE id = ?',
                [$data['password'], $aesKey, $user->id]);
        }
        $user->save();

        return back()->with('success', "Account for {$user->name} updated successfully.");
    }

    public function toggleUserActive(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        $user->is_active = !$user->is_active;
        $user->save();
        $state = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "{$user->name} has been {$state}.");
    }

    public function destroyUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if (($user->role?->name ?? '') === 'principal') {
            return back()->with('error', 'Principal accounts cannot be deleted here.');
        }

        $schoolLevel = $this->getAdminSchoolLevel();
        if (($user->school_level ?? '') !== $schoolLevel && ($user->role?->name ?? '') !== 'shared_teacher') {
            return back()->with('error', 'You can only delete user accounts for your school level.');
        }

        $name = $user->name;
        \App\Support\UserSchoolDataPurge::purge($user);
        $user->delete();

        return back()->with('success', "User {$name} deleted.");
    }

    public function printExportSchedule(Request $request)
    {
        $gradeLevel   = $request->input('grade_level');
        $dayOfWeek    = $request->input('day_of_week');
        $scheduleDate = $request->input('schedule_date');

        $sectionsByGrade = [
            'Grade 7'  => ['SERAPHIM', 'CHERUBIM', 'MICHAEL', 'RAPHAEL', 'GABRIEL'],
            'Grade 8'  => ['THERESE', 'ALOYSIUS', 'AGNES', 'JOHN', 'GORETTI'],
            'Grade 9'  => ['CHARTRES', 'PIAT', 'FATIMA', 'CARMEL', 'LOURDES'],
            'Grade 10' => ['PAUL', 'PLC', 'MBF', 'MICHEAU', 'MARIA'],
        ];

        $timeSlots = \App\Support\SchoolScheduleSlots::printExportSlots('junior_high');

        $scheduleGrid  = []; // [day][section_name][start_time] = [{subject, teacher}]
        $sections      = [];
        $availableDays = [];

        if ($gradeLevel) {
            $sections = $sectionsByGrade[$gradeLevel] ?? [];

            $query = ClassSchedule::where('grade_level', $gradeLevel)
                ->where('admin_approved', true)
                ->where('status', 'active');

            if ($dayOfWeek) {
                $query->where('day_of_week', $dayOfWeek);
            }
            if ($scheduleDate) {
                $query->where('schedule_date', $scheduleDate);
            }

            $rawSchedules = $query->orderBy('day_of_week')->orderBy('start_time')->get();

            $userIds = $rawSchedules->pluck('faculty_id')->filter()->unique();
            $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

            $availableDays = ClassSchedule::where('grade_level', $gradeLevel)
                ->where('admin_approved', true)
                ->distinct()
                ->pluck('day_of_week')
                ->filter()
                ->values()
                ->toArray();

            $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5];
            usort($availableDays, fn($a, $b) => ($dayOrder[$a] ?? 9) - ($dayOrder[$b] ?? 9));

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
                $day       = $s->day_of_week   ?? 'Unknown';
                $startTime = $s->start_time    ? substr($s->start_time, 0, 5) : '';
                $section   = $s->section_name  ?? '';

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

        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
        $days        = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('junior-high-admin.print-export', compact(
            'gradeLevel', 'dayOfWeek', 'scheduleDate',
            'sections', 'scheduleGrid', 'timeSlots', 'availableDays',
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
        $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

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
                    $s->day_of_week,
                    $s->start_time,
                    $s->end_time,
                    $s->grade_level,
                    $s->section_name,
                    $s->subject,
                    $teacher ? $teacher->name : '',
                    $s->status,
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
        $users   = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

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
                    $s->day_of_week,
                    $s->start_time,
                    $s->end_time,
                    $s->grade_level,
                    $s->section_name,
                    $s->subject,
                    $teacher ? $teacher->name : '',
                    $s->status,
                ], "\t");
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeUser(\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:191|unique:users,email',
            'role_id'    => 'required|integer',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        $role = \App\Support\AdminUserRoleSupport::validateRoleForPortal('junior_high', (int) $validated['role_id']);
        $schoolLevel = \App\Support\AdminUserRoleSupport::schoolLevelForNewUser($role, 'junior_high');
        $validated = \App\Support\AdminUserAccountsSupport::withNormalizedNames($validated);

        $user = \App\Models\User::create([
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
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE users SET password_encrypted = AES_ENCRYPT(?, ?) WHERE id = ?',
            [$validated['password'], $aesKey, $user->id]
        );

        if ($role->name === 'shared_teacher') {
            $request->validate([
                'subject1' => 'required|string|max:191',
                'subject2' => 'required|string|max:191|different:subject1',
            ]);
        }

        \App\Support\SharedTeacherRegistrySync::syncFromAdminRequest($user, $role, $request, 'junior_high');

        if ($request->wantsJson()) {
            $user->load('role');
            return response()->json([
                'success' => true,
                'message' => 'User ' . $user->name . ' created successfully.',
                'user'    => $user,
            ]);
        }

        return redirect()->route('admin.users')
            ->with('success', 'User ' . $user->name . ' created successfully.');
    }

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

            return view('junior-high-admin.system-logs.index', ['logs' => $logs]);
        } catch (\Exception $e) {
            Log::error('Junior High system logs error: ' . $e->getMessage());

            return view('junior-high-admin.system-logs.index', [
                'logs' => collect(),
                'error' => 'Unable to load audit logs right now.',
            ]);
        }
    }

    /**
     * Add a new teacher/faculty (assigned to admin's school_level)
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

            // Determine teacher role based on school level
            $teacherRoleName = $schoolLevel === 'junior_high' ? 'teacher_junior_high' : 'teacher_grade_school';
            $teacherRole = Role::where('name', $teacherRoleName)->first();

            if (!$teacherRole) {
                return response()->json(['success' => false, 'message' => "Teacher role '{$teacherRoleName}' not found"], 400);
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

            return response()->json(['success' => true, 'message' => 'Teacher added successfully', 'data' => $user], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Add teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error adding teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * All teachers in User Accounts for this admin's school level.
     * Faculty load assignment does not remove or hide accounts from this list.
     */
    public function getTeacherAssignedSubjects(int $id)
    {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, $id);
            $connection = $schoolLevel === 'grade_school' ? 'mysql_gs' : 'mysql_jh';
            $subjects = ($user->role?->name === 'shared_teacher')
                ? \App\Support\SharedTeacherSupport::assignedSubjectsForFaculty($connection, $user->id, $schoolLevel)
                : [];

            return response()->json(['success' => true, 'subjects' => $subjects]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            Log::error('Get teacher assigned subjects error: ' . $e->getMessage());

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
            Log::error('Get teachers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching teachers: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update teacher information (with school_level verification)
     */
    public function updateTeacher(Request $request, $id) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:100|unique:users,email,' . $id,
                'department' => 'nullable|string|max:100',
            ]);

            $validated = \App\Support\AdminUserAccountsSupport::withNormalizedNames($validated);
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, (int) $id);
            $user->update($validated);
            return response()->json(['success' => true, 'message' => 'Teacher updated successfully', 'data' => $user]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            Log::error('Update teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete teacher (with school_level verification)
     */
    public function deleteTeacher($id) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $user = \App\Support\AdminUserAccountsSupport::findUserAccount($schoolLevel, (int) $id);
            \App\Support\UserSchoolDataPurge::purge($user);
            $user->delete();

            return response()->json(['success' => true, 'message' => 'Teacher deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            Log::error('Delete teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Toggle teacher active/inactive status
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
            Log::error('Toggle teacher active error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating account status'], 400);
        }
    }

    /**
     * Add a new room (assigned to admin's school_level)
     */
    public function addRoom(Request $request) {
        try {
            $schoolLevel = $this->getAdminSchoolLevel();
            $validated = $request->validate([
                'room_number' => 'nullable|string|max:50|unique:rooms',
                'building' => 'nullable|string|max:100',
                'capacity' => 'required|integer|min:1|max:200',
                'features' => 'nullable|string|max:255',
                'status' => 'required|in:available,unavailable,maintenance',
            ]);

            $room = Room::create($validated);
            return response()->json(['success' => true, 'message' => 'Room added successfully', 'data' => $room], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Add room error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error adding room: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Get all rooms (filtered by school_level)
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
            Log::error('Get rooms error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching rooms: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update room information (with school_level verification)
     */
    public function updateRoom(Request $request, $id) {
        try {
            $validated = $request->validate([
                'room_number' => 'nullable|string|max:50|unique:rooms,room_number,' . $id,
                'building' => 'nullable|string|max:100',
                'capacity' => 'nullable|integer|min:1|max:200',
                'status' => 'nullable|in:available,unavailable,maintenance',
            ]);

            $room = Room::findOrFail($id);
            $room->update($validated);
            return response()->json(['success' => true, 'message' => 'Room updated successfully', 'data' => $room]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Room not found'], 404);
        } catch (\Exception $e) {
            Log::error('Update room error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating room: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete room (with school_level verification)
     */
    public function deleteRoom($id) {
        try {
            $room = Room::findOrFail($id);
            $room->delete();
            return response()->json(['success' => true, 'message' => 'Room deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Room not found'], 404);
        } catch (\Exception $e) {
            Log::error('Delete room error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting room: ' . $e->getMessage()], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Teacher portal data (subject_assignments, etc.) on mysql_jh admin DB
    // Admin needs to see what teachers are requesting / assigning.
    // -------------------------------------------------------------------------

    /**
     * GET api/admin/teacher/adjustment-requests
     * List all schedule adjustment requests submitted by JH teachers.
     */
    public function getTeacherAdjustmentRequests(Request $request)
    {
        try {
            $query = DB::connection('mysql_jh')
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

    /**
     * POST api/admin/teacher/adjustment-requests/{id}/approve
     * Approve a JH teacher's adjustment request.
     */
    public function approveTeacherAdjustmentRequest(Request $request, $id)
    {
        try {
            $row = DB::connection('mysql_jh')->table('teacher_requests')->where('id', $id)->first();
            if (! $row) {
                return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
            }

            $applyMsg = '';
            if (! \App\Support\TeacherPresenceSupport::isAbsenceLeaveType($row->request_type ?? null)) {
                $result = \App\Support\TeacherAdjustmentRequestSupport::applyApprovedToSchedule(
                    'mysql_jh',
                    $row,
                    \App\Support\TeacherAdjustmentRequestSupport::reviewerDisplayName(Auth::id())
                );
                $applyMsg = $result['applied'] ? ' ' . $result['message'] : ' (' . $result['message'] . ')';
            }

            DB::connection('mysql_jh')
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
                'mysql_jh',
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
     * POST api/admin/teacher/adjustment-requests/{id}/reject
     * Reject a JH teacher's adjustment request.
     */
    public function rejectTeacherAdjustmentRequest(Request $request, $id)
    {
        try {
            $row = DB::connection('mysql_jh')->table('teacher_requests')->where('id', $id)->first();

            DB::connection('mysql_jh')
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
                    'mysql_jh',
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

    public function getTeacherLeaveRequests(Request $request)
    {
        try {
            $data = \App\Support\TeacherLeaveRequestSupport::listForAdmin('mysql_jh');

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveTeacherLeaveRequest(Request $request, $id)
    {
        $result = \App\Support\TeacherLeaveRequestSupport::reviewApi(
            'mysql_jh',
            (int) $id,
            'approved',
            $request->input('admin_notes')
        );

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function rejectTeacherLeaveRequest(Request $request, $id)
    {
        $result = \App\Support\TeacherLeaveRequestSupport::reviewApi(
            'mysql_jh',
            (int) $id,
            'rejected',
            $request->input('admin_notes')
        );

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * GET api/admin/teacher/subject-assignments
     * List all subject assignments made by JH teachers.
     */
    public function getTeacherSubjectAssignments(Request $request)
    {
        try {
            $assignments = DB::connection('mysql_jh')
                ->table('subject_assignments')
                ->orderBy('created_at', 'desc')
                ->get();

            // Enrich with teacher names from main DB
            $userIds = $assignments->pluck('faculty_id')->merge($assignments->pluck('assigned_by'))->unique()->filter();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $assignments = $assignments->map(function ($a) use ($users) {
                $faculty  = $users->get($a->faculty_id);
                $assigner = $users->get($a->assigned_by);
                $a->faculty_name  = $faculty  ? trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? '')) : 'Unknown';
                $a->assigned_by_name = $assigner ? trim(($assigner->first_name ?? '') . ' ' . ($assigner->last_name ?? '')) : null;
                return $a;
            });

            return response()->json(['success' => true, 'data' => $assignments]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate institution-wide report output for Junior High admin.
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
            $filename = sprintf('%s_junior_high_%s.csv', $type, now()->format('Ymd_His'));

            $this->logExport($type, $filename, strlen($csv), count($rows));

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('JH report generation error: ' . $e->getMessage());
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
            Log::error('JH report history error: ' . $e->getMessage());
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
                'room' => $room?->room_number ?? 'TBD',
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
        $facultyCount = User::where('school_level', 'junior_high')
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
            'scope' => 'junior_high_admin',
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
                'scope' => 'junior_high_admin',
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

        $schedules   = DB::connection($connection)->table('class_schedules')->get()->toArray();
        $facultyLoads = DB::connection($connection)->table('faculty_loads')->get()->toArray();

        $data = [
            'exported_at'  => now()->toISOString(),
            'school'       => 'junior_high',
            'schedules'    => array_map(fn($r) => (array) $r, $schedules),
            'faculty_loads' => array_map(fn($r) => (array) $r, $facultyLoads),
        ];

        $filename = 'jh_backup_' . now()->format('Y-m-d_His') . '.json';
        $dir      = storage_path('app/backups/jh');
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
            $exists = $db->table('class_schedules')->where('id', $id)->exists();
            if ($exists) {
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
            $exists = $db->table('faculty_loads')->where('id', $id)->exists();
            if ($exists) {
                $db->table('faculty_loads')->where('id', $id)->update($row);
            } else {
                $db->table('faculty_loads')->insert(array_merge(['id' => $id], $row));
            }
            $restoredLoads++;
        }

        return redirect()->route('admin.settings', ['tab' => 'backup'])
            ->with('success', "Restore complete — {$restoredSchedules} schedule records and {$restoredLoads} faculty-load records restored.");
    }
}