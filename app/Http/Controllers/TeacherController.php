<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Support\TeacherAdjustmentRequestSupport;
use App\Support\TeacherPortalSupport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Get the authenticated teacher's school level
     */
    private function getTeacherSchoolLevel() {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return 'grade_school';
        }
        
        // Ensure role relationship is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Check role name for department
        if ($user->role && $user->role->name) {
            if (strpos($user->role->name, 'junior_high') !== false) {
                return 'junior_high';
            } elseif (strpos($user->role->name, 'grade_school') !== false) {
                return 'grade_school';
            }
        }
        
        // Fallback to school_level from database
        return $user->school_level ?? 'grade_school';
    }

    /**
     * ===============================
     * ADD TEACHER (FIXED)
     * ===============================
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|unique:users,email',
                'password'   => 'required|string|min:8',
                'status'     => 'required|in:active,inactive',
            ]);

            $schoolLevel = $this->getTeacherSchoolLevel();
            
            User::create([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'role'       => 'teacher', // 🔥 IMPORTANT
                'status'     => $validated['status'],
                'school_level' => $schoolLevel,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher added successfully'
                ]);
            }

            return redirect()
                ->route('admin.teachers.index')
                ->with('success', 'Teacher added successfully');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * ===============================
     * TEACHER DASHBOARD
     * ===============================
     * Teacher dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $metrics = TeacherPortalSupport::dashboardMetrics((int) $user->id);

        return view('junior-high-teacher.dashboard', array_merge($metrics, [
            'school_level' => 'junior_high',
        ]));
    }

    /**
     * Get all classes for the logged-in teacher (filtered by school_level)
     */
    public function getMyClasses(Request $request)
    {
        $teacherId = Auth::id();
        $schoolLevel = $this->getTeacherSchoolLevel();

        $classes = TeacherPortalSupport::approvedSchedulesForTeacher((int) $teacherId);

        $result = $classes->map(function ($s) {
            $data = $s->toArray();
            $data['grade_section'] = trim(($s->grade_level ?? '') . ($s->section_name ? ' – ' . $s->section_name : ''));
            $data['room_label'] = TeacherPortalSupport::roomLabel($s);
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $result,
            'count' => $result->count(),
        ]);
    }

    /**
     * Get all students in teacher's classes (filtered by school_level)
     */
    public function getMyStudents(Request $request)
    {
        $teacherId = Auth::id();
        $schoolLevel = $this->getTeacherSchoolLevel();

        if (! TeacherPortalSupport::hasClassSchedulesTable()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
                'total_students' => 0,
            ]);
        }

        $dbConn = DB::connection(config('database.school_connection', config('database.default')));

        $students = $dbConn->table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->select(
                'grade_level',
                'section_name',
                'subject',
                DB::raw('SUM(CAST(student_count AS UNSIGNED)) as total_students'),
                DB::raw('COUNT(*) as class_count')
            )
            ->groupBy('grade_level', 'section_name', 'subject')
            ->get();

        $totalStudents = $dbConn->table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->sum(DB::raw('CAST(student_count AS UNSIGNED)'));

        return response()->json([
            'success' => true,
            'data' => $students,
            'total_students' => $totalStudents ?? 0,
            'school_level' => $schoolLevel
        ]);
    }

    /**
     * Get class performance data (filtered by school_level)
     */
    public function getClassPerformance(Request $request)
    {
        $teacherId = Auth::id();
        $schoolLevel = $this->getTeacherSchoolLevel();

        $performance = DB::connection(config('database.school_connection', config('database.default')))->table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->select(
                'grade_level',
                'section_name',
                'subject',
                'student_count',
                'day_of_week',
                'start_time',
                'end_time'
            )
            ->get();

        $stats = [
            'total_classes' => $performance->count(),
            'total_students' => $performance->sum('student_count'),
            'average_class_size' => $performance->count() > 0
                ? round($performance->sum('student_count') / $performance->count(), 2)
                : 0
        ];

        return response()->json([
            'success' => true,
            'data' => $performance,
            'stats' => $stats
        ]);
    }

    /**
     * Get faculty teaching load
     */
    public function getFacultyLoad(Request $request)
    {
        $teacherId = Auth::id();

        // 1. Get admin-assigned faculty loads for this teacher (from mysql_jh via UseSchoolConnection)
        $connection = \App\Support\TeacherDatabaseSupport::connectionFromContext();
        $schedules = TeacherPortalSupport::workloadRecordsForTeacher($teacherId, $connection);
        $totalUnits = collect($schedules)->sum(fn ($s) => (int) ($s['units'] ?? 0));

        return response()->json([
            'success'     => true,
            'total_units' => $totalUnits > 0 ? $totalUnits : count($schedules),
            'schedules'   => $schedules,
        ]);
    }

    /**
     * API: Personal workload history (schedules + faculty loads).
     */
    public function getWorkloadHistory(Request $request)
    {
        $teacherId = Auth::id();
        $schoolYear = date('Y') . '-' . (date('Y') + 1);
        $connection = \App\Support\TeacherDatabaseSupport::connectionFromContext();
        $schedules = TeacherPortalSupport::workloadRecordsForTeacher($teacherId, $connection);
        $histories = collect($schedules)
            ->map(fn ($s) => TeacherPortalSupport::workloadHistoryEntry($s, $schoolYear))
            ->values();

        return response()->json(['success' => true, 'data' => $histories]);
    }

    /**
     * API: Schedules for the review-schedule page (Junior High).
     */
    public function getSchedulesForReview(Request $request)
    {
        $query = ClassSchedule::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->orderBy('created_at', 'desc')->get()->map(fn ($s) => $s->toArray())->all();
        $data = TeacherPortalSupport::enrichSchedulesForReview($rows, 'mysql_jh');

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get all teachers (for admin dashboard)
     */
    public function index(Request $request)
    {
        try {
            $teachers = User::whereHas('role', function($q) {
                $q->where('name', 'like', '%teacher%');
            })->get();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $teachers,
                    'count' => $teachers->count()
                ]);
            }

            return view('junior-high-admin.teachers.index', ['teachers' => $teachers]);
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Error fetching teachers']);
        }
    }

    // =========================================================================
    // PROVIDE FEEDBACK  (Use Case: Provide Feedback)
    // =========================================================================

    public function showFeedback()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $myFeedbacks = DB::connection(\App\Support\TeacherDatabaseSupport::connectionFromContext())->table('teacher_feedbacks')
            ->where('teacher_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('junior-high-teacher.feedback', [
            'myFeedbacks' => $myFeedbacks,
        ]);
    }

    public function submitFeedback(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'category' => 'required|in:schedule_clarity,workload_fairness,system_usability,other',
            'rating'   => 'required|integer|min:1|max:5',
            'message'  => 'required|string|max:2000',
        ]);

        DB::connection(\App\Support\TeacherDatabaseSupport::connectionFromContext())->table('teacher_feedbacks')->insert([
            'teacher_id'   => $user->id,
            'school_level' => 'junior_high',
            'category'     => $validated['category'],
            'rating'       => $validated['rating'],
            'message'      => $validated['message'],
            'status'       => 'submitted',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Feedback submitted successfully.']);
        }

        return redirect()->route('teacher.feedback')->with('success', 'Thank you! Your feedback has been submitted.');
    }

    public function showLoadingSchedule(\Illuminate\Http\Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $adminConn = \App\Support\TeacherDatabaseSupport::connectionFromContext();

        $loadingRows = DB::connection($adminConn)
            ->table('teacher_loading_schedules')
            ->where('faculty_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        if ($loadingRows->isNotEmpty()) {
            $schedules = $loadingRows->map(function ($row) {
                $row->room = \App\Support\TeacherPortalSupport::displayRoomFromRow($row);

                return $row;
            });
        } else {
            $classSchedules = \App\Models\ClassSchedule::where('faculty_id', $user->id)
                ->where('admin_approved', true)
                ->with(['room'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            $schedules = $classSchedules->map(fn ($s) => (object) [
                'id'           => $s->id,
                'subject_code' => null,
                'subject_name' => $s->subject,
                'grade_level'  => $s->grade_level,
                'section'      => $s->section_name,
                'day_of_week'  => $s->day_of_week,
                'time_start'   => $s->start_time,
                'time_end'     => $s->end_time,
                'room'         => \App\Support\TeacherPortalSupport::roomLabel($s),
                'units'        => $s->units ?? 1,
                'load_hours'   => $s->units ? round($s->units * 1.0, 2) : 0,
                'semester'     => '1st Semester',
                'school_year'  => date('Y') . '-' . (date('Y') + 1),
                'status'       => $s->admin_approved ? 'approved' : 'submitted',
            ]);
        }

        $weeklySchoolYear = $request->input('school_year', '2025-2026');
        $weeklyGrid = DB::connection($adminConn)
            ->table('master_weekly_schedules')
            ->where('faculty_id', $user->id)
            ->where('school_year', $weeklySchoolYear)
            ->orderBy('slot_order')
            ->get();

        $timeSlots = \App\Http\Controllers\MasterWeeklyScheduleController::timeSlots('junior_high');
        $days      = \App\Http\Controllers\MasterWeeklyScheduleController::days();

        return view('junior-high-teacher.loading-schedule', compact(
            'schedules', 'weeklyGrid', 'weeklySchoolYear', 'timeSlots', 'days'
        ));
    }

    public function requestAdjustments()
    {
        return view('junior-high-teacher.request-adjustments');
    }

    public function getAdjustmentRequests(Request $request)
    {
        try {
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('junior_high');
            $data = TeacherAdjustmentRequestSupport::listForTeacher($conn, (int) Auth::id());

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getAdjustmentScheduleOptions(Request $request)
    {
        try {
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('junior_high');
            $schedules = TeacherAdjustmentRequestSupport::approvedSchedulesForTeacher($conn, (int) Auth::id());
            $subjects = collect($schedules)
                ->pluck('subject')
                ->filter()
                ->unique(fn ($s) => strtolower(trim((string) $s)))
                ->values()
                ->all();

            return response()->json([
                'success'   => true,
                'subjects'  => $subjects,
                'schedules' => $schedules,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getAdjustmentAvailableTeachers(Request $request)
    {
        try {
            $validated = $request->validate([
                'subject'     => 'required|string|max:120',
                'grade_level' => 'nullable|string|max:80',
            ]);
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('junior_high');
            $teachers = TeacherAdjustmentRequestSupport::availableTeachersForReassignment(
                $conn,
                (int) Auth::id(),
                $validated['subject'],
                $validated['grade_level'] ?? null
            );

            return response()->json(['success' => true, 'teachers' => $teachers]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getLeaveRequests(Request $request)
    {
        try {
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('junior_high');
            $data = \App\Support\TeacherLeaveRequestSupport::listForTeacher($conn, (int) Auth::id());

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeLeaveRequest(Request $request)
    {
        try {
            $result = \App\Support\TeacherLeaveRequestSupport::store(
                $request,
                TeacherAdjustmentRequestSupport::connectionForSchool('junior_high')
            );

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeAdjustmentRequest(Request $request)
    {
        try {
            $result = TeacherAdjustmentRequestSupport::store($request, TeacherAdjustmentRequestSupport::connectionForSchool('junior_high'));

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function printExport()
    {
        $schedules = TeacherPortalSupport::teacherSchedulesForExport((int) Auth::id());

        return view('junior-high-teacher.print-export', [
            'schedules'       => $schedules,
            'exportCsvUrl'    => route('teacher.export.schedule', ['format' => 'csv']),
            'exportPrintUrl'  => route('teacher.export.schedule', ['format' => 'print']),
            'divisionLabel'   => 'Junior High Division',
        ]);
    }

    public function exportSchedule(Request $request)
    {
        $format = $request->query('format', 'csv');
        $schedules = TeacherPortalSupport::teacherSchedulesForExport((int) Auth::id());
        $user = Auth::user();
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher');

        if ($format === 'print') {
            return view('exports.teacher-schedule-print', [
                'schedules'     => $schedules,
                'teacherName'   => $name,
                'divisionLabel' => 'Junior High Division',
            ]);
        }

        $filename = 'my-schedule-' . date('Y-m-d') . ($format === 'excel' ? '.xls' : '.csv');
        $mime = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';

        return response()->streamDownload(function () use ($schedules) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Subject', 'Grade Level', 'Section', 'Day', 'Start', 'End', 'Room']);
            foreach ($schedules as $s) {
                fputcsv($out, [
                    $s->subject ?? '',
                    $s->grade_level ?? '',
                    $s->section_name ?? '',
                    $s->day_of_week ?? '',
                    $s->start_time ?? '',
                    $s->end_time ?? '',
                    TeacherPortalSupport::roomLabel($s),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => $mime]);
    }
}
