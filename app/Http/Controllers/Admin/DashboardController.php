<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CombinedScheduleService;
use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get the authenticated admin's department (Junior High only after school-level segregation)
     */
    private function getAdminDepartment() {
        return 'junior_high';
    }

    /**
     * Show admin dashboard
     */
    public function index()
    {
        try {
            $department = $this->getAdminDepartment();
            
            // Get statistics for dashboard (operational tables scoped to JH DB by middleware)
            $totalFaculty = User::whereHas('role', function($q) { 
                $q->where('name', 'like', '%teacher%'); 
            })
            ->where('school_level', $department)
            ->count();
            
            $totalClasses = ClassSchedule::count();
            
            $totalRooms = Room::count();
            
            $pendingApprovals = ClassSchedule::where('admin_approved', false)
                ->where('status', 'pending')
                ->count();
            
            $approvedSchedules = ClassSchedule::where('admin_approved', true)
                ->where('status', 'active')
                ->count();
            
            $totalLoadHours = FacultyLoad::sum('load_hours');
            
            $schedulingConflicts = ClassSchedule::where('status', 'conflict')->count();
            // Also detect teacher double-booking: same teacher, day, start_time in active approved schedules
            try {
                $dblRow = DB::connection('mysql_jh')->selectOne(
                    "SELECT COUNT(*) AS cnt FROM (
                        SELECT faculty_id, day_of_week, start_time
                        FROM class_schedules
                        WHERE admin_approved = 1 AND status = 'active' AND faculty_id IS NOT NULL
                        GROUP BY faculty_id, day_of_week, start_time
                        HAVING COUNT(*) > 1
                    ) t"
                );
                $schedulingConflicts += (int) ($dblRow->cnt ?? 0);
            } catch (\Exception $e) {}
            
            // Faculty loads data (all in JH DB)
            $facultyLoadsData = FacultyLoad::all();
            $totalFacultyLoads = $facultyLoadsData->count();

            // Compute breakdown from actual schedule assignments (status field values vary)
            $activeScheduleFacultyIds = ClassSchedule::where('admin_approved', true)
                ->where('status', 'active')
                ->whereNotNull('faculty_id')
                ->distinct()
                ->pluck('faculty_id')
                ->toArray();
            $activeFacultyLoads    = $facultyLoadsData->filter(fn($fl) => in_array($fl->faculty_id, $activeScheduleFacultyIds))->count();
            $availableFacultyLoads = $facultyLoadsData->filter(fn($fl) => !in_array($fl->faculty_id, $activeScheduleFacultyIds))->count();
            $overloadFacultyLoads  = $facultyLoadsData->filter(fn($fl) => ($fl->load_hours ?? 0) > 6)->count();
            
            // Rooms data (all in JH DB)
            $roomsData = Room::all();
            $totalRoomsCount = $roomsData->count();
            $availableRooms = $roomsData->where('status', 'available')->count();
            $inUseRooms = $roomsData->where('status', 'in-use')->count();
            $maintenanceRooms = $roomsData->where('status', 'maintenance')->count();
            
            // Teachers data (User is in shared DB — keep school_level filter)
            $teachersData = User::whereHas('role', function($q) { 
                $q->where('name', 'like', '%teacher%'); 
            })
            ->where('school_level', $department)
            ->get();
            $totalTeachers = $teachersData->count();
            $activeTeachers = $teachersData->where('status', 'active')->count();
            $inactiveTeachers = $teachersData->where('status', 'inactive')->count();
            
            // Get recent activities (all in JH DB)
            $recentSchedules = ClassSchedule::latest('updated_at')->limit(5)->get();

            // Load faculty/room for recent schedules explicitly (cross-connection safe)
            $recentUserIds = $recentSchedules->pluck('faculty_id')->merge($recentSchedules->pluck('approved_by'))->filter()->unique();
            $recentUsers = User::whereIn('id', $recentUserIds)->get()->keyBy('id');
            $recentRoomIds = $recentSchedules->pluck('room_id')->filter()->unique();
            $recentRooms = $recentRoomIds->isNotEmpty() ? Room::whereIn('id', $recentRoomIds)->get()->keyBy('id') : collect();
            $recentSchedules->each(function ($s) use ($recentUsers, $recentRooms) {
                $s->setRelation('faculty',  $recentUsers[$s->faculty_id] ?? null);
                $s->setRelation('room',     $recentRooms[$s->room_id] ?? null);
                $s->setRelation('approver', isset($s->approved_by) ? ($recentUsers[$s->approved_by] ?? null) : null);
            });
            
            // Get overloaded faculty (all in JH DB, cross-connection safe)
            $overloadedFacultyLoads = FacultyLoad::where('status', 'overloaded')->get();
            $overloadedUserIds = $overloadedFacultyLoads->pluck('faculty_id')->filter()->unique();
            $overloadedUsers = $overloadedUserIds->isNotEmpty() ? User::whereIn('id', $overloadedUserIds)->get()->keyBy('id') : collect();
            $overloadedFacultyLoads->each(function ($fl) use ($overloadedUsers) {
                $fl->setRelation('faculty', $overloadedUsers[$fl->faculty_id] ?? null);
            });
            $overloadedFaculty = $overloadedFacultyLoads;

            // Shared teacher requests stats (JH DB)
            try {
                $stReqTotal    = DB::connection('mysql_jh')->table('shared_teacher_requests')->count();
                $stReqPending  = DB::connection('mysql_jh')->table('shared_teacher_requests')->where('status','pending')->count();
                $stReqApproved = DB::connection('mysql_jh')->table('shared_teacher_requests')->where('status','approved')->count();
                $stReqRejected = DB::connection('mysql_jh')->table('shared_teacher_requests')->where('status','rejected')->count();
            } catch (\Exception $e) {
                $stReqTotal = $stReqPending = $stReqApproved = $stReqRejected = 0;
            }

            $timetableSchedules = array_values(array_filter(
                CombinedScheduleService::fetchApproved(),
                fn ($s) => ($s['school'] ?? '') === 'JH'
            ));

            $sharedTeacherIds = DB::connection('mysql_jh')->table('shared_teachers')
                ->where('is_active', true)->pluck('faculty_id')->map(fn ($id) => (int) $id)->all();
            $leaveBanner = \App\Support\TeacherPresenceSupport::collectActiveLeaveBannerData('mysql_jh', $sharedTeacherIds);

            // Junior High Admin dashboard
            return view('junior-high-admin.dashboard', [
                'leaveBanner' => $leaveBanner,
                'timetableSchedules' => $timetableSchedules,
                // Header stats
                'totalFaculty' => $totalFaculty,
                'totalClasses' => $totalClasses,
                'totalRooms' => $totalRoomsCount,
                'pendingApprovals' => $pendingApprovals,
                'approvedSchedules' => $approvedSchedules,
                'totalLoadHours' => $totalLoadHours ?? 0,
                'schedulingConflicts' => $schedulingConflicts,
                
                // Recent data
                'recentSchedules' => $recentSchedules,
                'overloadedFaculty' => $overloadedFaculty,
                
                // Faculty loads overview (fallback values)
                'totalFacultyLoads' => $totalFacultyLoads ?? 0,
                'activeFacultyLoads' => $activeFacultyLoads ?? 0,
                'availableFacultyLoads' => $availableFacultyLoads ?? 0,
                'overloadFacultyLoads' => $overloadFacultyLoads ?? 0,
                
                // Rooms overview (fallback values)
                'totalRoomsCount' => $totalRoomsCount ?? 0,
                'availableRooms' => $availableRooms ?? 0,
                'inUseRooms' => $inUseRooms ?? 0,
                'maintenanceRooms' => $maintenanceRooms ?? 0,
                
                // Teachers overview (fallback values)
                'totalTeachers' => $totalTeachers ?? 0,
                'activeTeachers' => $activeTeachers ?? 0,
                'inactiveTeachers' => $inactiveTeachers ?? 0,
                
                // Department info
                'department' => $department,

                // Shared teacher requests
                'stReqTotal'    => $stReqTotal,
                'stReqPending'  => $stReqPending,
                'stReqApproved' => $stReqApproved,
                'stReqRejected' => $stReqRejected,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('Admin Dashboard Error: ' . $e->getMessage());
            
            // Return a simple error page to avoid redirect loops
            return response('<div style="font-family:sans-serif;padding:2rem;"><h2>Dashboard Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><a href="/logout">Logout</a></div>', 500);
        }
    }
}
