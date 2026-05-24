<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\FacultyLoad;
use App\Support\TeacherAdjustmentRequestSupport;
use App\Support\TeacherPortalSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GradeSchoolTeacherController extends Controller
{
    /**
     * Get the authenticated teacher's school level (Grade School)
     */
    private function getTeacherSchoolLevel() {
        return 'grade_school';
    }

    /**
     * Display the Grade School teacher dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $metrics = TeacherPortalSupport::dashboardMetrics((int) $user->id);

        return view('grade-school-teacher.dashboard', array_merge($metrics, [
            'school_level' => 'grade_school',
        ]));
    }

    /**
     * Get all classes (Grade School only)
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
     * Get all students in classes (Grade School only)
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
     * Get class performance data (Grade School only)
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
            'stats' => $stats,
            'school_level' => $schoolLevel
        ]);
    }

    /**
     * Get faculty load (Grade School only)
     */
    public function getFacultyLoad(Request $request)
    {
        $teacherId = Auth::id();

        // Get all admin-assigned faculty loads for this teacher (mysql_gs via UseSchoolConnection)
        $connection = \App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school');
        $schedules = TeacherPortalSupport::workloadRecordsForTeacher($teacherId, $connection);
        $totalUnits = collect($schedules)->sum(fn ($s) => (int) ($s['units'] ?? 0));

        return response()->json([
            'success'     => true,
            'total_units' => $totalUnits > 0 ? $totalUnits : count($schedules),
            'schedules'   => $schedules,
        ]);
    }

    /**
     * Get grades submitted (Grade School only)
     */
    public function getGrades(Request $request)
    {
        $teacherId = Auth::id();
        $schoolLevel = $this->getTeacherSchoolLevel();

        $schedules = ClassSchedule::where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->select('id', 'subject', 'grade_level', 'section_name')
            ->get();

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * Submit grades (Grade School only)
     */
    public function submitGrades(Request $request)
    {
        try {
            $teacherId = Auth::id();
            $schoolLevel = $this->getTeacherSchoolLevel();

            $validated = $request->validate([
                'schedule_id' => 'required|exists:class_schedules,id',
                'grades' => 'required|array',
            ]);

            $schedule = ClassSchedule::where('id', $validated['schedule_id'])
                ->where('faculty_id', $teacherId)
                ->firstOrFail();

            return response()->json(['success' => true, 'message' => 'Grades submitted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error submitting grades'], 400);
        }
    }

    /**
     * Show class schedule page
     */
    public function classSchedule() {
        return view('grade-school-teacher.class-schedule');
    }

    /**
     * Show my classes page
     */
    public function myClasses() {
        return view('grade-school-teacher.my-classes');
    }

    /**
     * Show my students page
     */
    public function myStudents() {
        return view('grade-school-teacher.my-students');
    }

    /**
     * Show class performance page
     */
    public function classPerformance() {
        return view('grade-school-teacher.class-performance');
    }

    /**
     * Show faculty loading page
     */
    public function facultyLoading() {
        return view('grade-school-teacher.faculty-loading');
    }

    /**
     * Show print/export page
     */
    public function printExport()
    {
        $schedules = TeacherPortalSupport::teacherSchedulesForExport((int) Auth::id());

        return view('grade-school-teacher.print-export', [
            'schedules'       => $schedules,
            'exportCsvUrl'    => route('grade-school-teacher.export.schedule', ['format' => 'csv']),
            'exportPrintUrl'  => route('grade-school-teacher.export.schedule', ['format' => 'print']),
            'divisionLabel'   => 'Grade School Division',
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
                'divisionLabel' => 'Grade School Division',
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

    /**
     * Show review schedule page
     */
    public function reviewSchedule()
    {
        return view('grade-school-teacher.review-schedule');
    }

    /**
     * Show request adjustments page
     */
    public function requestAdjustments()
    {
        return view('grade-school-teacher.request-adjustments');
    }

    // -------------------------------------------------------------------------
    // API ENDPOINTS
    // -------------------------------------------------------------------------

    /**
     * API: Get schedules for review (GS)
     */
    public function getSchedulesForReview(Request $request)
    {
        $user = Auth::user();
        $query = ClassSchedule::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->orderBy('created_at', 'desc')->get()->map(fn ($s) => $s->toArray())->all();
        $data = TeacherPortalSupport::enrichSchedulesForReview($rows, 'mysql_gs');

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * API: Get adjustment requests (GS)
     * Uses mysql_gs admin DB (teacher_requests).
     */
    public function getAdjustmentRequests(Request $request)
    {
        try {
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('grade_school');
            $data = TeacherAdjustmentRequestSupport::listForTeacher($conn, (int) Auth::id());

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getAdjustmentScheduleOptions(Request $request)
    {
        try {
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('grade_school');
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
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('grade_school');
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
            $conn = TeacherAdjustmentRequestSupport::connectionForSchool('grade_school');
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
                TeacherAdjustmentRequestSupport::connectionForSchool('grade_school')
            );

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Store adjustment request (GS)
     */
    public function storeAdjustmentRequest(Request $request)
    {
        try {
            $result = TeacherAdjustmentRequestSupport::store($request, TeacherAdjustmentRequestSupport::connectionForSchool('grade_school'));

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

    /**
     * API: Get workload history (GS)
     */
    public function getWorkloadHistory(Request $request)
    {
        $teacherId = Auth::id();
        $schoolYear = date('Y') . '-' . (date('Y') + 1);

        $connection = \App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school');
        $schedules = TeacherPortalSupport::workloadRecordsForTeacher($teacherId, $connection);
        $histories = collect($schedules)
            ->map(fn ($s) => TeacherPortalSupport::workloadHistoryEntry($s, $schoolYear))
            ->values();

        return response()->json(['success' => true, 'data' => $histories]);
    }

    // =========================================================================
    // PROVIDE FEEDBACK  (Use Case: Provide Feedback)
    // =========================================================================

    public function showFeedback()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $myFeedbacks = DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'))->table('teacher_feedbacks')
            ->where('teacher_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('grade-school-teacher.feedback', [
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

        DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'))->table('teacher_feedbacks')->insert([
            'teacher_id'   => $user->id,
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

        return redirect()->route('grade-school-teacher.feedback')->with('success', 'Thank you! Your feedback has been submitted.');
    }

    public function showLoadingSchedule(\Illuminate\Http\Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $adminConn = \App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school');

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
            $classSchedules = ClassSchedule::where('faculty_id', $user->id)
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

        $timeSlots = \App\Http\Controllers\MasterWeeklyScheduleController::timeSlots('grade_school');
        $days      = \App\Http\Controllers\MasterWeeklyScheduleController::days();

        return view('grade-school-teacher.loading-schedule', compact(
            'schedules', 'weeklyGrid', 'weeklySchoolYear', 'timeSlots', 'days'
        ));
    }

}
