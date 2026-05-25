<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ScheduleApproval;
use App\Models\User;
use App\Models\Room;
use App\Support\ScheduleDisplaySupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleApprovalController extends Controller
{
    /**
     * Display pending schedules for approval
     */
    public function index(Request $request)
    {
        $query = ClassSchedule::query();
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('admin_approved', false)->where('status', 'pending');
            } elseif ($request->status === 'approved') {
                $query->where('admin_approved', true)->where('status', 'active');
            } elseif ($request->status === 'rejected') {
                $query->where('status', 'rejected');
            }
        }
        
        $schedules = $query->paginate(20);

        // Manually load faculty and room to avoid cross-connection eager loading
        $userIds = $schedules->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $roomIds = $schedules->pluck('room_id')->filter()->unique();
        $rooms = $roomIds->isNotEmpty() ? Room::whereIn('id', $roomIds)->get()->keyBy('id') : collect();
        $schedules->each(function ($s) use ($users, $rooms) {
            $s->setRelation('faculty', $users[$s->faculty_id] ?? null);
            $room = $rooms[$s->room_id] ?? null;
            $s->setRelation('room', $room);
            ScheduleDisplaySupport::applyToModel($s, $room);
        });
        
        $stats = [
            'pending' => ClassSchedule::where('admin_approved', false)->where('status', 'pending')->count(),
            'approved' => ClassSchedule::where('admin_approved', true)->where('status', 'active')->count(),
            'rejected' => ClassSchedule::where('status', 'rejected')->count(),
            'total' => ClassSchedule::count(),
        ];

        return view('junior-high-admin.schedule-approval.index', [
            'schedules' => $schedules,
            'stats' => $stats,
        ]);
    }

    /**
     * Approve a schedule
     */
    public function approve(Request $request, $schedule)
    {
        $schedule = ClassSchedule::findOrFail($schedule);
        try {
            \Illuminate\Support\Facades\DB::connection((new ClassSchedule)->getConnectionName())
                ->statement('SET @audit_user = ?', [Auth::user()->name ?? 'system']);
            $schedule->update([
                'status'                 => 'active',
                'admin_approved'         => true,
                'approved_at'            => now(),
                'approved_by'            => Auth::id(),
                'last_modified_by_admin' => now(),
            ]);

            // Log the change
            $changeLog = $schedule->change_log ? json_decode($schedule->change_log, true) : [];
            $changeLog[] = [
                'action' => 'approved',
                'by' => Auth::user()->name,
                'at' => now()->toDateTimeString()
            ];
            $schedule->update(['change_log' => json_encode($changeLog)]);

            ScheduleApproval::updateOrCreate(
                ['schedule_id' => $schedule->id],
                ['submitted_by' => $schedule->faculty_id ?? 0, 'status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Schedule approved successfully', 'data' => $schedule]);
            }

            return redirect()->back()->with('success', 'Schedule approved successfully!');
        } catch (\Exception $e) {
            \Log::error('Approve schedule error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error approving schedule: ' . $e->getMessage()], 400);
            }

            return redirect()->back()->with('error', 'Error approving schedule');
        }
    }

    /**
     * Reject a schedule
     */
    public function reject(Request $request, $schedule)
    {
        $schedule = ClassSchedule::findOrFail($schedule);
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);
            \Illuminate\Support\Facades\DB::connection((new ClassSchedule)->getConnectionName())
                ->statement('SET @audit_user = ?', [Auth::user()->name ?? 'system']);
            $schedule->update([
                'admin_approved' => false,
                'status' => 'rejected',
                'last_modified_by_admin' => now(),
            ]);

            // Log the rejection
            $changeLog = $schedule->change_log ? json_decode($schedule->change_log, true) : [];
            $changeLog[] = [
                'action' => 'rejected',
                'reason' => $validated['reason'] ?? 'No reason provided',
                'by' => Auth::user()->name,
                'at' => now()->toDateTimeString()
            ];
            $schedule->update(['change_log' => json_encode($changeLog)]);

            ScheduleApproval::updateOrCreate(
                ['schedule_id' => $schedule->id],
                ['submitted_by' => $schedule->faculty_id ?? 0, 'status' => 'rejected', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'admin_notes' => $validated['reason'] ?? null]
            );
            // Write name + reason directly (triggers can't look up user names)
            $dbConn = (new ClassSchedule)->getConnectionName();
            \Illuminate\Support\Facades\DB::connection($dbConn)->table('rejected_schedules')->updateOrInsert(
                ['schedule_id' => $schedule->id],
                [
                    'faculty_id'       => $schedule->faculty_id,
                    'subject'          => $schedule->subject,
                    'grade_level'      => $schedule->grade_level,
                    'section_name'     => $schedule->section_name,
                    'room_id'          => $schedule->room_id,
                    'day_of_week'      => $schedule->day_of_week,
                    'schedule_date'    => $schedule->schedule_date,
                    'start_time'       => $schedule->start_time,
                    'end_time'         => $schedule->end_time,
                    'student_count'    => $schedule->student_count,
                    'rejection_reason' => $validated['reason'] ?? null,
                    'rejected_by'      => Auth::id(),
                    'rejected_by_name' => Auth::user()->name,
                    'rejected_at'      => now(),
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Schedule rejected successfully']);
            }

            return redirect()->back()->with('success', 'Schedule rejected successfully!');
        } catch (\Exception $e) {
            \Log::error('Reject schedule error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error rejecting schedule: ' . $e->getMessage()], 400);
            }

            return redirect()->back()->with('error', 'Error rejecting schedule');
        }
    }

}
