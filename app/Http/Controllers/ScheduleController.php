<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Get all schedules (for admin)
     */
    public function index()
    {
        $schedules = ClassSchedule::with(['faculty', 'room', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * Get schedules for a specific teacher
     */
    public function getTeacherSchedules($facultyId)
    {
        $schedules = ClassSchedule::with(['faculty', 'room'])
            ->where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->orderBy('day_of_week')
            ->get();
        
        return response()->json($schedules);
    }

    /**
     * Get approved schedules only
     */
    public function getApprovedSchedules()
    {
        $schedules = ClassSchedule::with(['faculty', 'room'])
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->orderBy('day_of_week')
            ->get();
        
        return response()->json($schedules);
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request)
    {
        // Check if faculty_id is provided, if not use authenticated user
        $facultyId = $request->input('faculty_id') ?? Auth::id();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'grade_section' => 'required|string|max:100',
            'room_id' => 'nullable|exists:rooms,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'student_count' => 'nullable|integer|min:1|max:100',
        ]);

        $validated['faculty_id'] = $facultyId;
        
        // All schedules start as pending and require approval
        $validated['status'] = 'pending';
        $validated['admin_approved'] = false;
        
        $validated['version'] = 1;

        $schedule = ClassSchedule::create($validated);

        // Check if this is a form submission or API request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Schedule created successfully.',
                'schedule' => $schedule->load(['faculty', 'room']),
            ], 201);
        }

        // Redirect for form submission
        return redirect()->route('admin.class-schedule')
            ->with('success', 'Schedule created successfully! Review it in the Pending Schedules section to approve or reject.');
    }

    /**
     * Get a single schedule for editing
     */
    public function show(ClassSchedule $schedule)
    {
        return response()->json($schedule->load(['faculty', 'room', 'approver']));
    }

    /**
     * Approve a schedule (admin action)
     */
    public function approve(Request $request, ClassSchedule $schedule)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schedule->update([
            'admin_approved' => true,
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'change_log' => $schedule->change_log ? $schedule->change_log . "\n\n[" . now() . "] Approved by " . Auth::user()->name : "[" . now() . "] Approved by " . Auth::user()->name,
        ]);

        return response()->json([
            'message' => 'Schedule approved successfully.',
            'schedule' => $schedule->load(['faculty', 'room', 'approver']),
        ]);
    }

    /**
     * Reject/Disapprove a schedule (admin action)
     */
    public function reject(Request $request, ClassSchedule $schedule)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $schedule->update([
            'admin_approved' => false,
            'status' => 'rejected',
            'change_log' => $schedule->change_log ? $schedule->change_log . "\n\n[" . now() . "] Rejected by " . Auth::user()->name . ": " . $validated['reason'] : "[" . now() . "] Rejected by " . Auth::user()->name . ": " . $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Schedule rejected.',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Update a schedule (admin can edit approved schedules)
     */
    public function update(Request $request, ClassSchedule $schedule)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'grade_section' => 'sometimes|string|max:100',
            'room_id' => 'sometimes|nullable|exists:rooms,id',
            'day_of_week' => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'student_count' => 'sometimes|nullable|integer|min:1',
            'status' => 'sometimes|in:pending,active,completed',
        ]);

        // Store original data for change log
        $originalData = $schedule->only(array_keys($validated));
        
        // Increment version
        $validated['version'] = $schedule->version + 1;
        $validated['last_modified_by_admin'] = now();

        // Create change log entry
        $changeEntry = "[" . now() . "] Updated by Admin " . Auth::user()->name . ":\n";
        foreach ($validated as $field => $value) {
            if (isset($originalData[$field]) && $originalData[$field] !== $value) {
                $changeEntry .= "  • $field: {$originalData[$field]} → $value\n";
            }
        }

        $validated['change_log'] = $schedule->change_log ? $schedule->change_log . "\n\n" . $changeEntry : $changeEntry;

        $schedule->update($validated);

        return response()->json([
            'message' => 'Schedule updated successfully.',
            'schedule' => $schedule->load(['faculty', 'room', 'approver']),
        ]);
    }

    /**
     * Delete a schedule (admin action)
     */
    public function destroy(Request $request, ClassSchedule $schedule)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Instead of deleting, mark as deleted and log the action
        $schedule->update([
            'status' => 'deleted',
            'admin_approved' => false,
            'change_log' => $schedule->change_log ? $schedule->change_log . "\n\n[" . now() . "] Deleted by " . Auth::user()->name . ": " . $validated['reason'] : "[" . now() . "] Deleted by " . Auth::user()->name . ": " . $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Schedule deleted successfully.',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Get change history for a schedule
     */
    public function getHistory(ClassSchedule $schedule)
    {
        return response()->json([
            'schedule_id' => $schedule->id,
            'version' => $schedule->version,
            'created_at' => $schedule->created_at,
            'created_by' => $schedule->faculty->name,
            'approved_at' => $schedule->approved_at,
            'approved_by' => $schedule->approver?->name,
            'last_modified_at' => $schedule->last_modified_by_admin,
            'change_log' => $schedule->change_log,
        ]);
    }

    /**
     * Get pending schedules for admin review
     */
    public function getPendingSchedules()
    {
        $schedules = ClassSchedule::with(['faculty', 'room'])
            ->where('admin_approved', false)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => $schedules
        ]);
    }
}
