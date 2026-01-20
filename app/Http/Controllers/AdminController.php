namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller {
    /**
     * Get all schedules with relationships
     */
    public function getSchedules() {
        $schedules = ClassSchedule::with(['faculty', 'room', 'approver'])->get();
        return response()->json(['data' => $schedules]);
    }

    /**
     * Approve a schedule
     */
    public function approveSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $schedule->update([
                'admin_approved' => true,
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'last_modified_by_admin' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Schedule approved successfully', 'data' => $schedule]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Approve schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error approving schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Reject and delete a schedule
     */
    public function rejectSchedule(Request $request, $id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            // Log the rejection reason if provided
            $changeLog = json_decode($schedule->change_log, true) ?? [];
            $changeLog[] = [
                'action' => 'rejected',
                'reason' => $request->input('reason', 'No reason provided'),
                'by' => Auth::user()->name,
                'at' => now()->toDateTimeString()
            ];
            $schedule->update(['change_log' => json_encode($changeLog)]);
            // Delete the schedule
            $schedule->delete();
            return response()->json(['success' => true, 'message' => 'Schedule rejected and removed']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Reject schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error rejecting schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Update schedule details
     */
    public function updateSchedule(Request $request, $id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            
            $validated = $request->validate([
                'subject' => 'nullable|string|max:255',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'day_of_week' => 'nullable|string',
                'room_id' => 'nullable|exists:rooms,id',
                'student_count' => 'nullable|integer|min:1',
            ]);

            $schedule->update($validated);
            $schedule->load(['faculty', 'room']);
            
            return response()->json(['success' => true, 'message' => 'Schedule updated successfully', 'data' => $schedule]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Update schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete a schedule
     */
    public function deleteSchedule($id) {
        try {
            $schedule = ClassSchedule::findOrFail($id);
            $schedule->delete();
            return response()->json(['success' => true, 'message' => 'Schedule deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Delete schedule error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting schedule: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Add a new teacher/faculty
     */
    public function addTeacher(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users',
                'position' => 'nullable|string|max:255',
                'school_level' => 'nullable|string|max:255',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'position' => $validated['position'] ?? null,
                'school_level' => $validated['school_level'] ?? null,
                'password' => bcrypt('password'),
                'role_id' => 2, // Assuming 2 is teacher role
                'is_active' => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Teacher added successfully', 'data' => $user], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Add teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error adding teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Get all teachers
     */
    public function getTeachers() {
        try {
            $teachers = User::where('role_id', 2)->get(); // 2 = faculty/teacher role
            return response()->json(['success' => true, 'data' => $teachers]);
        } catch (\Exception $e) {
            \Log::error('Get teachers error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching teachers: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Update teacher information
     */
    public function updateTeacher(Request $request, $id) {
        try {
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:100|unique:users,email,' . $id,
                'department' => 'nullable|string|max:100',
            ]);

            $user = User::findOrFail($id);
            $user->update($validated);
            return response()->json(['success' => true, 'message' => 'Teacher updated successfully', 'data' => $user]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Update teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Delete teacher
     */
    public function deleteTeacher($id) {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['success' => true, 'message' => 'Teacher deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Delete teacher error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting teacher: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Add a new room
     */
    public function addRoom(Request $request) {
        try {
            $validated = $request->validate([
                'room_number' => 'required|string|max:50|unique:rooms',
                'building' => 'required|string|max:100',
                'capacity' => 'required|integer|min:1|max:200',
                'features' => 'nullable|string|max:255',
                'has_laboratory' => 'boolean',
                'has_projector' => 'boolean',
                'has_ac' => 'boolean',
                'status' => 'required|in:available,unavailable,maintenance',
            ]);

            $room = Room::create($validated);
            return response()->json(['success' => true, 'message' => 'Room added successfully', 'data' => $room], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Add room error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error adding room: ' . $e->getMessage()], 400);
        }
    }
}