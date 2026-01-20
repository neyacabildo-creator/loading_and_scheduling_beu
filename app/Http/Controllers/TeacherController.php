<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
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

            User::create([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'role'       => 'teacher', // 🔥 IMPORTANT
                'status'     => $validated['status'],
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
     * Get all classes for the logged-in teacher
     */
    public function getMyClasses(Request $request)
    {
        $teacherId = Auth::id();

        $classes = ClassSchedule::where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->with(['room', 'faculty'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes,
            'count' => $classes->count()
        ]);
    }

    /**
     * Get all students in teacher's classes
     */
    public function getMyStudents(Request $request)
    {
        $teacherId = Auth::id();

        $students = DB::table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->select(
                'grade_section',
                'subject',
                DB::raw('SUM(CAST(student_count AS UNSIGNED)) as total_students'),
                DB::raw('COUNT(*) as class_count')
            )
            ->groupBy('grade_section', 'subject')
            ->get();

        $totalStudents = DB::table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->sum(DB::raw('CAST(student_count AS UNSIGNED)'));

        return response()->json([
            'success' => true,
            'data' => $students,
            'total_students' => $totalStudents ?? 0
        ]);
    }

    /**
     * Get class performance data
     */
    public function getClassPerformance(Request $request)
    {
        $teacherId = Auth::id();

        $performance = DB::table('class_schedules')
            ->where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->select(
                'grade_section',
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

        $schedules = ClassSchedule::where('faculty_id', $teacherId)
            ->where('status', 'active')
            ->where('admin_approved', true)
            ->with(['room'])
            ->get();

        $totalUnits = $schedules->count();

        return response()->json([
            'success' => true,
            'total_units' => $totalUnits,
            'schedules' => $schedules
        ]);
    }
}
