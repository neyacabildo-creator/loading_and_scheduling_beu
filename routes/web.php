<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
// RegisteredUserController removed — public self-registration is disabled.
// Accounts are created exclusively by Principals via /principal/users.
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FacultyLoadController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Verification endpoint — restricted to principal; no longer a public info-leak
Route::middleware(['auth', 'principal.admin'])->get('/api/verify-setup', function () {
    $roles = \App\Models\Role::all();
    $users = \App\Models\User::with('role')->get();

    return response()->json([
        'roles' => $roles->map(fn($r) => [
            'id'           => $r->id,
            'name'         => $r->name,
            'display_name' => $r->display_name,
        ]),
        'users' => $users->map(fn($u) => [
            'id'           => $u->id,
            'email'        => $u->email,
            'name'         => $u->name,
            'role_id'      => $u->role_id,
            'school_level' => $u->school_level,
            'role'         => $u->role?->name,
        ]),
    ]);
});

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(\App\Support\AuthRedirectSupport::homeRouteName());
    }

    return redirect()->route('login');
});

// Show login page to anyone (not protected by guest middleware)
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');

// CSRF token refresh endpoint — called by login page JS to keep token alive and prevent 419
Route::get('/csrf-refresh', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.refresh');

Route::middleware('guest')->group(function () {
    // Public self-registration is disabled — accounts are created by Principals only.
    // Login: route throttle + per-email lockout in LoginRequest (5 failures / 5 min).
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('auth/heartbeat', [AuthenticatedSessionController::class, 'heartbeat'])->name('auth.heartbeat');

    // Protected API routes for admin (Junior High)
    Route::middleware(['admin', 'school.db:mysql_jh'])->prefix('api/admin')->group(function () {
        Route::get('/combined-schedules', [\App\Http\Controllers\AdminController::class, 'getCombinedSchedules']);
        Route::get('/room-for-section', [ScheduleController::class, 'getRoomForSection']);
        Route::get('/schedules', [\App\Http\Controllers\AdminController::class, 'getSchedules']);
        Route::get('/schedules/{id}', [\App\Http\Controllers\AdminController::class, 'getSchedule']);
        Route::get('/schedules/{id}/history', [\App\Http\Controllers\AdminController::class, 'getScheduleHistory']);
        Route::post('/schedules/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveSchedule']);
        Route::post('/schedules/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectSchedule']);
        Route::put('/schedules/{id}', [\App\Http\Controllers\AdminController::class, 'updateSchedule']);
        Route::delete('/schedules/{id}', [\App\Http\Controllers\AdminController::class, 'deleteSchedule']);
        Route::post('/teachers', [\App\Http\Controllers\AdminController::class, 'addTeacher']);
        Route::post('/rooms', [\App\Http\Controllers\AdminController::class, 'addRoom']);

        // Cross-DB: JH admin reading teacher data from mysql_jh_teacher
        Route::get('/teacher/adjustment-requests', [\App\Http\Controllers\AdminController::class, 'getTeacherAdjustmentRequests']);
        Route::post('/teacher/adjustment-requests/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveTeacherAdjustmentRequest']);
        Route::post('/teacher/adjustment-requests/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectTeacherAdjustmentRequest']);
        Route::get('/teacher/leave-requests', [\App\Http\Controllers\AdminController::class, 'getTeacherLeaveRequests']);
        Route::post('/teacher/leave-requests/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveTeacherLeaveRequest']);
        Route::post('/teacher/leave-requests/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectTeacherLeaveRequest']);
        Route::get('/teacher/subject-assignments', [\App\Http\Controllers\AdminController::class, 'getTeacherSubjectAssignments']);
    });
    // Teacher-only dashboard (Junior High)
    Route::middleware(['teacher', 'school.db:mysql_jh'])->group(function () {
        Route::get('teacher/dashboard', [\App\Http\Controllers\TeacherController::class, 'dashboard'])->name('teacher.dashboard');
        
        Route::get('teacher/class-schedule', function () {
            return view('junior-high-teacher.class-schedule');
        })->name('teacher.class-schedule');
        
        Route::get('teacher/my-classes', function () {
            return view('junior-high-teacher.my-classes');
        })->name('teacher.my-classes');
        
        Route::get('teacher/my-students', function () {
            return view('junior-high-teacher.my-students');
        })->name('teacher.my-students');
        
        Route::get('teacher/class-performance', function () {
            return view('junior-high-teacher.class-performance');
        })->name('teacher.class-performance');
        
        Route::get('teacher/faculty-loading', function () {
            return view('junior-high-teacher.faculty-loading');
        })->name('teacher.faculty-loading');
        
        Route::get('teacher/grade-submission', function () {
            return view('junior-high-teacher.grade-submission');
        })->name('teacher.grade-submission');
        
        Route::get('teacher/print-export', [\App\Http\Controllers\TeacherController::class, 'printExport'])->name('teacher.print-export');
        Route::get('teacher/export/schedule', [\App\Http\Controllers\TeacherController::class, 'exportSchedule'])->name('teacher.export.schedule');

        Route::get('teacher/request-adjustments', [\App\Http\Controllers\TeacherController::class, 'requestAdjustments'])->name('teacher.request-adjustments');

        Route::get('teacher/review-schedule', function () {
            return view('junior-high-teacher.review-schedule');
        })->name('teacher.review-schedule');

        Route::get('teacher/feedback', [\App\Http\Controllers\TeacherController::class, 'showFeedback'])->name('teacher.feedback');
        Route::post('teacher/feedback', [\App\Http\Controllers\TeacherController::class, 'submitFeedback'])->name('teacher.feedback.submit');

        Route::get('teacher/loading-schedule', [\App\Http\Controllers\TeacherController::class, 'showLoadingSchedule'])->name('teacher.loading-schedule');

        Route::get('teacher/settings', function () {
            return view('junior-high-teacher.settings');
        })->name('teacher.settings');

        Route::post('teacher/profile/photo', function (\Illuminate\Http\Request $request) {
            $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
            $user->save();
            return redirect()->route('teacher.settings')->with('success', 'Profile photo updated successfully.');
        })->name('teacher.profile.photo');

        Route::put('teacher/profile', function (\Illuminate\Http\Request $request) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $data = $request->validate([
                'first_name'       => 'required|string|max:100',
                'last_name'        => 'required|string|max:100',
                'email'            => 'required|email|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password|string',
                'password'         => \App\Support\SecurePassword::optionalRules(),
            ]);
            $user->first_name = $data['first_name'];
            $user->last_name  = $data['last_name'];
            $user->email      = $data['email'];
            if (! empty($data['password'])) {
                if (! \Illuminate\Support\Facades\Hash::check($data['current_password'] ?? '', $user->password)) {
                    return back()->withErrors(['current_password' => 'Current password is incorrect.']);
                }
                $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
            }
            $user->save();
            \App\Support\UserProfileSupport::syncTeacherNameReferences($user->fresh() ?? $user);
            return redirect()->route('teacher.settings')->with('success', 'Profile updated successfully.');
        })->name('teacher.profile.update');
        
        // Teacher API endpoints
        Route::get('api/teacher/classes', [\App\Http\Controllers\TeacherController::class, 'getMyClasses']);
        Route::get('api/teacher/students', [\App\Http\Controllers\TeacherController::class, 'getMyStudents']);
        Route::get('api/teacher/performance', [\App\Http\Controllers\TeacherController::class, 'getClassPerformance']);
        Route::get('api/teacher/faculty-load', [\App\Http\Controllers\TeacherController::class, 'getFacultyLoad']);
        Route::get('api/teacher/workload-history', [\App\Http\Controllers\TeacherController::class, 'getWorkloadHistory']);
        Route::get('api/teacher/grades', [\App\Http\Controllers\TeacherController::class, 'getGrades']);
        Route::post('api/teacher/grades/submit', [\App\Http\Controllers\TeacherController::class, 'submitGrades']);
        Route::get('api/teacher/schedules', [ScheduleController::class, 'getTeacherSchedules'])->name('teacher.schedules');
        Route::get('api/teacher/adjustment-requests', [\App\Http\Controllers\TeacherController::class, 'getAdjustmentRequests']);
        Route::get('api/teacher/adjustment-schedules', [\App\Http\Controllers\TeacherController::class, 'getAdjustmentScheduleOptions']);
        Route::get('api/teacher/adjustment-available-teachers', [\App\Http\Controllers\TeacherController::class, 'getAdjustmentAvailableTeachers']);
        Route::post('api/teacher/adjustment-check-slot', [\App\Http\Controllers\ScheduleDssController::class, 'checkAdjustmentSlot']);
        Route::post('api/teacher/adjustment-requests', [\App\Http\Controllers\TeacherController::class, 'storeAdjustmentRequest']);
        Route::get('api/teacher/leave-requests', [\App\Http\Controllers\TeacherController::class, 'getLeaveRequests']);
        Route::post('api/teacher/leave-requests', [\App\Http\Controllers\TeacherController::class, 'storeLeaveRequest']);
        Route::get('api/teacher/notifications', [\App\Http\Controllers\TeacherNotificationController::class, 'index']);
        Route::post('api/teacher/notifications/read', [\App\Http\Controllers\TeacherNotificationController::class, 'markRead']);
        Route::get('api/teacher/schedules-for-review', [\App\Http\Controllers\TeacherController::class, 'getSchedulesForReview']);
    });

    // Admin-only dashboard (Junior High) — uses loading_scheduling_jh
    Route::middleware(['admin', 'school.db:mysql_jh'])->group(function () {
        Route::get('admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        
        // Management features
        Route::get('admin/class-schedule', function () {
            return view('junior-high-admin.class-schedule');
        })->name('admin.class-schedule');
        
        Route::get('admin/faculty-loading', [\App\Http\Controllers\Admin\FacultyLoadingController::class, 'index'])->name('admin.faculty-loading');
        Route::get('admin/faculty-loading/create', [\App\Http\Controllers\Admin\FacultyLoadingController::class, 'create'])->name('admin.faculty-loading.create');
        Route::post('admin/faculty-loading/store', [\App\Http\Controllers\Admin\FacultyLoadingController::class, 'store'])->name('admin.faculty-loading.store');
        Route::get('admin/faculty-loading/{id}/edit', [\App\Http\Controllers\Admin\FacultyLoadingController::class, 'edit'])->name('admin.faculty-loading.edit');
        Route::put('admin/faculty-loading/{id}', [\App\Http\Controllers\Admin\FacultyLoadingController::class, 'update'])->name('admin.faculty-loading.update');

        // Master Weekly Schedule — JH Admin
        Route::get('admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'manageJH'])->name('admin.master-schedule.manage');
        Route::get('admin/master-schedule/{teacherId}/card', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'cardViewJH'])->name('admin.master-schedule.card');
        Route::get('admin/master-schedule/{teacherId}/download', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'downloadJH'])->name('admin.master-schedule.download');
        Route::post('admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'save'])->name('admin.master-schedule.save');
        Route::delete('admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'clear'])->name('admin.master-schedule.clear');
        
        Route::get('admin/print-export', [\App\Http\Controllers\AdminController::class, 'printExportSchedule'])->name('admin.print-export');

        Route::redirect('admin/reports', '/admin/print-export', 301)->name('admin.reports');
        Route::redirect('admin/reports/history', '/admin/print-export', 301)->name('admin.reports.history');
        Route::redirect('admin/reports/generate/{type}', '/admin/print-export', 301)->name('admin.reports.generate');

        Route::get('admin/users', function () {
            $users = \App\Models\User::where('school_level', 'junior_high')
                ->with('role')->latest()->get();
            $accountRoleOptions = \App\Support\AdminUserRoleSupport::roleOptionsForPortal('junior_high');

            return view('junior-high-admin.users.index', compact('users', 'accountRoleOptions'));
        })->name('admin.users');

        Route::get('admin/users/create', function () {
            return redirect()->route('admin.users');
        })->name('admin.users.create');

        Route::post('admin/users', [\App\Http\Controllers\AdminController::class, 'storeUser'])
            ->name('admin.users.store');
        Route::patch('admin/users/{user}', [\App\Http\Controllers\AdminController::class, 'updateUser'])
            ->name('admin.users.update');
        Route::patch('admin/users/{user}/toggle', [\App\Http\Controllers\AdminController::class, 'toggleUserActive'])
            ->name('admin.users.toggle');
        Route::delete('admin/users/{user}', [\App\Http\Controllers\AdminController::class, 'destroyUser'])
            ->name('admin.users.destroy');

        Route::get('admin/users/{user}/edit', function () {
            return view('junior-high-admin.users.edit');
        })->name('admin.users.edit');

        // Export routes for JH admin
        Route::get('admin/export/csv',   [\App\Http\Controllers\AdminController::class, 'exportCsv'])->name('admin.export.csv');
        Route::get('admin/export/excel', [\App\Http\Controllers\AdminController::class, 'exportExcel'])->name('admin.export.excel');

        Route::get('admin/system-logs', [\App\Http\Controllers\AdminController::class, 'systemLogs'])->name('admin.system-logs');

        Route::get('admin/settings', function () {
            $backupDir = storage_path('app/backups/jh');
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
            return view('junior-high-admin.settings.index', compact('backupFiles'));
        })->name('admin.settings');

        Route::get('admin/backup/download', [\App\Http\Controllers\AdminController::class, 'backupDownload'])->name('admin.backup.download');
        Route::post('admin/backup/restore',  [\App\Http\Controllers\AdminController::class, 'backupRestore'])->name('admin.backup.restore');

        Route::put('admin/profile', function (\Illuminate\Http\Request $request) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $data = $request->validate([
                'first_name'       => 'required|string|max:100',
                'last_name'        => 'required|string|max:100',
                'email'            => 'required|email|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password|string',
                'password'         => \App\Support\SecurePassword::optionalRules(),
            ]);
            $user->first_name = $data['first_name'];
            $user->last_name  = $data['last_name'];
            $user->email      = $data['email'];
            if (!empty($data['password'])) {
                if (!\Illuminate\Support\Facades\Hash::check($data['current_password'] ?? '', $user->password)) {
                    return back()->withErrors(['current_password' => 'Current password is incorrect.']);
                }
                $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
            }
            $user->save();
            \App\Support\UserProfileSupport::syncTeacherNameReferences($user->fresh() ?? $user);
            return redirect()->route('admin.settings')->with('success', 'Profile updated successfully.');
        })->name('admin.profile.update');

        Route::post('admin/profile/photo', function (\Illuminate\Http\Request $request) {
            $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
            $user->save();
            return redirect()->route('admin.settings')->with('success', 'Profile photo updated successfully.');
        })->name('admin.profile.photo');


        // Rooms management
        Route::get('admin/rooms-sections', [RoomController::class, 'index'])->name('admin.rooms-sections.index');
        Route::resource('admin/rooms', RoomController::class, ['names' => [
            'index' => 'admin.rooms.index',
            'create' => 'admin.rooms.create',
            'store' => 'admin.rooms.store',
            'show' => 'admin.rooms.show',
            'edit' => 'admin.rooms.edit',
            'update' => 'admin.rooms.update',
            'destroy' => 'admin.rooms.destroy',
        ]]);

        // Schedule Approval management
        Route::prefix('admin/schedule-approval')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\ScheduleApprovalController::class, 'index'])->name('admin.schedule-approval.index');
            Route::post('{schedule}/approve', [\App\Http\Controllers\Admin\ScheduleApprovalController::class, 'approve'])->name('admin.schedule-approval.approve');
            Route::post('{schedule}/reject', [\App\Http\Controllers\Admin\ScheduleApprovalController::class, 'reject'])->name('admin.schedule-approval.reject');
        });

        // Faculty Loads API endpoints
        Route::get('api/faculty-loads', [FacultyLoadController::class, 'index']);
        Route::post('api/faculty-loads', [FacultyLoadController::class, 'store']);
        Route::get('api/faculty-loads/{id}', [FacultyLoadController::class, 'show']);
        Route::put('api/faculty-loads/{id}', [FacultyLoadController::class, 'update']);
        Route::delete('api/faculty-loads/{id}', [FacultyLoadController::class, 'destroy']);
        // ── Shared Teachers Management (JH Admin) ────────────────────────────
        Route::get('admin/shared-teachers', function () {
            $sharedTeachers = \App\Models\SharedTeacher::orderBy('teacher_name')->get();
            $jhTeachers     = \App\Models\User::where('school_level', 'junior_high')
                ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
                ->orderBy('first_name')->get();
            return view('junior-high-admin.shared-teachers', compact('sharedTeachers', 'jhTeachers'));
        })->name('admin.shared-teachers.index');

        Route::post('admin/shared-teachers', function (\Illuminate\Http\Request $request) {
            $data = $request->validate([
                'faculty_id'   => 'nullable|integer',
                'teacher_name' => 'required|string|max:150',
                'email'        => 'nullable|email|max:150',
                'department'   => 'nullable|string|max:100',
                'notes'        => 'nullable|string|max:500',
            ]);
            $data['school_level'] = 'junior_high';
            $data['is_active']    = true;
            \App\Models\SharedTeacher::create($data);
            return redirect()->route('admin.shared-teachers.index')
                ->with('success', 'Shared teacher added successfully.');
        })->name('admin.shared-teachers.store');

        Route::patch('admin/shared-teachers/{id}/toggle', function ($id) {
            $st = \App\Models\SharedTeacher::findOrFail($id);
            $st->update(['is_active' => !$st->is_active]);
            return redirect()->route('admin.shared-teachers.index')
                ->with('success', $st->teacher_name . ' marked ' . ($st->is_active ? 'active' : 'inactive') . '.');
        })->name('admin.shared-teachers.toggle');

        Route::delete('admin/shared-teachers/{id}', function ($id) {
            \App\Models\SharedTeacher::findOrFail($id)->delete();
            return redirect()->route('admin.shared-teachers.index')
                ->with('success', 'Shared teacher removed.');
        })->name('admin.shared-teachers.destroy');

        // Shared Teacher schedule-request review (JH admin)
        Route::get('admin/shared-teacher-requests', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhRequests'])
            ->name('admin.shared-teacher-requests');
        Route::patch('admin/shared-teacher-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhApprove'])
            ->name('admin.shared-teacher-requests.approve');
        Route::patch('admin/shared-teacher-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhReject'])
            ->name('admin.shared-teacher-requests.reject');
        Route::patch('admin/teacher-schedule-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhApproveScheduleRequest'])
            ->name('admin.teacher-schedule-requests.approve');
        Route::patch('admin/teacher-schedule-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhRejectScheduleRequest'])
            ->name('admin.teacher-schedule-requests.reject');
        Route::patch('admin/teacher-leave-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhApproveLeaveRequest'])
            ->name('admin.teacher-leave-requests.approve');
        Route::patch('admin/teacher-leave-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminJhRejectLeaveRequest'])
            ->name('admin.teacher-leave-requests.reject');

        Route::get('admin/schedule/create', [\App\Http\Controllers\AdminController::class, 'scheduleCreate'])
            ->name('admin.schedule.create');

        // Auto Schedule Generator (JH admin)
        Route::get('admin/schedule/generate',         [\App\Http\Controllers\ScheduleGeneratorController::class, 'show'])->name('admin.schedule.generate');
        Route::post('admin/schedule/generate/preview',[\App\Http\Controllers\ScheduleGeneratorController::class, 'preview'])->name('admin.schedule.generate.preview');
        Route::post('admin/schedule/generate/confirm',[\App\Http\Controllers\ScheduleGeneratorController::class, 'confirm'])->name('admin.schedule.generate.confirm');

        // Schedule form submission (form POST)
        Route::post('admin/schedule/store', [ScheduleController::class, 'store'])->name('admin.schedule.store');
        
        // Schedule management API endpoints
        Route::get('api/admin/schedules', [ScheduleController::class, 'index'])->name('admin.schedules.index');
        Route::get('api/admin/schedules/pending', [ScheduleController::class, 'getPendingSchedules'])->name('admin.schedules.pending');
        Route::get('api/admin/schedules/{schedule}', [ScheduleController::class, 'show'])->name('admin.schedules.show');
        Route::post('api/admin/schedules', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::post('api/admin/schedules/{schedule}/approve', [ScheduleController::class, 'approve'])->name('schedule.approve');
        Route::post('api/admin/schedules/{schedule}/reject', [ScheduleController::class, 'reject'])->name('schedule.reject');
        Route::put('api/admin/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('api/admin/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
        Route::get('api/admin/schedules/{schedule}/history', [ScheduleController::class, 'getHistory'])->name('schedule.history');
        
        // Available rooms for a time slot (automated room assignment)
        Route::get('api/admin/available-rooms', [ScheduleController::class, 'getAvailableRooms'])->name('admin.available-rooms');

        // Scheduling conflicts and detection
        Route::get('api/admin/schedules/conflicts/summary', [ScheduleController::class, 'getConflictsSummary'])->name('admin.schedules.conflicts');
        Route::post('api/admin/schedules/check-duplicate', [ScheduleController::class, 'checkDuplicate'])->name('admin.schedules.check-duplicate');
        Route::post('api/admin/schedules/check-grid', [ScheduleController::class, 'checkScheduleGrid'])->name('admin.schedules.check-grid');

        // Teacher filtering by grade and subject
        Route::get('api/admin/teachers/by-grade-subject', [ScheduleController::class, 'getTeachersByGradeAndSubject'])->name('admin.teachers.by-grade-subject');

        // Faculty load availability status
        Route::get('api/admin/faculty-load-status', [ScheduleController::class, 'getFacultyLoadStatus'])->name('admin.faculty-load-status');

        Route::post('api/admin/dss/assess-slot', [\App\Http\Controllers\ScheduleDssController::class, 'assessSlot'])->name('admin.dss.assess-slot');
        Route::post('api/admin/dss/assess-faculty-load', [\App\Http\Controllers\ScheduleDssController::class, 'assessFacultyLoad'])->name('admin.dss.assess-faculty-load');

        Route::get('api/admin/notifications', [\App\Http\Controllers\AdminNotificationController::class, 'index']);
        Route::post('api/admin/notifications/read', [\App\Http\Controllers\AdminNotificationController::class, 'markRead']);

        // Rooms API endpoints
        Route::get('api/rooms', [RoomController::class, 'index']);
        Route::post('api/rooms', [RoomController::class, 'store']);
        Route::get('api/rooms/{room}', [RoomController::class, 'show']);
        Route::put('api/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('api/rooms/{room}', [RoomController::class, 'destroy']);
        
        // Teachers/Faculty API endpoints
        Route::get('api/teachers', [\App\Http\Controllers\AdminController::class, 'getTeachers']);
        Route::get('api/teachers/{id}/assigned-subjects', [\App\Http\Controllers\AdminController::class, 'getTeacherAssignedSubjects']);
        Route::post('api/teachers', [\App\Http\Controllers\AdminController::class, 'addTeacher']);
        Route::put('api/teachers/{id}', [\App\Http\Controllers\AdminController::class, 'updateTeacher']);
        Route::delete('api/teachers/{id}', [\App\Http\Controllers\AdminController::class, 'deleteTeacher']);
        Route::patch('api/teachers/{id}/toggle-active', [\App\Http\Controllers\AdminController::class, 'toggleTeacherActive']);
    });
    
    // Shared endpoints (both teacher and admin)
    Route::get('api/schedules/approved', [ScheduleController::class, 'getApprovedSchedules'])->name('schedules.approved');
    
    // Fallback dashboard — redirects each role to their proper portal
    Route::get('dashboard', function () {
        return redirect()->route(\App\Support\AuthRedirectSupport::homeRouteName());
    })->name('dashboard');
    
    // POST-only logout — GET logout removed to prevent CSRF-based forced sign-out
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // ── Shared Teacher Portal ─────────────────────────────────────────────
    Route::middleware('shared.teacher')->prefix('shared-teacher')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\SharedTeacherPortalController::class, 'dashboard'])
            ->name('shared-teacher.dashboard');
        Route::get('requests', [\App\Http\Controllers\SharedTeacherPortalController::class, 'requests'])
            ->name('shared-teacher.requests');
        Route::get('requests/schedules', [\App\Http\Controllers\SharedTeacherPortalController::class, 'requestSchedules'])
            ->name('shared-teacher.requests.schedules');
        Route::post('requests', [\App\Http\Controllers\SharedTeacherPortalController::class, 'storeRequest'])
            ->name('shared-teacher.requests.store');
        Route::post('requests/leave', [\App\Http\Controllers\SharedTeacherPortalController::class, 'storeLeaveRequest'])
            ->name('shared-teacher.requests.leave.store');
        Route::get('settings', function () {
            return view('shared-teacher.settings');
        })->name('shared-teacher.settings');
        Route::post('profile/photo', function (\Illuminate\Http\Request $request) {
            $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = $request->file('photo')->store('profile-photos', 'public');
            $user->save();
            return redirect()->route('shared-teacher.settings')->with('success', 'Profile photo updated.');
        })->name('shared-teacher.profile.photo');
        Route::put('profile', function (\Illuminate\Http\Request $request) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $data = $request->validate([
                'first_name'       => 'required|string|max:100',
                'last_name'        => 'required|string|max:100',
                'email'            => 'required|email|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password|string',
                'password'         => \App\Support\SecurePassword::optionalRules(),
            ]);
            $user->first_name = $data['first_name'];
            $user->last_name  = $data['last_name'];
            $user->email      = $data['email'];
            if (! empty($data['password'])) {
                if (! \Illuminate\Support\Facades\Hash::check($data['current_password'] ?? '', $user->password)) {
                    return back()->withErrors(['current_password' => 'Current password is incorrect.']);
                }
                $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
            }
            $user->save();
            \App\Support\UserProfileSupport::syncTeacherNameReferences($user->fresh() ?? $user);
            return redirect()->route('shared-teacher.settings')->with('success', 'Profile updated successfully.');
        })->name('shared-teacher.profile.update');
        Route::get('api/shared-teacher/notifications', [\App\Http\Controllers\TeacherNotificationController::class, 'index']);
        Route::post('api/shared-teacher/notifications/read', [\App\Http\Controllers\TeacherNotificationController::class, 'markRead']);
    });
});

// =============================================================================
// GRADE SCHOOL ADMIN ROUTES - School Level Segregation
// =============================================================================

// GS admin — uses loading_scheduling_gs
Route::middleware(['auth', \App\Http\Middleware\IsGradeSchoolAdmin::class, 'school.db:mysql_gs'])->group(function () {
    // Grade School Admin Dashboard
    Route::get('grade-school-admin/dashboard', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'dashboard'
    ])->name('grade-school-admin.dashboard');

    // Grade School Admin Classes & Schedules
    Route::get('grade-school-admin/class-schedule', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'classSchedule'
    ])->name('grade-school-admin.class-schedule');

    // Grade School Admin Faculty Loading
    Route::get('grade-school-admin/faculty-loading', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'facultyLoading'
    ])->name('grade-school-admin.faculty-loading');

    // Master Weekly Schedule — GS Admin
    Route::get('grade-school-admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'manageGS'])->name('grade-school-admin.master-schedule.manage');
    Route::get('grade-school-admin/master-schedule/{teacherId}/card', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'cardViewGS'])->name('grade-school-admin.master-schedule.card');
    Route::get('grade-school-admin/master-schedule/{teacherId}/download', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'downloadGS'])->name('grade-school-admin.master-schedule.download');
    Route::post('grade-school-admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'save'])->name('grade-school-admin.master-schedule.save');
    Route::delete('grade-school-admin/master-schedule/{teacherId}', [\App\Http\Controllers\MasterWeeklyScheduleController::class, 'clear'])->name('grade-school-admin.master-schedule.clear');

    // Grade School Admin Rooms Management (full CRUD)
    Route::resource('grade-school-admin/rooms', \App\Http\Controllers\GradeSchoolRoomController::class, ['names' => [
        'index'  => 'grade-school-admin.rooms.index',
        'create' => 'grade-school-admin.rooms.create',
        'store'  => 'grade-school-admin.rooms.store',
        'show'   => 'grade-school-admin.rooms.show',
        'edit'   => 'grade-school-admin.rooms.edit',
        'update' => 'grade-school-admin.rooms.update',
        'destroy'=> 'grade-school-admin.rooms.destroy',
    ]]);

    // Grade School Admin Users Management
    Route::get('grade-school-admin/users', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'users'
    ])->name('grade-school-admin.users.index');
    Route::get('grade-school-admin/users/create', function () {
        return redirect()->route('grade-school-admin.users.index');
    })->name('grade-school-admin.users.create');
    Route::post('grade-school-admin/users', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'storeUser'
    ])->name('grade-school-admin.users.store');
    Route::get('grade-school-admin/users/{user}/edit', function () {
        return view('grade-school-admin.users.edit');
    })->name('grade-school-admin.users.edit');

    // Grade School Admin Schedule Approval
    Route::get('grade-school-admin/schedule-approval', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'scheduleApproval'
    ])->name('grade-school-admin.schedule-approval.index');

    Route::redirect('grade-school-admin/reports', '/grade-school-admin/print-export', 301)->name('grade-school-admin.reports.index');
    Route::redirect('grade-school-admin/reports/history', '/grade-school-admin/print-export', 301)->name('grade-school-admin.reports.history');
    Route::redirect('grade-school-admin/reports/generate/{type}', '/grade-school-admin/print-export', 301)->name('grade-school-admin.reports.generate');

    // Grade School Admin Rooms & Sections
    Route::get('grade-school-admin/rooms-sections', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'roomsSections'
    ])->name('grade-school-admin.rooms-sections.index');

    // Grade School Admin Generate Schedule
    Route::get('grade-school-admin/schedule/create', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'scheduleCreate'
    ])->name('grade-school-admin.schedule.create');

    Route::get('grade-school-admin/kinder-schedule', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'kinderScheduleForm'
    ])->name('grade-school-admin.kinder-schedule');
    Route::post('grade-school-admin/kinder-schedule', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'storeKinderSchedule'
    ])->name('grade-school-admin.kinder-schedule.store');

    // ── Shared Teachers Management (GS Admin) ────────────────────────────
    Route::get('grade-school-admin/shared-teachers', function () {
        $sharedTeachers = (new \App\Models\SharedTeacher)->setConnection('mysql_gs')->newQuery()
            ->orderBy('teacher_name')->get();
        $gsTeachers = \App\Models\User::where('school_level', 'grade_school')
            ->whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
            ->orderBy('first_name')->get();
        return view('grade-school-admin.shared-teachers', compact('sharedTeachers', 'gsTeachers'));
    })->name('grade-school-admin.shared-teachers.index');

    Route::post('grade-school-admin/shared-teachers', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'faculty_id'   => 'nullable|integer',
            'teacher_name' => 'required|string|max:150',
            'email'        => 'nullable|email|max:150',
            'department'   => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);
        $data['school_level'] = 'grade_school';
        $data['is_active']    = true;
        (new \App\Models\SharedTeacher)->setConnection('mysql_gs')->newQuery()->create($data);
        return redirect()->route('grade-school-admin.shared-teachers.index')
            ->with('success', 'Shared teacher added successfully.');
    })->name('grade-school-admin.shared-teachers.store');

    Route::patch('grade-school-admin/shared-teachers/{id}/toggle', function ($id) {
        $st = (new \App\Models\SharedTeacher)->setConnection('mysql_gs')->newQuery()->findOrFail($id);
        $st->update(['is_active' => !$st->is_active]);
        return redirect()->route('grade-school-admin.shared-teachers.index')
            ->with('success', $st->teacher_name . ' marked ' . ($st->is_active ? 'active' : 'inactive') . '.');
    })->name('grade-school-admin.shared-teachers.toggle');

    Route::delete('grade-school-admin/shared-teachers/{id}', function ($id) {
        (new \App\Models\SharedTeacher)->setConnection('mysql_gs')->newQuery()->findOrFail($id)->delete();
        return redirect()->route('grade-school-admin.shared-teachers.index')
            ->with('success', 'Shared teacher removed.');
    })->name('grade-school-admin.shared-teachers.destroy');

    // Shared Teacher schedule-request review (GS admin)
    Route::get('grade-school-admin/shared-teacher-requests', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsRequests'])
        ->name('grade-school-admin.shared-teacher-requests');
    Route::patch('grade-school-admin/shared-teacher-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsApprove'])
        ->name('grade-school-admin.shared-teacher-requests.approve');
    Route::patch('grade-school-admin/shared-teacher-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsReject'])
        ->name('grade-school-admin.shared-teacher-requests.reject');
    Route::patch('grade-school-admin/teacher-schedule-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsApproveScheduleRequest'])
        ->name('grade-school-admin.teacher-schedule-requests.approve');
    Route::patch('grade-school-admin/teacher-schedule-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsRejectScheduleRequest'])
        ->name('grade-school-admin.teacher-schedule-requests.reject');
    Route::patch('grade-school-admin/teacher-leave-requests/{id}/approve', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsApproveLeaveRequest'])
        ->name('grade-school-admin.teacher-leave-requests.approve');
    Route::patch('grade-school-admin/teacher-leave-requests/{id}/reject', [\App\Http\Controllers\SharedTeacherPortalController::class, 'adminGsRejectLeaveRequest'])
        ->name('grade-school-admin.teacher-leave-requests.reject');


    Route::post('grade-school-admin/schedule/store', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'storeSchedule'
    ])->name('grade-school-admin.schedule.store');
    Route::post('grade-school-admin/schedule/check-grid', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'checkScheduleGrid'
    ])->name('grade-school-admin.schedule.check-grid');

    // Auto Schedule Generator (GS admin)
    Route::get('grade-school-admin/schedule/generate',          [\App\Http\Controllers\ScheduleGeneratorController::class, 'show'])->name('grade-school-admin.schedule.generate');
    Route::post('grade-school-admin/schedule/generate/preview', [\App\Http\Controllers\ScheduleGeneratorController::class, 'preview'])->name('grade-school-admin.schedule.generate.preview');
    Route::post('grade-school-admin/schedule/generate/confirm', [\App\Http\Controllers\ScheduleGeneratorController::class, 'confirm'])->name('grade-school-admin.schedule.generate.confirm');

    // Grade School Admin Print / Export
    Route::get('grade-school-admin/print-export', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'printExport'
    ])->name('grade-school-admin.print-export');
    Route::get('grade-school-admin/export/csv',   [\App\Http\Controllers\GradeSchoolAdminController::class, 'exportCsv'])->name('grade-school-admin.export.csv');
    Route::get('grade-school-admin/export/excel', [\App\Http\Controllers\GradeSchoolAdminController::class, 'exportExcel'])->name('grade-school-admin.export.excel');

    // Grade School Admin System Logs
    Route::get('grade-school-admin/system-logs', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'systemLogs'
    ])->name('grade-school-admin.system-logs');

    // Grade School Admin Settings
    Route::get('grade-school-admin/settings', [
        \App\Http\Controllers\GradeSchoolAdminController::class, 'settings'
    ])->name('grade-school-admin.settings');
    Route::get('grade-school-admin/backup/download', [\App\Http\Controllers\GradeSchoolAdminController::class, 'backupDownload'])->name('grade-school-admin.backup.download');
    Route::post('grade-school-admin/backup/restore',  [\App\Http\Controllers\GradeSchoolAdminController::class, 'backupRestore'])->name('grade-school-admin.backup.restore');

    Route::put('grade-school-admin/profile', function (\Illuminate\Http\Request $request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password'         => \App\Support\SecurePassword::optionalRules(),
        ]);
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'];
        $user->email      = $data['email'];
        if (!empty($data['password'])) {
            if (!\Illuminate\Support\Facades\Hash::check($data['current_password'] ?? '', $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        $user->save();
        \App\Support\UserProfileSupport::syncTeacherNameReferences($user->fresh() ?? $user);
        return redirect()->route('grade-school-admin.settings')->with('success', 'Profile updated successfully.');
    })->name('grade-school-admin.profile.update');

    Route::post('grade-school-admin/profile/photo', function (\Illuminate\Http\Request $request) {
        $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
        }
        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();
        return redirect()->route('grade-school-admin.settings')->with('success', 'Profile photo updated successfully.');
    })->name('grade-school-admin.profile.photo');

    // Grade School Admin API Routes
    Route::prefix('api/grade-school-admin')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\AdminNotificationController::class, 'index']);
        Route::post('/notifications/read', [\App\Http\Controllers\AdminNotificationController::class, 'markRead']);

        // Schedules
        Route::get('/schedules', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getSchedules'
        ]);
        Route::get('/combined-schedules', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getCombinedSchedules'
        ]);
        Route::get('/schedules/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getSchedule'
        ]);
        Route::get('/schedules/{id}/history', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getScheduleHistory'
        ]);
        Route::post('/schedules/{id}/approve', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'approveSchedule'
        ]);
        Route::post('/schedules/{id}/reject', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'rejectSchedule'
        ]);
        Route::put('/schedules/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'updateSchedule'
        ]);
        Route::delete('/schedules/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'deleteSchedule'
        ]);

        // Teachers
        Route::post('/teachers', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'addTeacher'
        ]);
        Route::get('/teachers', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getTeachers'
        ]);
        Route::get('/teachers/{id}/assigned-subjects', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getTeacherAssignedSubjects'
        ]);
        Route::put('/teachers/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'updateTeacher'
        ]);
        Route::delete('/teachers/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'deleteTeacher'
        ]);
        Route::patch('/teachers/{id}/toggle-active', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'toggleTeacherActive'
        ]);

        // Rooms
        Route::post('/rooms', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'addRoom'
        ]);
        Route::get('/rooms', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getRooms'
        ]);
        Route::put('/rooms/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'updateRoom'
        ]);
        Route::delete('/rooms/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'deleteRoom'
        ]);

        // Available rooms for a time slot (automated room assignment)
        Route::get('/available-rooms', [ScheduleController::class, 'getAvailableRooms'])->name('grade-school-admin.available-rooms');
        Route::get('/room-for-section', [ScheduleController::class, 'getRoomForSection']);

        // Faculty Loads
        Route::get('/faculty-loads', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getFacultyLoads'
        ]);
        Route::post('/faculty-loads', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'addFacultyLoad'
        ]);
        Route::put('/faculty-loads/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'updateFacultyLoad'
        ]);
        Route::delete('/faculty-loads/{id}', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'deleteFacultyLoad'
        ]);

        // Cross-DB: GS admin reading teacher data from mysql_gs_teacher
        Route::get('/teacher/adjustment-requests', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getTeacherAdjustmentRequests'
        ]);
        Route::post('/teacher/adjustment-requests/{id}/approve', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'approveTeacherAdjustmentRequest'
        ]);
        Route::post('/teacher/adjustment-requests/{id}/reject', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'rejectTeacherAdjustmentRequest'
        ]);
        Route::get('/teacher/leave-requests', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getTeacherLeaveRequests'
        ]);
        Route::post('/teacher/leave-requests/{id}/approve', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'approveTeacherLeaveRequest'
        ]);
        Route::post('/teacher/leave-requests/{id}/reject', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'rejectTeacherLeaveRequest'
        ]);
        Route::get('/teacher/subject-assignments', [
            \App\Http\Controllers\GradeSchoolAdminController::class, 'getTeacherSubjectAssignments'
        ]);

        // Scheduling conflicts and detection
        Route::get('/schedules/conflicts/summary', [ScheduleController::class, 'getConflictsSummary'])->name('grade-school-admin.schedules.conflicts');
        Route::post('/schedules/check-duplicate', [ScheduleController::class, 'checkDuplicate'])->name('grade-school-admin.schedules.check-duplicate');

        // Teacher filtering by grade and subject
        Route::get('/teachers/by-grade-subject', [ScheduleController::class, 'getTeachersByGradeAndSubject'])->name('grade-school-admin.teachers.by-grade-subject');

        // Faculty load availability status
        Route::get('/faculty-load-status', [ScheduleController::class, 'getFacultyLoadStatus'])->name('grade-school-admin.faculty-load-status');

        Route::post('/dss/assess-slot', [\App\Http\Controllers\ScheduleDssController::class, 'assessSlot'])->name('grade-school-admin.dss.assess-slot');
        Route::post('/dss/assess-faculty-load', [\App\Http\Controllers\ScheduleDssController::class, 'assessFacultyLoad'])->name('grade-school-admin.dss.assess-faculty-load');

    });
});

// =============================================================================
// PRINCIPAL ROUTES — Principal / Secretary
// Full control over both school levels; handles admin permission requests
// =============================================================================

Route::middleware(['auth', 'principal.admin'])->prefix('principal')->name('principal.')->group(function () {
    Route::get('/dashboard',           [\App\Http\Controllers\PrincipalController::class, 'dashboard'])->name('dashboard');

    Route::get('/users',               [\App\Http\Controllers\PrincipalController::class, 'users'])->name('users');
    Route::post('/users',              [\App\Http\Controllers\PrincipalController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}',      [\App\Http\Controllers\PrincipalController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/toggle',[\App\Http\Controllers\PrincipalController::class, 'toggleUserActive'])->name('users.toggle');
    Route::delete('/users/{user}',      [\App\Http\Controllers\PrincipalController::class, 'deleteUser'])->name('users.destroy');

    Route::get('/permission-requests', [\App\Http\Controllers\PrincipalController::class, 'permissionRequests'])->name('permission-requests');
    Route::patch('/permission-requests/{permissionRequest}/approve', [\App\Http\Controllers\PrincipalController::class, 'approveRequest'])->name('requests.approve');
    Route::patch('/permission-requests/{permissionRequest}/reject',  [\App\Http\Controllers\PrincipalController::class, 'rejectRequest'])->name('requests.reject');

    Route::get('/system-logs',         [\App\Http\Controllers\PrincipalController::class, 'systemLogs'])->name('system-logs');
    Route::get('/teacher-logs/junior-high',  [\App\Http\Controllers\PrincipalController::class, 'teacherLogsJH'])->name('teacher-logs.jh');
    Route::get('/teacher-logs/grade-school', [\App\Http\Controllers\PrincipalController::class, 'teacherLogsGS'])->name('teacher-logs.gs');

    Route::get('/api/stats',           [\App\Http\Controllers\PrincipalController::class, 'apiStats'])->name('api.stats');

    Route::get('/schedule-approvals',  [\App\Http\Controllers\PrincipalController::class, 'schedulePendingApprovals'])->name('schedule-approvals');
    Route::post('/schedule-approvals/{school}/{id}/approve', [\App\Http\Controllers\PrincipalController::class, 'approveSchedule'])->name('schedule-approvals.approve');
    Route::post('/schedule-approvals/{school}/{id}/reject',  [\App\Http\Controllers\PrincipalController::class, 'rejectSchedule'])->name('schedule-approvals.reject');

    Route::get('/database',            [\App\Http\Controllers\PrincipalController::class, 'database'])->name('database');
    Route::patch('/settings/{key}',    [\App\Http\Controllers\PrincipalController::class, 'updateSetting'])->name('settings.update');
});

// Legacy URLs → principal portal
Route::redirect('/super-admin', '/principal/dashboard', 301);
Route::get('/super-admin/{path?}', function (?string $path = null) {
    $path = trim((string) $path, '/');

    return redirect($path === '' ? '/principal/dashboard' : '/principal/' . $path, 301);
})->where('path', '.*');

// ── Shared Teachers Panel API (accessible to any authenticated admin) ─────────
Route::middleware(['auth'])->get('/api/shared-teachers-panel', function () {
    // Primary source: users with the shared_teacher role
    $roleSharedIds = \Illuminate\Support\Facades\DB::table('users')
        ->whereIn('role_id', \Illuminate\Support\Facades\DB::table('roles')->where('name', 'shared_teacher')->pluck('id'))
        ->pluck('id')->toArray();

    // Also include teachers explicitly added to shared_teachers table in JH DB
    $jhSharedTableIds = \App\Support\SharedTeacherSupport::activeFacultyIds('mysql_jh');

    // Only role-based OR explicitly added to shared_teachers table
    $sharedIds = array_values(array_unique(array_merge($roleSharedIds, $jhSharedTableIds)));

    if (empty($sharedIds)) {
        return response()->json(['success' => true, 'data' => [], 'conflict_count' => 0]);
    }

    $users = \Illuminate\Support\Facades\DB::table('users')
        ->whereIn('id', $sharedIds)->select('id', 'first_name', 'last_name')->get()->keyBy('id');

    // faculty_designations lives in the school DBs (mysql_jh / mysql_gs), not in the default DB
    $desigJH = \Illuminate\Support\Facades\DB::connection('mysql_jh')->table('faculty_designations')
        ->whereIn('faculty_id', $sharedIds)->select('faculty_id', 'designation_type', 'max_classes', 'max_load_hours')
        ->get()->keyBy('faculty_id');
    $desigGS = \Illuminate\Support\Facades\DB::connection('mysql_gs')->table('faculty_designations')
        ->whereIn('faculty_id', $sharedIds)->select('faculty_id', 'designation_type', 'max_classes', 'max_load_hours')
        ->get()->keyBy('faculty_id');
    // Merge: GS first, then JH overrides (JH designation takes precedence for shared teachers)
    $designations = $desigGS->merge($desigJH)->keyBy('faculty_id');

    $defaultMaxClasses   = ['regular' => 6, 'coordinator' => 3, 'dept_head' => 4, 'shared' => 4, 'part_time' => 3];
    $defaultMaxLoadHours = ['regular' => 24, 'coordinator' => 12, 'dept_head' => 16, 'shared' => 16, 'part_time' => 12];

    $jhLoads = \Illuminate\Support\Facades\DB::connection('mysql_jh')->table('faculty_loads')
        ->whereIn('faculty_id', $sharedIds)
        ->selectRaw('faculty_id, SUM(classes_assigned) as classes, SUM(load_hours) as hours')
        ->groupBy('faculty_id')->get()->keyBy('faculty_id');

    $gsLoads = \Illuminate\Support\Facades\DB::connection('mysql_gs')->table('faculty_loads')
        ->whereIn('faculty_id', $sharedIds)
        ->selectRaw('faculty_id, SUM(classes_assigned) as classes, SUM(load_hours) as hours')
        ->groupBy('faculty_id')->get()->keyBy('faculty_id');

    // ── Subjects per teacher from faculty_loads ───────────────────────────
    $jhSubjectsRaw = \Illuminate\Support\Facades\DB::connection('mysql_jh')->table('faculty_loads')
        ->whereIn('faculty_id', $sharedIds)->whereNotNull('subject')
        ->select('faculty_id', 'subject')->get()->groupBy('faculty_id');

    $gsSubjectsRaw = \Illuminate\Support\Facades\DB::connection('mysql_gs')->table('faculty_loads')
        ->whereIn('faculty_id', $sharedIds)->whereNotNull('subject')
        ->select('faculty_id', 'subject')->get()->groupBy('faculty_id');

    $jhScheds = \Illuminate\Support\Facades\DB::connection('mysql_jh')->table('class_schedules')
        ->whereIn('faculty_id', $sharedIds)->whereIn('status', ['active', 'approved', 'pending'])
        ->select('faculty_id', 'subject', 'day_of_week', 'start_time', 'end_time', 'section_name')
        ->get()->groupBy('faculty_id');

    $gsScheds = \Illuminate\Support\Facades\DB::connection('mysql_gs')->table('class_schedules')
        ->whereIn('faculty_id', $sharedIds)->whereIn('status', ['active', 'approved', 'pending'])
        ->select('faculty_id', 'subject', 'day_of_week', 'start_time', 'end_time', 'section_name')
        ->get()->groupBy('faculty_id');

    $result = [];
    $conflictCount = 0;

    foreach ($sharedIds as $id) {
        $user = $users[$id] ?? null;
        $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : "User #$id";

        $jhClasses = (int) ($jhLoads[$id]->classes ?? 0);
        $jhHours   = (float) ($jhLoads[$id]->hours  ?? 0);
        $gsClasses = (int) ($gsLoads[$id]->classes ?? 0);
        $gsHours   = (float) ($gsLoads[$id]->hours  ?? 0);

        $desig = $designations[$id] ?? null;
        $desigType    = $desig->designation_type ?? 'regular';
        $maxClasses   = (int)   ($desig->max_classes    ?? ($defaultMaxClasses[$desigType]   ?? 6));
        $maxLoadHours = (float) ($desig->max_load_hours ?? ($defaultMaxLoadHours[$desigType] ?? 24));

        $myJH = collect($jhScheds[$id] ?? []);
        $myGS = collect($gsScheds[$id] ?? []);
        $conflicts = [];

        foreach ($myJH as $jhs) {
            foreach ($myGS as $gss) {
                if (strtolower($jhs->day_of_week ?? '') !== strtolower($gss->day_of_week ?? '')) continue;
                $jStart = strtotime($jhs->start_time ?? '00:00');
                $jEnd   = strtotime($jhs->end_time   ?? '00:00');
                $gStart = strtotime($gss->start_time ?? '00:00');
                $gEnd   = strtotime($gss->end_time   ?? '00:00');
                if ($jStart < $gEnd && $jEnd > $gStart) {
                    $conflicts[] = [
                        'day'        => $jhs->day_of_week,
                        'jh_subject' => $jhs->subject,
                        'jh_time'    => ($jhs->start_time ?? '') . '–' . ($jhs->end_time ?? ''),
                        'jh_section' => $jhs->section_name,
                        'gs_subject' => $gss->subject,
                        'gs_time'    => ($gss->start_time ?? '') . '–' . ($gss->end_time ?? ''),
                        'gs_section' => $gss->section_name,
                    ];
                }
            }
        }

        if (count($conflicts) > 0) $conflictCount++;

        $total  = $jhHours + $gsHours;
        $status = $total > $maxLoadHours ? 'overloaded'
                : ($total > $maxLoadHours * 0.8 ? 'near_limit' : 'ok');

        $jhSubjectStr = collect($jhSubjectsRaw[$id] ?? [])->pluck('subject')->filter()->unique()->implode(', ');
        $gsSubjectStr = collect($gsSubjectsRaw[$id] ?? [])->pluck('subject')->filter()->unique()->implode(', ');

        $result[] = [
            'id'          => $id,
            'name'        => $name ?: "User #$id",
            'jh_subjects' => $jhSubjectStr ?: '—',
            'gs_subjects' => $gsSubjectStr ?: '—',
            'jh_classes'     => $jhClasses,
            'jh_hours'       => $jhHours,
            'gs_classes'     => $gsClasses,
            'gs_hours'       => $gsHours,
            'max_classes'    => $maxClasses,
            'max_hours'      => $maxLoadHours,
            'total_hours'    => $total,
            'status'         => $status,
            'conflicts'      => $conflicts,
            'jh_schedules'   => $myJH->map(fn($s) => ['subject' => $s->subject, 'day' => $s->day_of_week, 'start' => $s->start_time, 'end' => $s->end_time, 'section' => $s->section_name])->values(),
            'gs_schedules'   => $myGS->map(fn($s) => ['subject' => $s->subject, 'day' => $s->day_of_week, 'start' => $s->start_time, 'end' => $s->end_time, 'section' => $s->section_name])->values(),
        ];
    }

    // Deduplicate by name — keep the entry with the most data (classes + schedules)
    $best = [];
    foreach ($result as $entry) {
        $key = strtolower(trim($entry['name']));
        if (!isset($best[$key])) {
            $best[$key] = $entry;
        } else {
            $existingScore = $best[$key]['jh_classes'] + $best[$key]['gs_classes']
                           + count($best[$key]['jh_schedules']) + count($best[$key]['gs_schedules']);
            $newScore = $entry['jh_classes'] + $entry['gs_classes']
                      + count($entry['jh_schedules']) + count($entry['gs_schedules']);
            if ($newScore > $existingScore) $best[$key] = $entry;
        }
    }
    $result = array_values($best);
    $conflictCount = count(array_filter($result, fn($e) => count($e['conflicts']) > 0));

    usort($result, fn($a, $b) =>
        (count($b['conflicts']) <=> count($a['conflicts'])) ?:
        (($b['status'] === 'overloaded' ? 1 : 0) <=> ($a['status'] === 'overloaded' ? 1 : 0))
    );

    return response()->json(['success' => true, 'data' => $result, 'conflict_count' => $conflictCount]);
})->name('api.shared-teachers-panel');

// Admin → Principal permission requests (JH and GS admins share the same controller)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/permission-requests',                         [\App\Http\Controllers\PermissionRequestController::class, 'index'])->name('admin.permission-requests');
    Route::post('/admin/permission-requests',                        [\App\Http\Controllers\PermissionRequestController::class, 'store'])->name('admin.permission-requests.store');
    Route::patch('/admin/permission-requests/{permissionRequest}/cancel', [\App\Http\Controllers\PermissionRequestController::class, 'cancel'])->name('admin.permission-requests.cancel');
});

Route::middleware(['auth', \App\Http\Middleware\IsGradeSchoolAdmin::class])->group(function () {
    Route::get('/grade-school-admin/permission-requests',                         [\App\Http\Controllers\PermissionRequestController::class, 'index'])->name('grade-school-admin.permission-requests');
    Route::post('/grade-school-admin/permission-requests',                        [\App\Http\Controllers\PermissionRequestController::class, 'store'])->name('grade-school-admin.permission-requests.store');
    Route::patch('/grade-school-admin/permission-requests/{permissionRequest}/cancel', [\App\Http\Controllers\PermissionRequestController::class, 'cancel'])->name('grade-school-admin.permission-requests.cancel');
});

// =============================================================================
// GRADE SCHOOL TEACHER ROUTES - School Level Segregation
// =============================================================================

Route::middleware(['auth', \App\Http\Middleware\IsGradeSchoolTeacher::class, 'school.db:mysql_gs'])->group(function () {
    // Grade School Teacher Dashboard
    Route::get('grade-school-teacher/dashboard', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'dashboard'
    ])->name('grade-school-teacher.dashboard');

    // Grade School Teacher Classes
    Route::get('grade-school-teacher/class-schedule', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'classSchedule'
    ])->name('grade-school-teacher.class-schedule');

    Route::get('grade-school-teacher/my-classes', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'myClasses'
    ])->name('grade-school-teacher.my-classes');

    Route::get('grade-school-teacher/my-students', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'myStudents'
    ])->name('grade-school-teacher.my-students');

    Route::get('grade-school-teacher/class-performance', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'classPerformance'
    ])->name('grade-school-teacher.class-performance');

    // Grade School Teacher Faculty Loading
    Route::get('grade-school-teacher/faculty-loading', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'facultyLoading'
    ])->name('grade-school-teacher.faculty-loading');

    // Grade School Teacher Export/Print
    Route::get('grade-school-teacher/print-export', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'printExport'
    ])->name('grade-school-teacher.print-export');

    Route::get('grade-school-teacher/export/schedule', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'exportSchedule'
    ])->name('grade-school-teacher.export.schedule');

    Route::get('grade-school-teacher/feedback', [\App\Http\Controllers\GradeSchoolTeacherController::class, 'showFeedback'])->name('grade-school-teacher.feedback');
    Route::post('grade-school-teacher/feedback', [\App\Http\Controllers\GradeSchoolTeacherController::class, 'submitFeedback'])->name('grade-school-teacher.feedback.submit');

    Route::get('grade-school-teacher/loading-schedule', [\App\Http\Controllers\GradeSchoolTeacherController::class, 'showLoadingSchedule'])->name('grade-school-teacher.loading-schedule');

    Route::get('grade-school-teacher/settings', function () {
        return view('grade-school-teacher.settings');
    })->name('grade-school-teacher.settings');

    Route::post('grade-school-teacher/profile/photo', function (\Illuminate\Http\Request $request) {
        $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
        }
        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();
        return redirect()->route('grade-school-teacher.settings')->with('success', 'Profile photo updated successfully.');
    })->name('grade-school-teacher.profile.photo');

    Route::put('grade-school-teacher/profile', function (\Illuminate\Http\Request $request) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password'         => \App\Support\SecurePassword::optionalRules(),
        ]);
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'];
        $user->email      = $data['email'];
        if (! empty($data['password'])) {
            if (! \Illuminate\Support\Facades\Hash::check($data['current_password'] ?? '', $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        $user->save();
        \App\Support\UserProfileSupport::syncTeacherNameReferences($user->fresh() ?? $user);
        return redirect()->route('grade-school-teacher.settings')->with('success', 'Profile updated successfully.');
    })->name('grade-school-teacher.profile.update');

    Route::get('grade-school-teacher/review-schedule', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'reviewSchedule'
    ])->name('grade-school-teacher.review-schedule');

    Route::get('grade-school-teacher/request-adjustments', [
        \App\Http\Controllers\GradeSchoolTeacherController::class, 'requestAdjustments'
    ])->name('grade-school-teacher.request-adjustments');

    // Grade School Teacher API Routes
    Route::prefix('api/grade-school-teacher')->group(function () {
        // Classes
        Route::get('/classes', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getMyClasses'
        ]);

        // Students
        Route::get('/students', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getMyStudents'
        ]);

        // Performance
        Route::get('/performance', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getClassPerformance'
        ]);

        // Faculty Load
        Route::get('/faculty-load', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getFacultyLoad'
        ]);

        // Grades
        Route::get('/grades', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getGrades'
        ]);
        Route::post('/grades/submit', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'submitGrades'
        ]);

        // Review Schedule
        Route::get('/schedules', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getSchedulesForReview'
        ]);

        Route::get('/workload-history', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getWorkloadHistory'
        ]);

        // Adjustment Requests
        Route::get('/adjustment-requests', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getAdjustmentRequests'
        ]);
        Route::get('/adjustment-schedules', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getAdjustmentScheduleOptions'
        ]);
        Route::get('/adjustment-available-teachers', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getAdjustmentAvailableTeachers'
        ]);
        Route::post('/adjustment-check-slot', [\App\Http\Controllers\ScheduleDssController::class, 'checkAdjustmentSlot']);
        Route::post('/adjustment-requests', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'storeAdjustmentRequest'
        ]);
        Route::get('/leave-requests', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'getLeaveRequests'
        ]);
        Route::post('/leave-requests', [
            \App\Http\Controllers\GradeSchoolTeacherController::class, 'storeLeaveRequest'
        ]);
        Route::get('/notifications', [\App\Http\Controllers\TeacherNotificationController::class, 'index']);
        Route::post('/notifications/read', [\App\Http\Controllers\TeacherNotificationController::class, 'markRead']);

    });
});

// =============================================================================
// SHARED CROSS-DIVISION TEACHER LOAD API (accessible to any authenticated user)
// Used by faculty-loading modals in both JH and GS admin to show designation
// limits and cross-division load warnings without manual checking.
// =============================================================================
Route::middleware(['auth'])->get('/api/teacher-cross-load/{userId}', function ($userId) {
    $defaultMaxClasses   = ['regular' => 6, 'coordinator' => 3, 'dept_head' => 4, 'shared' => 4, 'part_time' => 3];
    $defaultMaxLoadHours = ['regular' => 24, 'coordinator' => 12, 'dept_head' => 16, 'shared' => 16, 'part_time' => 12];

    // Designation info (from main DB)
    $desig = \Illuminate\Support\Facades\DB::table('faculty_designations')
        ->where('user_id', $userId)
        ->select('designation_type', 'max_classes', 'max_load_hours')
        ->first();

    $desigType    = $desig->designation_type ?? 'regular';
    $maxClasses   = (int) ($desig->max_classes    ?? ($defaultMaxClasses[$desigType]   ?? 6));
    $maxLoadHours = (float) ($desig->max_load_hours ?? ($defaultMaxLoadHours[$desigType] ?? 24));

    // JH load
    $jhLoad = \Illuminate\Support\Facades\DB::connection('mysql_jh')
        ->table('faculty_loads')
        ->where('faculty_id', $userId)
        ->selectRaw('SUM(classes_assigned) as classes, SUM(load_hours) as hours')
        ->first();

    // GS load
    $gsLoad = \Illuminate\Support\Facades\DB::connection('mysql_gs')
        ->table('faculty_loads')
        ->where('faculty_id', $userId)
        ->selectRaw('SUM(classes_assigned) as classes, SUM(load_hours) as hours')
        ->first();

    return response()->json([
        'success'       => true,
        'designation'   => $desigType,
        'max_classes'   => $maxClasses,
        'max_load_hours'=> $maxLoadHours,
        'jh_classes'    => (int) ($jhLoad->classes ?? 0),
        'jh_hours'      => (float) ($jhLoad->hours ?? 0),
        'gs_classes'    => (int) ($gsLoad->classes ?? 0),
        'gs_hours'      => (float) ($gsLoad->hours ?? 0),
        'total_hours'   => (float) ($jhLoad->hours ?? 0) + (float) ($gsLoad->hours ?? 0),
    ]);
})->name('api.teacher-cross-load');

// =============================================================================
// Subjects API — provides list of available subjects for form dropdowns
// =============================================================================
Route::middleware(['auth'])->get('/api/subjects', function (\Illuminate\Http\Request $request) {
    $portal = (string) $request->query('portal', 'all');
    $subjects = \App\Support\SchoolSubjectsCatalog::subjectsForPortal($portal);

    return response()->json([
        'success'  => true,
        'portal'   => \App\Support\SchoolSubjectsCatalog::normalizePortal($portal),
        'subjects' => $subjects,
    ]);
})->name('api.subjects');

