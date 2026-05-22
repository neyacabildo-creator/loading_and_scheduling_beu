<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Support\TeacherPortalSupport;
use Illuminate\Http\Request;

class STLController extends Controller
{
    /**
     * Get faculty count for authenticated STL's subject team
     */
    public function getFacultyCount()
    {
        try {
            $user = Auth::user();
            // Get all faculty in the same department/subject team
            $count = User::where('role_id', 3)
                ->where('id', '!=', $user->id)
                ->count();

            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get faculty loading count for STL's subject team
     */
    public function getLoadingCount()
    {
        try {
            $count = FacultyLoad::count();
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get scheduled classes count for STL's subject team
     */
    public function getScheduleCount()
    {
        try {
            $count = ClassSchedule::whereIn('status', ['pending', 'approved'])->count();
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending approvals/reviews count
     */
    public function getPendingCount()
    {
        try {
            $count = ClassSchedule::where('status', 'pending')->count();
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities()
    {
        try {
            $activities = [];

            // Get recent schedule actions
            $recentSchedules = ClassSchedule::orderBy('updated_at', 'desc')->limit(3)->get();
            foreach ($recentSchedules as $schedule) {
                $activities[] = [
                    'message' => 'Schedule updated: ' . $schedule->subject . ' (' . $schedule->status . ')',
                    'time' => $schedule->updated_at->diffForHumans()
                ];
            }

            // Get recent faculty loads
            $recentLoads = FacultyLoad::orderBy('updated_at', 'desc')->limit(2)->get();
            foreach ($recentLoads as $load) {
                $activities[] = [
                    'message' => 'Faculty load updated',
                    'time' => $load->updated_at->diffForHumans()
                ];
            }

            usort($activities, function ($a, $b) {
                // This is a simple sort; in production, timestamps would be more precise
                return 0;
            });

            return response()->json(['success' => true, 'activities' => array_slice($activities, 0, 5)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'activities' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Team schedule listing for STL Review Schedule page.
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
     * Manage Faculty Loading - Subject Specific
     */
    public function manageFacultyLoading()
    {
        $faculties = User::where('role_id', 3)->get();
        $loads = FacultyLoad::get();
        $userIds = $loads->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $loads->each(function (FacultyLoad $l) use ($users) { $l->setRelation('faculty', $users[$l->faculty_id] ?? null); });

        return view('junior-high-teacher.manage-faculty-loading-stl', [
            'faculties' => $faculties,
            'loads' => $loads
        ]);
    }

    /**
     * Assign Subject to Faculty
     */
    public function assignSubjects()
    {
        $faculties = User::where('role_id', 3)->get();
        $subjects = FacultyLoad::distinct('subject')->pluck('subject');

        return view('junior-high-teacher.assign-subjects-stl', [
            'faculties' => $faculties,
            'subjects' => $subjects
        ]);
    }

    /**
     * Store subject assignment
     */
    public function storeSubjectAssignment(Request $request)
    {
        try {
            $validated = $request->validate([
                'faculty_id' => 'required|exists:users,id',
                'subject' => 'required|string',
                'load_hours' => 'required|numeric|min:1'
            ]);

            $load = FacultyLoad::updateOrCreate(
                ['faculty_id' => $validated['faculty_id'], 'subject' => $validated['subject']],
                ['load_hours' => $validated['load_hours']]
            );

            return response()->json(['success' => true, 'message' => 'Subject assigned successfully', 'data' => $load]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * View DSS Recommendations - Decision Support System
     */
    public function viewDSSRecommendations()
    {
        try {
            // Generate AI-based recommendations for subject team optimization
            $recommendations = $this->generateDSSRecommendations();

            return view('junior-high-teacher.dss-recommendations-stl', [
                'recommendations' => $recommendations
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate DSS Recommendations based on current data
     */
    private function generateDSSRecommendations()
    {
        $recommendations = [];

        // Get current load data
        $totalLoads = FacultyLoad::sum('load_hours');
        $facultyCount = User::where('role_id', 3)->count();

        // Recommendation 1: Workload Balancing
        if ($facultyCount > 0) {
            $avgLoad = $totalLoads / $facultyCount;
            $recommendations[] = [
                'title' => 'Workload Balancing',
                'description' => 'Consider redistributing loads to achieve better balance across faculty',
                'priority' => 'High',
                'action' => 'Review overloaded faculty members',
                'impact' => 'Improves faculty satisfaction and performance'
            ];
        }

        // Recommendation 2: Schedule Optimization
        $pendingSchedules = ClassSchedule::where('status', 'pending')->count();
        if ($pendingSchedules > 0) {
            $recommendations[] = [
                'title' => 'Schedule Approval Needed',
                'description' => 'Review and approve pending schedules to maintain smooth operations',
                'priority' => 'Medium',
                'action' => 'Process pending schedules',
                'impact' => 'Ensures timely schedule execution'
            ];
        }

        // Recommendation 3: Resource Allocation
        $recommendations[] = [
            'title' => 'Resource Allocation Review',
            'description' => 'Analyze room and equipment allocation for optimal utilization',
            'priority' => 'Medium',
            'action' => 'Review resource distribution',
            'impact' => 'Reduces conflicts and improves efficiency'
        ];

        return $recommendations;
    }

    /**
     * Request Schedule Adjustment
     */
    public function requestAdjustment(Request $request)
    {
        try {
            if ($request->filled('proposed_change') && ! $request->filled('proposed_changes')) {
                $request->merge(['proposed_changes' => $request->input('proposed_change')]);
            }
            if (! $request->filled('request_type')) {
                $request->merge(['request_type' => 'other']);
            }

            $result = \App\Support\TeacherAdjustmentRequestSupport::store(
                $request,
                \App\Support\TeacherAdjustmentRequestSupport::adminConnectionForSchool('junior_high')
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'id'      => $result['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * View Faculty Workload History
     */
    public function viewWorkloadHistory()
    {
        try {
            $faculties = User::where('role_id', 3)->with('loads')->get();
            $historyData = $this->generateWorkloadHistory($faculties);

            return view('junior-high-teacher.workload-history-stl', [
                'faculties' => $faculties,
                'historyData' => $historyData
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate workload history data
     */
    private function generateWorkloadHistory($faculties)
    {
        $history = [];

        foreach ($faculties as $faculty) {
            $totalLoad = $faculty->loads->sum('load_hours');
            $avgLoad = $faculty->loads->count() > 0 ? $totalLoad / $faculty->loads->count() : 0;

            $history[$faculty->id] = [
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
                'total_load' => $totalLoad,
                'assignments' => $faculty->loads->count(),
                'average_load' => round($avgLoad, 2),
                'trend' => 'stable'  // Could be enhanced with historical data
            ];
        }

        return $history;
    }

    /**
     * Generate Reports - Subject Team Specific
     */
    public function generateReports(Request $request)
    {
        try {
            $reportType = $request->get('type', 'summary');
            $period = $request->get('period', 'current');

            $reportData = $this->prepareReportData($reportType, $period);

            return view('junior-high-teacher.generate-reports-stl', [
                'reportData' => $reportData,
                'reportType' => $reportType,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Prepare report data based on type and period
     */
    private function prepareReportData($type, $period)
    {
        $data = [
            'title' => 'Subject Team Report',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'period' => $period,
            'summary' => []
        ];

        // Get team summary
        $totalFaculty = User::where('role_id', 3)->count();
        $totalLoads = FacultyLoad::sum('load_hours');
        $totalSchedules = ClassSchedule::count();

        $data['summary'] = [
            'total_faculty' => $totalFaculty,
            'total_load_hours' => $totalLoads,
            'total_schedules' => $totalSchedules,
            'average_load' => $totalFaculty > 0 ? $totalLoads / $totalFaculty : 0
        ];

        if ($type === 'workload') {
            $wloads = FacultyLoad::get();
            $wUserIds = $wloads->pluck('faculty_id')->filter()->unique();
            $wUsers = $wUserIds->isNotEmpty() ? User::whereIn('id', $wUserIds)->get()->keyBy('id') : collect();
            $data['workload_details'] = $wloads->map(function ($load) use ($wUsers) {
                $u = $wUsers[$load->faculty_id] ?? null;
                return [
                    'faculty'    => $u ? ($u->first_name . ' ' . $u->last_name) : 'N/A',
                    'subject'    => $load->subject,
                    'load_hours' => $load->load_hours
                ];
            })->toArray();
        }

        if ($type === 'schedule') {
            $data['schedule_details'] = ClassSchedule::get()->map(function ($schedule) {
                return [
                    'subject' => $schedule->subject,
                    'day' => $schedule->day_of_week,
                    'time' => $schedule->time_start . ' - ' . $schedule->time_end,
                    'room' => $schedule->room,
                    'status' => $schedule->status
                ];
            })->toArray();
        }

        return $data;
    }
}
