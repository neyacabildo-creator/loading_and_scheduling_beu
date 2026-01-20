<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FacultyLoadController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;

Route::prefix('api/admin')->group(function () {
    Route::get('/schedules', [AdminController::class, 'getSchedules']);
    Route::post('/schedules/{id}/approve', [AdminController::class, 'approveSchedule']);
    Route::post('/schedules/{id}/reject', [AdminController::class, 'rejectSchedule']);
    Route::put('/schedules/{id}', [AdminController::class, 'updateSchedule']);
    Route::delete('/schedules/{id}', [AdminController::class, 'deleteSchedule']);
    Route::post('/teachers', [AdminController::class, 'addTeacher']);
    Route::post('/rooms', [AdminController::class, 'addRoom']);
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    // Teacher-only dashboard
    Route::middleware('teacher')->group(function () {
        Route::get('teacher/dashboard', function () {
            $mySchedules = \App\Models\ClassSchedule::where('faculty_id', Auth::id())
                ->where('admin_approved', true)
                ->where('status', 'active')
                ->with(['faculty', 'room'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            return view('teacher.dashboard', ['mySchedules' => $mySchedules]);
        })->name('teacher.dashboard');
        
        Route::get('teacher/class-schedule', function () {
            return view('teacher.class-schedule');
        })->name('teacher.class-schedule');
        
        Route::get('teacher/my-classes', function () {
            return view('teacher.my-classes');
        })->name('teacher.my-classes');
        
        Route::get('teacher/my-students', function () {
            return view('teacher.my-students');
        })->name('teacher.my-students');
        
        Route::get('teacher/class-performance', function () {
            return view('teacher.class-performance');
        })->name('teacher.class-performance');
        
        Route::get('teacher/faculty-loading', function () {
            return view('teacher.faculty-loading');
        })->name('teacher.faculty-loading');
        
        Route::get('teacher/grade-submission', function () {
            return view('teacher.grade-submission');
        })->name('teacher.grade-submission');
        
        Route::get('teacher/print-export', function () {
            return view('teacher.print-export');
        })->name('teacher.print-export');
        
        // Teacher API endpoints
        Route::get('api/teacher/classes', [\App\Http\Controllers\TeacherController::class, 'getMyClasses']);
        Route::get('api/teacher/students', [\App\Http\Controllers\TeacherController::class, 'getMyStudents']);
        Route::get('api/teacher/performance', [\App\Http\Controllers\TeacherController::class, 'getClassPerformance']);
        Route::get('api/teacher/faculty-load', [\App\Http\Controllers\TeacherController::class, 'getFacultyLoad']);
        Route::get('api/teacher/grades', [\App\Http\Controllers\TeacherController::class, 'getGrades']);
        Route::post('api/teacher/grades/submit', [\App\Http\Controllers\TeacherController::class, 'submitGrades']);
        Route::get('api/teacher/schedules', [ScheduleController::class, 'getTeacherSchedules'])->name('teacher.schedules');
    });
    
    // Admin-only dashboard
    Route::middleware('admin')->group(function () {
        Route::get('admin/dashboard', function () {
            $schedules = \App\Models\ClassSchedule::with(['faculty', 'room', 'approver'])->get();
            $totalUsers = \App\Models\User::count();
            $totalSchedules = $schedules->count();
            $pendingApprovals = $schedules->where('admin_approved', false)->where('status', 'pending')->count();
            
            return view('admin.dashboard', [
                'schedules' => $schedules,
                'totalUsers' => $totalUsers,
                'totalSchedules' => $totalSchedules,
                'pendingApprovals' => $pendingApprovals,
            ]);
        })->name('admin.dashboard');
        
        // Management features
        Route::get('admin/class-schedule', function () {
            return view('admin.class-schedule');
        })->name('admin.class-schedule');
        
        Route::get('admin/faculty-loading', function () {
            return view('admin.faculty-loading');
        })->name('admin.faculty-loading');
        
        Route::get('admin/dss-recommendations', function () {
            return view('admin.dss-recommendations');
        })->name('admin.dss-recommendations');
        
        Route::get('admin/print-export', function () {
            return view('admin.print-export');
        })->name('admin.print-export');

        // Rooms management
        Route::resource('admin/rooms', RoomController::class, ['names' => [
            'index' => 'admin.rooms.index',
            'create' => 'admin.rooms.create',
            'store' => 'admin.rooms.store',
            'edit' => 'admin.rooms.edit',
            'update' => 'admin.rooms.update',
            'destroy' => 'admin.rooms.destroy',
        ]]);

        // Faculty Loads management
        Route::resource('admin/faculty-loads', FacultyLoadController::class, ['names' => [
            'index' => 'admin.faculty-loads.index',
            'create' => 'admin.faculty-loads.create',
            'store' => 'admin.faculty-loads.store',
            'edit' => 'admin.faculty-loads.edit',
            'update' => 'admin.faculty-loads.update',
            'destroy' => 'admin.faculty-loads.destroy',
        ]]);

        // Schedule creation form (admin only)
        Route::get('admin/schedule/create', function () {
            $rooms = \App\Models\Room::where('status', 'available')->get();
            $teachers = \App\Models\User::whereHas('role', function($q) { $q->where('name', 'teacher'); })->get();
            return view('admin.schedule-form', ['rooms' => $rooms, 'teachers' => $teachers]);
        })->name('admin.schedule.create');
        
        // Schedule form submission (form POST)
        Route::post('admin/schedule/store', [ScheduleController::class, 'store'])->name('admin.schedule.store');
        
        // Schedule management API endpoints
        Route::get('api/admin/schedules', [ScheduleController::class, 'index'])->name('admin.schedules.index');
        Route::get('api/admin/schedules/{schedule}', [ScheduleController::class, 'show'])->name('admin.schedules.show');
        Route::get('api/admin/schedules/pending', [ScheduleController::class, 'getPendingSchedules'])->name('admin.schedules.pending');
        Route::post('api/admin/schedules', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::post('api/admin/schedules/{schedule}/approve', [ScheduleController::class, 'approve'])->name('schedule.approve');
        Route::post('api/admin/schedules/{schedule}/reject', [ScheduleController::class, 'reject'])->name('schedule.reject');
        Route::put('api/admin/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('api/admin/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
        Route::get('api/admin/schedules/{schedule}/history', [ScheduleController::class, 'getHistory'])->name('schedule.history');
        
        // Rooms API endpoints
        Route::get('api/rooms', [RoomController::class, 'index']);
        Route::post('api/rooms', [RoomController::class, 'store']);
        Route::get('api/rooms/{room}', [RoomController::class, 'show']);
        Route::put('api/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('api/rooms/{room}', [RoomController::class, 'destroy']);
        
        // Teachers/Faculty API endpoints
        Route::get('api/teachers', [AdminController::class, 'getTeachers']);
        Route::post('api/teachers', [AdminController::class, 'addTeacher']);
        Route::put('api/teachers/{id}', [AdminController::class, 'updateTeacher']);
        Route::delete('api/teachers/{id}', [AdminController::class, 'deleteTeacher']);
        
        // Faculty Loads API endpoints
        Route::get('api/faculty-loads', [FacultyLoadController::class, 'index']);
        Route::post('api/faculty-loads', [FacultyLoadController::class, 'store']);
        Route::get('api/faculty-loads/{facultyLoad}', [FacultyLoadController::class, 'show']);
        Route::put('api/faculty-loads/{facultyLoad}', [FacultyLoadController::class, 'update']);
        Route::delete('api/faculty-loads/{facultyLoad}', [FacultyLoadController::class, 'destroy']);
    });
    
    // Shared endpoints (both teacher and admin)
    Route::get('api/schedules/approved', [ScheduleController::class, 'getApprovedSchedules'])->name('schedules.approved');
    
    // Fallback dashboard - redirect to role-specific dashboard
    Route::get('dashboard', function () {
        $user = Auth::user();
        if ($user->role) {
            if (strpos($user->role->name, 'admin') !== false) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role->name === 'teacher') {
                return redirect()->route('teacher.dashboard');
            }
        }
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::get('logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout.get');
});

