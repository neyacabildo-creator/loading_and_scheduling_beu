<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\PermissionRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PrincipalController extends Controller
{
    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        try {
            // Accurate role-based counts
            $jhAdmins     = User::whereHas('role', fn($q) => $q->where('name', 'admin_junior_high'))->where('is_active', true)->count();
            $gsAdmins     = User::whereHas('role', fn($q) => $q->where('name', 'admin_grade_school'))->where('is_active', true)->count();
            $totalAdmins  = $jhAdmins + $gsAdmins;

            $jhFaculty    = User::whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))->where('school_level', 'junior_high')->where('is_active', true)->count();
            $gsFaculty    = User::whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))->where('school_level', 'grade_school')->where('is_active', true)->count();
            $totalFaculty = $jhFaculty + $gsFaculty;

            $inactiveUsers   = User::where('is_active', false)->count();
            $totalUsers      = User::count();
            $pendingRequests = PermissionRequest::where('status', 'pending')->count();

            // Schedule counts from school DBs
            $gsSchedules = $this->safeDbCount('mysql_gs', 'class_schedules');
            $jhSchedules = $this->safeDbCount('mysql_jh', 'class_schedules');

            // Schedules that are admin-approved but still awaiting Principal (principal) review
            $pendingSchedules = $this->countPrincipalPendingSchedules('mysql_jh')
                + $this->countPrincipalPendingSchedules('mysql_gs');

            $principalScheduleFlags = \App\Support\ScheduleDssSupport::principalPendingScheduleFlags();

            // Rooms from both school DBs (Room model uses UseSchoolConnection which falls back
            // to default DB in principal context — query directly instead)
            $jhRooms     = $this->safeDbCount('mysql_jh', 'rooms');
            $gsRooms     = $this->safeDbCount('mysql_gs', 'rooms');
            $totalRooms  = $jhRooms + $gsRooms;

            // Fetch room rows for the overview table
            try {
                $jhRoomsData = DB::connection('mysql_jh')->table('rooms')
                    ->select('room_number', 'building', 'capacity', 'features', 'status')
                    ->orderBy('room_number')->get()
                    ->map(fn($r) => (array)$r + ['school' => 'Junior High']);
            } catch (\Exception $e) { $jhRoomsData = collect(); }
            try {
                $gsRoomsData = DB::connection('mysql_gs')->table('rooms')
                    ->select('room_number', 'building', 'capacity', 'features', 'status')
                    ->orderBy('room_number')->get()
                    ->map(fn($r) => (array)$r + ['school' => 'Grade School']);
            } catch (\Exception $e) { $gsRoomsData = collect(); }
            $roomsData = collect($jhRoomsData)->merge(collect($gsRoomsData));

            // Load recent permission requests with users manually (avoids cross-DB eager-load issues)
            $recentRequests = PermissionRequest::orderByDesc('created_at')->limit(5)->get();
            $rIds = $recentRequests->pluck('requester_id')->filter()->unique();
            $rUsers = $rIds->isNotEmpty() ? User::whereIn('id', $rIds)->get()->keyBy('id') : collect();
            $recentRequests->each(fn($r) => $r->setRelation('requester', $rUsers->get($r->requester_id)));

            return view('principal.dashboard', compact(
                'totalFaculty', 'totalAdmins',
                'jhFaculty', 'gsFaculty',
                'jhAdmins', 'gsAdmins',
                'inactiveUsers', 'totalUsers',
                'pendingRequests',
                'gsSchedules', 'jhSchedules',
                'pendingSchedules',
                'principalScheduleFlags',
                'totalRooms', 'jhRooms', 'gsRooms', 'roomsData',
                'recentRequests'
            ));
        } catch (\Exception $e) {
            Log::error('Principal dashboard error: ' . $e->getMessage());
            return view('principal.dashboard', [
                'totalFaculty'     => 0, 'totalAdmins'  => 0,
                'jhFaculty'        => 0, 'gsFaculty'    => 0,
                'jhAdmins'         => 0, 'gsAdmins'     => 0,
                'inactiveUsers'    => 0, 'totalUsers'   => 0,
                'pendingRequests'  => 0,
                'gsSchedules'      => 0, 'jhSchedules'  => 0,
                'pendingSchedules' => 0,
                'principalScheduleFlags' => ['total_pending' => 0, 'with_policy_flags' => 0],
                'totalRooms'       => 0, 'jhRooms' => 0, 'gsRooms' => 0,
                'roomsData'        => collect(),
                'recentRequests'   => collect(),
            ]);
        }
    }

    /** Count rows in a school DB table, with optional where conditions. */
    private function safeDbCount(string $connection, string $table, array $where = []): int
    {
        try {
            $query = DB::connection($connection)->table($table);
            foreach ($where as $col => $val) {
                $query->where($col, $val);
            }
            return (int) $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function countPrincipalPendingSchedules(string $connection): int
    {
        try {
            $query = DB::connection($connection)->table('class_schedules')
                ->where('admin_approved', true)
                ->where('principal_approved', false)
                ->whereIn('status', ['active', 'approved']);

            if (Schema::connection($connection)->hasColumn('class_schedules', 'principal_approved_by')) {
                $query->whereNull('principal_approved_by');
            }

            return (int) $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    // ── Permission Requests ──────────────────────────────────────────────────

    /** List all requests (Principal view) */
    public function permissionRequests(Request $request)
    {
        $query = PermissionRequest::orderByRaw("FIELD(status, 'pending', 'approved', 'rejected', 'cancelled')")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20);

        // Manually load users across DB connections to avoid cross-DB eager-load issues
        $userIds = $requests->pluck('requester_id')
            ->merge($requests->pluck('reviewed_by'))
            ->filter()->unique();
        $users = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();
        $requests->each(function ($req) use ($users) {
            $req->setRelation('requester', $users->get($req->requester_id));
            $req->setRelation('reviewer',  $users->get($req->reviewed_by));
        });

        return view('principal.permission-requests', compact('requests'));
    }

    /** Approve a permission request and optionally leave a tip/note */
    public function approveRequest(Request $request, PermissionRequest $permissionRequest)
    {
        $data = $request->validate([
            'reviewer_notes' => 'nullable|string|max:1000',
        ]);

        $permissionRequest->update([
            'status'         => 'approved',
            'reviewed_by'    => Auth::id(),
            'reviewer_notes' => $data['reviewer_notes'] ?? null,
            'reviewed_at'    => now(),
        ]);

        $permissionRequest->refresh();
        \App\Support\AdminPortalNotificationSupport::notifyPrincipalPermissionDecision(
            $permissionRequest,
            'approved'
        );

        return back()->with('success', 'Request approved and admin has been notified.');
    }

    /** Reject a permission request with a mandatory note / tip */
    public function rejectRequest(Request $request, PermissionRequest $permissionRequest)
    {
        $data = $request->validate([
            'reviewer_notes' => 'required|string|max:1000',
        ]);

        $permissionRequest->update([
            'status'         => 'rejected',
            'reviewed_by'    => Auth::id(),
            'reviewer_notes' => $data['reviewer_notes'],
            'reviewed_at'    => now(),
        ]);

        $permissionRequest->refresh();
        \App\Support\AdminPortalNotificationSupport::notifyPrincipalPermissionDecision(
            $permissionRequest,
            'rejected'
        );

        return back()->with('success', 'Request rejected. Admin has been given your guidance.');
    }

    // ── User Management (all levels) ─────────────────────────────────────────

    public function users()
    {
        $users = User::with('role')
            ->orderBy('school_level')
            ->orderBy('name')
            ->paginate(30);

        $users->getCollection()->transform(function (User $user) {
            $row = $this->formatUserForPrincipalTable($user);
            $user->setAttribute('display_password', $row['display_password']);
            $user->setAttribute('role_label', $row['role_label']);

            return $user;
        });

        $roles = Role::query()
            ->whereIn('name', \App\Support\AuthRedirectSupport::PRINCIPAL_ASSIGNABLE_ROLE_NAMES)
            ->orderBy('name')
            ->get();

        return view('principal.users', compact('users', 'roles'));
    }

    /** Create any user type (admin or teacher). Only Principals can call this. */
    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:255|unique:users,email',
            'role_id'      => 'required|exists:roles,id',
            'school_level' => 'nullable|in:grade_school,junior_high',
            'password'     => \App\Support\SecurePassword::rules(),
            'subject1'     => 'nullable|string|max:191',
            'subject2'     => 'nullable|string|max:191',
        ]);

        $role = Role::findOrFail($data['role_id']);

        if ($role->name === 'principal') {
            return back()->withErrors(['role_id' => 'Principal accounts cannot be created through this interface.'])->withInput();
        }

        if (! in_array($role->name, \App\Support\AuthRedirectSupport::PRINCIPAL_ASSIGNABLE_ROLE_NAMES, true)) {
            return back()->withErrors(['role_id' => 'This role cannot be assigned through the principal portal.'])->withInput();
        }

        $schoolLevel = \App\Support\PrincipalUserProvisioningSupport::resolveSchoolLevel($role, $data['school_level'] ?? null);
        if ($schoolLevel === null && \App\Support\PrincipalUserProvisioningSupport::requiresSchoolLevel($role->name)) {
            return back()->withErrors([
                'school_level' => 'School level is required for this role (Grade School or Junior High).',
            ])->withInput();
        }

        $role = \App\Support\PrincipalUserProvisioningSupport::normalizeAssignableRole($role, $schoolLevel);

        $user = User::create([
            'name'         => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'role_id'      => $role->id,
            'school_level' => $schoolLevel,
            'is_active'    => true,
        ]);

        \App\Support\UserPasswordSupport::storeEncryptedCopy($user->id, $data['password']);
        $user->load('role');
        \App\Support\PrincipalUserProvisioningSupport::provisionNewUser($user, $role, $request);
        \App\Support\AuthRedirectSupport::repairAccountForPortalAccess($user);

        $portal = \App\Support\AuthRedirectSupport::portalLabelForUser($user->fresh(['role']));

        return redirect()->route('principal.users')->with(
            'success',
            "Account created successfully. The user can sign in and will be directed to the {$portal}."
        );
    }

    /** Toggle active/inactive for any user */
    public function toggleUserActive(User $user)
    {
        // Prevent Principal from deactivating themselves
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $state = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User has been {$state}.");
    }

    /**
     * Update a user's name, email, role, and optionally password.
     */
    public function updateUser(Request $request, User $user)
    {
        $previousRoleName = $user->role?->name;

        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id'      => 'nullable|exists:roles,id',
            'school_level' => 'nullable|in:grade_school,junior_high',
            'password'     => \App\Support\SecurePassword::optionalRules(),
            'subject1'     => 'nullable|string|max:191',
            'subject2'     => 'nullable|string|max:191',
        ]);

        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'];
        $user->name       = trim($data['first_name'] . ' ' . $data['last_name']);
        $user->email      = $data['email'];

        $newRole = null;
        if (! empty($data['role_id'])) {
            $newRole = Role::find((int) $data['role_id']);
            if ($newRole && $newRole->name === 'principal') {
                return back()->withErrors(['role_id' => 'Principal role cannot be assigned here.'])->withInput();
            }
            if ($newRole && ! in_array($newRole->name, \App\Support\AuthRedirectSupport::PRINCIPAL_ASSIGNABLE_ROLE_NAMES, true)) {
                return back()->withErrors(['role_id' => 'This role cannot be assigned through the principal portal.'])->withInput();
            }
        }

        $roleForLevel = $newRole ?? $user->role;
        if ($roleForLevel) {
            $resolved = \App\Support\PrincipalUserProvisioningSupport::resolveSchoolLevel(
                $roleForLevel,
                $data['school_level'] ?? $user->school_level
            );
            if ($resolved === null && \App\Support\PrincipalUserProvisioningSupport::requiresSchoolLevel($roleForLevel->name)) {
                return back()->withErrors([
                    'school_level' => 'School level is required for this role.',
                ])->withInput();
            }
            $user->school_level = $resolved;
            $roleForLevel = \App\Support\PrincipalUserProvisioningSupport::normalizeAssignableRole($roleForLevel, $resolved);
        } elseif (array_key_exists('school_level', $data)) {
            $user->school_level = $data['school_level'] ?: null;
        }

        if ($newRole) {
            $user->role_id = $roleForLevel->id;
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->load('role');

        if (! empty($data['password'])) {
            \App\Support\UserPasswordSupport::storeEncryptedCopy($user->id, $data['password']);
        }

        if ($user->role) {
            \App\Support\PrincipalUserProvisioningSupport::syncAfterUpdate(
                $user,
                $user->role,
                $request,
                $previousRoleName
            );
        }

        $user->refresh();
        $user->load('role');
        \App\Support\AuthRedirectSupport::repairAccountForPortalAccess($user);
        $user->refresh();
        $user->load('role');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Account for {$user->name} updated successfully.",
                'user'    => $this->formatUserForPrincipalTable($user),
            ]);
        }

        return redirect()
            ->route('principal.users', ['page' => $request->input('page')])
            ->with('success', "Account for {$user->name} updated successfully.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserForPrincipalTable(User $user): array
    {
        $user->loadMissing('role');

        $plain = \App\Support\UserPasswordSupport::decryptPlainPassword($user->id);
        if ($plain === null && ($user->role?->name ?? '') === 'principal') {
            $plain = \App\Support\UserPassword::plainText($user);
        }

        $roleName = $user->role?->name ?? '';

        return [
            'id'                 => $user->id,
            'first_name'         => $user->first_name,
            'last_name'          => $user->last_name,
            'name'               => $user->name,
            'email'              => $user->email,
            'display_password'   => $plain,
            'role_id'            => $user->role_id,
            'role_name'          => $roleName,
            'role_label'         => $user->role?->display_name ?? ucfirst(str_replace('_', ' ', $roleName)),
            'school_level'       => $user->school_level,
            'school_level_label' => $user->school_level
                ? ucfirst(str_replace('_', ' ', $user->school_level))
                : '—',
            'is_active'          => (bool) $user->is_active,
        ];
    }

    /** Permanently delete a user account (irreversible). */
    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->role?->name === 'principal') {
            return back()->with('error', 'Principal accounts cannot be deleted through this interface.');
        }

        $name = $user->name;
        \App\Support\UserSchoolDataPurge::purge($user);
        $user->delete();

        return back()->with('success', "Account for {$name} has been permanently deleted.");
    }

    /** API: system-wide stats */
    public function apiStats()
    {
        return response()->json([
            'total_faculty'       => User::whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))->where('is_active', true)->count(),
            'total_admins'        => User::whereHas('role', fn($q) => $q->whereIn('name', ['admin_grade_school', 'admin_junior_high', 'admin']))->where('is_active', true)->count(),
            'pending_requests'    => PermissionRequest::where('status', 'pending')->count(),
            'gs_schedules'        => $this->safeDbCount('mysql_gs', 'class_schedules'),
            'jh_schedules'        => $this->safeDbCount('mysql_jh', 'class_schedules'),
            'pending_schedules'   => $this->safeDbCount('mysql_gs', 'class_schedules', ['admin_approved' => 0, 'status' => 'pending'])
                                   + $this->safeDbCount('mysql_jh', 'class_schedules', ['admin_approved' => 0, 'status' => 'pending']),
            'inactive_users'      => User::where('is_active', false)->count(),
            'total_users'         => User::count(),
        ]);
    }

    // ── System Logs (cross-level) ────────────────────────────────────────────

    public function systemLogs()
    {
        $allLogs  = collect();
        $userIds  = collect();

        foreach (['mysql_jh' => 'Junior High', 'mysql_gs' => 'Grade School'] as $conn => $schoolLabel) {
            try {
                $rawLogs = DB::connection($conn)
                    ->table('audit_logs')
                    ->orderByDesc('changed_at')
                    ->limit(100)
                    ->get();

                foreach ($rawLogs as $log) {
                    if (is_numeric($log->changed_by)) {
                        $userIds->push((int) $log->changed_by);
                    }
                    foreach (['old_data', 'new_data'] as $col) {
                        $payload = json_decode($log->{$col} ?? '', true);
                        if (is_array($payload)) {
                            foreach (['approved_by', 'faculty_id', 'submitted_by', 'reviewed_by'] as $field) {
                                if (isset($payload[$field]) && is_numeric($payload[$field])) {
                                    $userIds->push((int) $payload[$field]);
                                }
                            }
                        }
                    }
                    $allLogs->push((object) array_merge((array) $log, ['_school' => $schoolLabel, '_conn' => $conn]));
                }
            } catch (\Exception $e) {
                Log::warning("Principal system logs – could not read {$conn}: " . $e->getMessage());
            }
        }

        $uniqueIds = $userIds->unique()->filter()->values();
        $users = $uniqueIds->isNotEmpty()
            ? User::whereIn('id', $uniqueIds)->get()->keyBy('id')
            : collect();

        $logs = $allLogs
            ->sortByDesc('changed_at')
            ->take(200)
            ->values()
            ->map(function ($log) use ($users) {
                $action = strtoupper((string) $log->action);
                return [
                    'timestamp'   => $log->changed_at,
                    'school'      => $log->_school,
                    'user'        => \App\Support\ScheduleAudit::resolveUserDisplay($log->changed_by, $users),
                    'table'       => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->table_name)),
                    'action'      => $action,
                    'record'      => $log->record_id ? '#' . $log->record_id : 'N/A',
                    'details'     => \App\Support\ScheduleAudit::summarizeAuditLog((array) $log, $users),
                    'level_class' => match ($action) {
                        'DELETE' => 'level-error',
                        'UPDATE' => 'level-warning',
                        default  => 'level-info',
                    },
                ];
            });

        return view('principal.system-logs', compact('logs'));
    }

    /** Teacher activity logs — Junior High school DB */
    public function teacherLogsJH()
    {
        return $this->buildTeacherLogs('mysql_jh', 'Junior High');
    }

    /** Teacher activity logs — Grade School DB */
    public function teacherLogsGS()
    {
        return $this->buildTeacherLogs('mysql_gs', 'Grade School');
    }

    private function buildTeacherLogs(string $conn, string $schoolLabel)
    {
        // Get IDs of all users with a teacher role
        $teacherIds = User::whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
            ->pluck('id')
            ->toArray();

        $logs = collect();

        try {
            $rawLogs = DB::connection($conn)
                ->table('audit_logs')
                ->when(!empty($teacherIds), fn($q) => $q->whereIn('changed_by', $teacherIds))
                ->orderByDesc('changed_at')
                ->limit(200)
                ->get();

            $users = !empty($teacherIds)
                ? User::whereIn('id', $teacherIds)->get()->keyBy('id')
                : collect();

            $logs = $rawLogs->map(function ($log) use ($users) {
                $action = strtoupper((string) $log->action);
                return [
                    'timestamp'   => $log->changed_at,
                    'user'        => \App\Support\ScheduleAudit::resolveUserDisplay($log->changed_by, $users),
                    'table'       => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->table_name)),
                    'action'      => $action,
                    'record'      => $log->record_id ? '#' . $log->record_id : 'N/A',
                    'details'     => \App\Support\ScheduleAudit::summarizeAuditLog((array) $log, $users),
                    'level_class' => match ($action) {
                        'DELETE' => 'level-error',
                        'UPDATE' => 'level-warning',
                        default  => 'level-info',
                    },
                ];
            });
        } catch (\Exception $e) {
            Log::warning("Teacher logs [{$conn}] error: " . $e->getMessage());
        }

        return view('principal.teacher-logs', compact('logs', 'schoolLabel'));
    }

    // ── Schedule Approvals ───────────────────────────────────────────────────

    public function schedulePendingApprovals()
    {
        $jhSchedules = $this->fetchPendingPrincipalApprovals('mysql_jh');
        $gsSchedules = $this->fetchPendingPrincipalApprovals('mysql_gs');

        return view('principal.schedule-approvals', compact('jhSchedules', 'gsSchedules'));
    }

    public function approveSchedule(Request $request, string $school, int $id)
    {
        $conn = $this->resolveSchoolConnection($school);
        if (!$conn) {
            return response()->json(['success' => false, 'message' => 'Invalid school level.'], 422);
        }

        try {
            $schedule = DB::connection($conn)->table('class_schedules')->where('id', $id)->first();
            if (! $schedule) {
                return response()->json(['success' => false, 'message' => 'Schedule not found.'], 404);
            }

            DB::connection($conn)->table('class_schedules')->where('id', $id)->update([
                'principal_approved'    => true,
                'principal_approved_at' => now(),
                'principal_approved_by' => Auth::id(),
            ]);

            try {
                \App\Support\PrincipalScheduleNotificationSupport::afterApprove($conn, $schedule);
            } catch (\Throwable $notifyErr) {
                Log::warning("Principal approveSchedule notify [{$conn}] #{$id}: " . $notifyErr->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Schedule approved. Admin and teacher have been notified.']);
        } catch (\Exception $e) {
            Log::error("Principal approveSchedule [{$conn}] #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Approval failed: ' . $e->getMessage()], 500);
        }
    }

    public function rejectSchedule(Request $request, string $school, int $id)
    {
        $conn = $this->resolveSchoolConnection($school);
        if (!$conn) {
            return response()->json(['success' => false, 'message' => 'Invalid school level.'], 422);
        }

        try {
            $schedule = DB::connection($conn)->table('class_schedules')->where('id', $id)->first();
            if (! $schedule) {
                return response()->json(['success' => false, 'message' => 'Schedule not found.'], 404);
            }

            DB::connection($conn)->table('class_schedules')->where('id', $id)->update([
                'principal_approved'    => false,
                'principal_approved_at' => now(),
                'principal_approved_by' => Auth::id(),
            ]);

            try {
                \App\Support\PrincipalScheduleNotificationSupport::afterReject($conn, $schedule);
            } catch (\Throwable $notifyErr) {
                Log::warning("Principal rejectSchedule notify [{$conn}] #{$id}: " . $notifyErr->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Schedule rejected. School admin has been notified.']);
        } catch (\Exception $e) {
            Log::error("Principal rejectSchedule [{$conn}] #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Rejection failed: ' . $e->getMessage()], 500);
        }
    }

    private function resolveSchoolConnection(string $school): ?string
    {
        return match ($school) {
            'jh'    => 'mysql_jh',
            'gs'    => 'mysql_gs',
            default => null,
        };
    }

    private function fetchPendingPrincipalApprovals(string $conn): \Illuminate\Support\Collection
    {
        try {
            $query = DB::connection($conn)
                ->table('class_schedules')
                ->where('admin_approved', true)
                ->where('principal_approved', false)
                ->whereIn('status', ['active', 'approved']);

            if (Schema::connection($conn)->hasColumn('class_schedules', 'principal_approved_by')) {
                $query->whereNull('principal_approved_by');
            }

            $rows = $query->orderByDesc('approved_at')->get();

            // Enrich with faculty and room names
            $facultyIds = $rows->pluck('faculty_id')->filter()->unique()->values();
            $roomIds    = $rows->pluck('room_id')->filter()->unique()->values();

            $faculty = $facultyIds->isNotEmpty()
                ? User::whereIn('id', $facultyIds)->get()->keyBy('id')
                : collect();

            $rooms = $roomIds->isNotEmpty()
                ? DB::connection($conn)->table('rooms')->whereIn('id', $roomIds)->get()->keyBy('id')
                : collect();

            $approverIds = $rows->pluck('approved_by')->filter()->unique();
            $approvers   = $approverIds->isNotEmpty()
                ? User::whereIn('id', $approverIds)->get()->keyBy('id')
                : collect();

            return $rows->map(function ($row) use ($faculty, $rooms, $approvers) {
                $teacher = $faculty->get($row->faculty_id);
                $row->faculty_user     = $teacher;
                $row->faculty_name     = $teacher
                    ? \App\Support\UserProfileSupport::displayName($teacher)
                    : 'N/A';
                $row->room_label     = $rooms->get($row->room_id)?->room_number ?? '—';
                $row->approver_name  = $approvers->get($row->approved_by)?->name ?? 'N/A';
                // Compute grade/section display
                $grade   = $row->grade_level   ?? '';
                $section = $row->section_name  ?? '';
                $row->grade_section = trim($grade . ($section ? ' - ' . $section : ''), ' -') ?: ($grade ?: 'N/A');
                return $row;
            });
        } catch (\Exception $e) {
            Log::warning("fetchPendingPrincipalApprovals [{$conn}]: " . $e->getMessage());
            return collect();
        }
    }

    // ── Database Overview & System Settings ──────────────────────────────────

    /** Database overview: per-DB row counts + system settings */
    public function database()
    {
        // Row counts per database / table
        $dbStats = [
            'main' => [
                'label'      => 'Main Database',
                'connection' => 'mysql',
                'tables'     => [
                    'users'  => $this->safeDbCount('mysql', 'users'),
                    'roles'  => $this->safeDbCount('mysql', 'roles'),
                    'rooms'  => $this->safeDbCount('mysql', 'rooms'),
                ],
            ],
            'jh' => [
                'label'      => 'Junior High Database',
                'connection' => 'mysql_jh',
                'tables'     => [
                    'class_schedules' => $this->safeDbCount('mysql_jh', 'class_schedules'),
                    'faculty_loads'   => $this->safeDbCount('mysql_jh', 'faculty_loads'),
                    'audit_logs'      => $this->safeDbCount('mysql_jh', 'audit_logs'),
                ],
            ],
            'gs' => [
                'label'      => 'Grade School Database',
                'connection' => 'mysql_gs',
                'tables'     => [
                    'class_schedules' => $this->safeDbCount('mysql_gs', 'class_schedules'),
                    'faculty_loads'   => $this->safeDbCount('mysql_gs', 'faculty_loads'),
                    'audit_logs'      => $this->safeDbCount('mysql_gs', 'audit_logs'),
                ],
            ],
            'principal' => [
                'label'      => 'Principal Database',
                'connection' => 'mysql_principal',
                'tables'     => [
                    'permission_requests' => $this->safeDbCount('mysql_principal', 'permission_requests'),
                    'system_settings'     => $this->safeDbCount('mysql_principal', 'system_settings'),
                ],
            ],
            'jh_teacher_portal' => [
                'label'      => 'JH Teacher Portal (admin DB)',
                'connection' => 'mysql_jh',
                'tables'     => [
                    'teacher_requests'          => $this->safeDbCount('mysql_jh', 'teacher_requests'),
                    'teacher_feedbacks'         => $this->safeDbCount('mysql_jh', 'teacher_feedbacks'),
                    'subject_assignments'       => $this->safeDbCount('mysql_jh', 'subject_assignments'),
                    'teacher_loading_schedules' => $this->safeDbCount('mysql_jh', 'teacher_loading_schedules'),
                ],
            ],
            'gs_teacher_portal' => [
                'label'      => 'GS Teacher Portal (admin DB)',
                'connection' => 'mysql_gs',
                'tables'     => [
                    'teacher_requests'          => $this->safeDbCount('mysql_gs', 'teacher_requests'),
                    'teacher_feedbacks'         => $this->safeDbCount('mysql_gs', 'teacher_feedbacks'),
                    'subject_assignments'       => $this->safeDbCount('mysql_gs', 'subject_assignments'),
                    'teacher_loading_schedules' => $this->safeDbCount('mysql_gs', 'teacher_loading_schedules'),
                ],
            ],
        ];

        // System settings from principal DB
        $settings = collect();
        try {
            $settings = DB::connection('mysql_principal')
                ->table('system_settings')
                ->orderBy('group')
                ->orderBy('key')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load system_settings: ' . $e->getMessage());
        }

        // Group settings by their 'group' column for display
        $settingsByGroup = $settings->groupBy('group');

        return view('principal.database', compact('dbStats', 'settings', 'settingsByGroup'));
    }

    /** Update a single system setting value (only if is_editable = true). */
    public function updateSetting(Request $request, string $key)
    {
        // Sanitise key to prevent injection — only allow alphanumeric and underscores
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            return back()->with('error', 'Invalid setting key.');
        }

        $data = $request->validate([
            'value' => 'nullable|string|max:500',
        ]);

        try {
            $setting = DB::connection('mysql_principal')
                ->table('system_settings')
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return back()->with('error', 'Setting not found.');
            }

            if (!$setting->is_editable) {
                return back()->with('error', 'This setting is read-only.');
            }

            DB::connection('mysql_principal')
                ->table('system_settings')
                ->where('key', $key)
                ->update([
                    'value'      => $data['value'] ?? '',
                    'updated_at' => now(),
                ]);

            return back()->with('success', 'Setting "' . $key . '" updated successfully.');
        } catch (\Exception $e) {
            Log::error('updateSetting error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update setting. Please try again.');
        }
    }
}
