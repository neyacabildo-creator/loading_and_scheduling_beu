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
        try {
            $user = Auth::user();
            $schoolLevel = $this->getTeacherSchoolLevel();
            
            $mySchedules = ClassSchedule::where('faculty_id', $user->id)
                ->where(function($q) {
                    $q->where('admin_approved', true)->orWhere('status', 'active');
                })
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
            
            $myClasses = $mySchedules->count();
            $totalStudents = $mySchedules->sum(function($schedule) {
                return $schedule->student_count ?? 0;
            });
            $teachingLoad = $mySchedules->sum(function($schedule) {
                return $schedule->units ?? 0;
            });
            $pendingTasks = $mySchedules->where('status', 'pending')->count();
            
            $isSTL = $this->checkIfSTL($user);
            $stlData = [];
            
            if ($isSTL) {
                $stlData = [
                    'isSTL' => true,
                    'facultyCount' => $this->getFacultyCountForTeam($user),
                    'loadCount' => $this->getLoadCountForTeam($user),
                    'scheduleCount' => $this->getScheduleCountForTeam($user),
                    'pendingReviews' => $this->getPendingReviewsForTeam($user),
                    'dssRecommendations' => $this->getDSSRecommendations($user),
                    'teamMembers' => $this->getTeamMembers($user),
                ];
            }
            
            return view('grade-school-teacher.dashboard', [
                'mySchedules' => $mySchedules,
                'myClasses' => $myClasses,
                'totalStudents' => $totalStudents,
                'teachingLoad' => $teachingLoad,
                'pendingTasks' => $pendingTasks,
                'isSTL' => $isSTL,
                'stlData' => $stlData,
                'school_level' => $schoolLevel,
            ]);
            
        } catch (\Exception $e) {
            return back()->withError('Error loading dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Check if user is STL
     */
    private function checkIfSTL($user)
    {
        return $user->role_id === 3 || $user->has_stl_permissions === true;
    }

    /**
     * Get faculty count for team (Grade School only)
     */
    private function getFacultyCountForTeam($user)
    {
        $schoolLevel = $this->getTeacherSchoolLevel();
        return User::where('school_level', $schoolLevel)
            ->where('role_id', 3)
            ->where('id', '!=', $user->id)
            ->count();
    }

    /**
     * Get load count for team (Grade School only)
     */
    private function getLoadCountForTeam($user)
    {
        $schoolLevel = $this->getTeacherSchoolLevel();
        return FacultyLoad::whereHas('faculty', function($query) use ($schoolLevel) {
                $query->where('school_level', $schoolLevel);
            })->count();
    }

    /**
     * Get schedule count for team (Grade School only)
     */
    private function getScheduleCountForTeam($user)
    {
        return ClassSchedule::count();
    }

    /**
     * Get pending reviews for team (Grade School only)
     */
    private function getPendingReviewsForTeam($user)
    {
        return ClassSchedule::where('status', 'pending')->count();
    }

    /**
     * Get DSS recommendations
     */
    private function getDSSRecommendations($user)
    {
        return [
            ['type' => 'balance', 'message' => 'Balance faculty loads across team members'],
            ['type' => 'schedule', 'message' => 'Review time-slot conflicts'],
            ['type' => 'expertise', 'message' => 'Align faculty with subject expertise'],
        ];
    }

    /**
     * Get team members for STL (Grade School only)
     */
    private function getTeamMembers($user)
    {
        $schoolLevel = $this->getTeacherSchoolLevel();
        return User::where('school_level', $schoolLevel)
            ->where('role_id', 3)
            ->where('id', '!=', $user->id)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();
    }

    /**
     * Get all classes (Grade School only)
     */
    public function getMyClasses(Request $request)
    {
        $teacherId = Auth::id();
        $schoolLevel = $this->getTeacherSchoolLevel();

        $classes = ClassSchedule::where('faculty_id', $teacherId)
            ->where(function ($q) {
                $q->where('admin_approved', true)->orWhere('status', 'active');
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->with(['room'])
            ->get();

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
        $facultyLoads = FacultyLoad::where('faculty_id', $teacherId)->get();

        $classSchedules = ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->whereNotIn('status', ['pending', 'rejected', 'deleted'])
            ->with(['room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $schedules = TeacherPortalSupport::buildWorkloadSchedules($classSchedules, $facultyLoads);
        $totalUnits = collect($schedules)->sum(fn ($s) => (int) ($s['units'] ?? 1));

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
     * Show manage faculty loading page (STL feature)
     */
    public function manageFacultyLoading() {
        $user = Auth::user();
        $teamMembers = $this->getTeamMembers($user);
        return view('grade-school-teacher.manage-faculty-loading', compact('teamMembers'));
    }

    /**
     * Show DSS recommendations page
     */
    public function dssRecommendations() {
        return view('grade-school-teacher.dss-recommendations');
    }

    /**
     * Show review schedule page
     */
    public function reviewSchedule() {
        return view('grade-school-teacher.review-schedule');
    }

    /**
     * Show generate reports page
     */
    public function generateReports() {
        return view('grade-school-teacher.generate-reports');
    }

    /**
     * Show assign subjects page
     */
    public function assignSubjects() {
        $user = Auth::user();
        $teamMembers = $this->getTeamMembers($user);
        return view('grade-school-teacher.assign-subjects', compact('teamMembers'));
    }

    /**
     * Show request adjustments page
     */
    public function requestAdjustments() {
        return view('grade-school-teacher.request-adjustments');
    }

    /**
     * Show workload history page
     */
    public function workloadHistory() {
        return view('grade-school-teacher.workload-history');
    }

    // -------------------------------------------------------------------------
    // API ENDPOINTS FOR NEW FEATURES
    // -------------------------------------------------------------------------

    /**
     * API: Get faculty loads for team (GS manage-faculty-loading)
     */
    public function getTeamFacultyLoads(Request $request)
    {
        $user = Auth::user();
        $schoolLevel = $this->getTeacherSchoolLevel();
        $loads = FacultyLoad::with('faculty')
            ->whereHas('faculty', function ($q) use ($schoolLevel) {
                $q->where('school_level', $schoolLevel);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $loads]);
    }

    /**
     * API: Store a faculty load entry
     */
    public function storeTeamFacultyLoad(Request $request)
    {
        try {
            $validated = $request->validate([
                'faculty_id'  => 'required|integer',
                'subject'     => 'required|string|max:255',
                'grade_level' => 'nullable|string|max:50',
                'section'     => 'nullable|string|max:50',
                'units'       => 'nullable|integer|min:0',
                'room'        => 'nullable|string|max:100',
                'day_of_week' => 'nullable|string|max:20',
                'start_time'  => 'nullable|string',
                'end_time'    => 'nullable|string',
            ]);

            $load = FacultyLoad::create(array_merge($validated, [
                'academic_year' => date('Y') . '-' . (date('Y') + 1),
                'semester'      => '1st',
                'status'        => 'active',
            ]));

            return response()->json(['success' => true, 'data' => $load, 'message' => 'Faculty load added.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Delete a faculty load entry
     */
    public function deleteTeamFacultyLoad(Request $request, $id)
    {
        FacultyLoad::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Faculty load removed.']);
    }

    /**
     * API: Get DSS recommendations (GS)
     */
    public function getDSSRecommendationsAPI(Request $request)
    {
        $user = Auth::user();
        $db = DB::connection(config('database.school_connection', config('database.default')));

        $recs = $db->table('dss_recommendations')
            ->where('status', 'active')
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recs->isEmpty()) {
            $recs = collect([
                (object)['id' => 1, 'type' => 'balance',    'priority' => 'high',   'message' => 'Balance faculty loads across team members', 'status' => 'active', 'created_at' => now()],
                (object)['id' => 2, 'type' => 'schedule',   'priority' => 'medium', 'message' => 'Review time-slot conflicts in current schedule', 'status' => 'active', 'created_at' => now()],
                (object)['id' => 3, 'type' => 'expertise',  'priority' => 'low',    'message' => 'Align faculty with subject expertise', 'status' => 'active', 'created_at' => now()],
            ]);
        }

        return response()->json(['success' => true, 'data' => $recs]);
    }

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
     * API: Get subject assignments (GS)
     * Uses mysql_gs admin DB (same data as grade-school admin).
     */
    public function getSubjectAssignments(Request $request)
    {
        $db = DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'));
        $assignments = $db->table('subject_assignments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    /**
     * API: Store a subject assignment (GS)
     */
    public function storeSubjectAssignment(Request $request)
    {
        try {
            $validated = $request->validate([
                'faculty_id'  => 'required|integer',
                'subject'     => 'required|string|max:255',
                'grade_level' => 'nullable|string|max:50',
                'units'       => 'nullable|integer|min:0',
                'notes'       => 'nullable|string|max:500',
            ]);

            $validated['assigned_by'] = Auth::id();
            $validated['status'] = 'assigned';

            $db = DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'));
            $id = $db->table('subject_assignments')->insertGetId(array_merge($validated, [
                'created_at' => now(), 'updated_at' => now(),
            ]));

            return response()->json(['success' => true, 'id' => $id, 'message' => 'Subject assigned.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Delete a subject assignment (GS)
     */
    public function deleteSubjectAssignment(Request $request, $id)
    {
        $db = DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'));
        $db->table('subject_assignments')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
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
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
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

        $facultyLoads = FacultyLoad::where('faculty_id', $teacherId)->get();
        $classSchedules = ClassSchedule::where('faculty_id', $teacherId)
            ->whereNotIn('status', ['rejected', 'deleted'])
            ->where(function ($q) {
                $q->where('admin_approved', true)
                    ->orWhereIn('status', ['active', 'approved']);
            })
            ->with(['room'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $schedules = TeacherPortalSupport::buildWorkloadSchedules($classSchedules, $facultyLoads);
        $histories = collect($schedules)->map(fn ($s) => [
            'id'          => $s['id'] ?? null,
            'subject'     => $s['subject'] ?? $s['subject_name'] ?? '—',
            'grade_level' => $s['grade_level'] ?? null,
            'section'     => $s['section_name'] ?? null,
            'day_of_week' => $s['day_of_week'] ?? '—',
            'start_time'  => $s['start_time'] ?? null,
            'end_time'    => $s['end_time'] ?? null,
            'units'       => (int) ($s['units'] ?? 1),
            'load_hours'  => (float) ($s['load_hours'] ?? 0),
            'school_year' => $schoolYear,
            'status'      => $s['status'] ?? 'active',
        ])->values();

        return response()->json(['success' => true, 'data' => $histories]);
    }

    /**
     * API: Generate report data (GS)
     */
    public function generateReportData(Request $request)
    {
        try {
            $type = $request->input('type', 'faculty_loading');
            $user = Auth::user();
            $adminDb = DB::connection(\App\Support\TeacherDatabaseSupport::connectionForSchool('grade_school'));

            $data = match ($type) {
                'faculty_loading' => FacultyLoad::with('faculty')
                    ->whereHas('faculty', fn($q) => $q->where('school_level', $this->getTeacherSchoolLevel()))
                    ->get(),
                'schedule_listing' => ClassSchedule::with(['faculty', 'room'])
                    ->get(),
                'subject_assignments' => $adminDb->table('subject_assignments')->orderBy('created_at', 'desc')->get(),
                default => collect([]),
            };

            return response()->json(['success' => true, 'data' => $data, 'type' => $type]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

        $timeSlots = \App\Http\Controllers\MasterWeeklyScheduleController::timeSlots();
        $days      = \App\Http\Controllers\MasterWeeklyScheduleController::days();

        return view('grade-school-teacher.loading-schedule', compact(
            'schedules', 'weeklyGrid', 'weeklySchoolYear', 'timeSlots', 'days'
        ));
    }

}
