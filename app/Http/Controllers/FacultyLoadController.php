<?php

namespace App\Http\Controllers;

use App\Models\FacultyDesignation;
use App\Models\FacultyLoad;
use App\Models\LoadConflictLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\FacultyLoadStats;
use App\Support\FacultyLoadSupport;

class FacultyLoadController extends Controller
{
    /**
     * Display all faculty loads
     */
    public function index(Request $request)
    {
        $query = FacultyLoad::orderBy('created_at', 'desc');
        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', (int) $request->faculty_id);
        }
        $facultyLoads = $query->get();

        // Manually load users from default connection to avoid cross-connection eager loading
        $userIds = $facultyLoads->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();

        // Collect shared-teacher user IDs for badge rendering
        $sharedTeacherIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
            ->pluck('id')->map(fn($id) => (string) $id)->flip()->toArray();

        // Pre-fetch approved schedules for all faculty IDs to compute live stats
        $allFacultyIds = $facultyLoads->pluck('faculty_id')->filter()->unique()->values()->toArray();
        $approvedScheds = \App\Models\ClassSchedule::on('mysql_jh')->whereIn('faculty_id', $allFacultyIds)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->get(['faculty_id', 'subject', 'section_name', 'day_of_week', 'start_time', 'end_time'])
            ->groupBy('faculty_id');

        $result = $facultyLoads->map(function (FacultyLoad $load) use ($users, $sharedTeacherIds, $approvedScheds) {
            $data = $load->toArray();
            $user = $users[$load->faculty_id] ?? null;
            $data['faculty'] = $user
                ? ['id' => $load->faculty_id, 'name' => $user->name]
                : null;
            // Store denormalised name so the view always has it even without join
            if (empty($data['teacher_name']) && $user) {
                $data['teacher_name'] = trim($user->first_name . ' ' . $user->last_name) ?: $user->name;
            }
            $data['is_shared_teacher'] = isset($sharedTeacherIds[(string) $load->faculty_id]);
            $presence = $load->faculty_id
                ? \App\Support\TeacherPresenceSupport::activeStatusForTeacherWithDays('mysql_jh', (int) $load->faculty_id)
                : null;

            if ($load->faculty_id) {
                $allScheds = collect($approvedScheds->get($load->faculty_id, []));
                $sharedCount = FacultyLoadSupport::countLoadsForTeacher((int) $load->faculty_id);
                $data['shared_load_count'] = $sharedCount;
                $data['shared_load_conflict'] = ($data['is_shared_teacher'] ?? false)
                    && $sharedCount > FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS;

                $data = \App\Support\FacultyAvailabilitySupport::enrichFacultyLoadRow(
                    $data,
                    'mysql_jh',
                    (int) $load->faculty_id,
                    $allScheds,
                    $presence,
                    (bool) ($data['is_shared_teacher'] ?? false),
                    $sharedCount
                );
            }

            $data['load_hours_label'] = FacultyLoadSupport::formatHoursLabel($data['load_hours'] ?? 0);
            $data['has_user_account'] = FacultyLoadSupport::facultyIdHasRegisteredAccount(
                (int) ($load->faculty_id ?? 0),
                'junior_high'
            );

            return $data;
        })->filter(fn ($row) => ($row['has_user_account'] ?? false)
            && ! FacultyLoadSupport::isAutoProvisionedPlaceholder($row))->values();

        // Check if this is an API request
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $result]);
        }

        return redirect()->route('admin.faculty-loading');
    }

    /**
     * Store a new faculty load
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'department' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
            'classes_assigned' => 'nullable|integer|min:0',
            'load_hours' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,available,unavailable,not_available',
            'notes' => 'nullable|string|max:1000',
        ]);

        $teacher = User::find($validated['faculty_id']);
        $teacherName = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : null;
        $dupMsg = \App\Support\FacultyLoadSupport::facultyLoadConflictMessage(
            (int) $validated['faculty_id'],
            $teacherName,
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null
        );
        if ($dupMsg !== null) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $dupMsg], 409);
            }
            return redirect()->route('admin.faculty-loading')->with('error', $dupMsg);
        }

        try {
            FacultyLoadSupport::assertFacultyLoadAccount((int) $validated['faculty_id'], 'junior_high');
            FacultyLoadSupport::assertSharedTeacherLoadLimit((int) $validated['faculty_id']);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->route('admin.faculty-loading')->with('error', $e->getMessage());
        }

        $teacher = User::find($validated['faculty_id']);
        $validated['teacher_name'] = $teacher ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name) : null;

        if (($validated['status'] ?? null) === 'unavailable') {
            $validated['status'] = 'not_available';
        }
        $validated['load_hours'] = FacultyLoadStats::computeLoadHours(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null
        );
        $validated['classes_assigned'] = FacultyLoadStats::countOngoingClasses(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null
        );
        $validated['status'] = FacultyLoadStats::resolveStatus((int) $validated['faculty_id']);

        $load = FacultyLoad::create($validated);
        FacultyLoadSupport::refreshTeacherLoadingScheduleRow($load);
        FacultyLoadSupport::applySharedTeacherLoadConflict((int) $load->faculty_id, $load->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Faculty load added successfully.']);
        }

        return redirect()->route('admin.faculty-loading')->with('success', 'Faculty load added successfully.');
    }

    /**
     * Show edit form or get faculty load details (JSON API)
     */
    public function show(Request $request, $id)
    {
        $facultyLoad = FacultyLoad::on('mysql_jh')->findOrFail($id);

        if ($request->wantsJson()) {
            $facultyLoad->setRelation('faculty', $facultyLoad->faculty_id ? User::find($facultyLoad->faculty_id) : null);
            return response()->json($facultyLoad);
        }

        $teachers = User::where('school_level', 'junior_high')
            ->whereHas('role', function($q) { $q->where('name', 'like', '%teacher%'); })->get();
        return redirect()->route('admin.faculty-loading.edit', $id);
    }

    /**
     * Update the specified faculty load in storage (JSON API).
     */
    public function update(Request $request, $id)
    {
        try {
            $facultyLoad = FacultyLoad::findOrFail($id);
            $oldGrade = $facultyLoad->grade_level;
            $oldSubject = $facultyLoad->subject;

            $validated = $request->validate([
                'faculty_id'       => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::with('role')->find($value);
                        if (!$user || $user->school_level !== 'junior_high') {
                            $fail('The selected teacher must have a Junior High user account in User Accounts.');
                            return;
                        }
                        if (!$user->role || stripos($user->role->name, 'teacher') === false) {
                            $fail('The selected faculty member must be a registered teacher or instructor.');
                        }
                    },
                ],
                'subject'          => 'nullable|string|max:255',
                'grade_level'      => 'nullable|string|max:50',
                'classes_assigned' => 'nullable|integer|min:0',
                'load_hours'       => 'nullable|numeric|min:0',
                'status'           => 'nullable|in:available,unavailable,not_available,overloaded,active,inactive,part-time',
                'notes'            => 'nullable|string|max:1000',
            ]);

            $teacher = User::find($validated['faculty_id']);
            $validated['teacher_name'] = $teacher
                ? (trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name)
                : null;

            if (($validated['status'] ?? null) === 'unavailable') {
                $validated['status'] = 'not_available';
            }

            try {
                FacultyLoadSupport::assertFacultyLoadAccount((int) $validated['faculty_id'], 'junior_high');
                if ((int) $validated['faculty_id'] !== (int) $facultyLoad->faculty_id) {
                    FacultyLoadSupport::assertSharedTeacherLoadLimit((int) $validated['faculty_id']);
                }
            } catch (\InvalidArgumentException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            $validated['load_hours'] = FacultyLoadStats::computeLoadHours(
                (int) $validated['faculty_id'],
                $validated['grade_level'] ?? null,
                $validated['subject'] ?? null
            );
            $validated['classes_assigned'] = FacultyLoadStats::countOngoingClasses(
                (int) $validated['faculty_id'],
                $validated['grade_level'] ?? null
            );
            $validated['status'] = FacultyLoadStats::resolveStatus((int) $validated['faculty_id']);

            $facultyLoad->update($validated);
            $facultyLoad->refresh();

            FacultyLoadSupport::syncSchedulesAfterLoadChange(
                (int) $facultyLoad->faculty_id,
                $oldGrade,
                $oldSubject,
                $facultyLoad->grade_level,
                $facultyLoad->subject
            );
            FacultyLoadSupport::refreshTeacherLoadingScheduleRow($facultyLoad, $oldSubject);
            FacultyLoadSupport::applySharedTeacherLoadConflict((int) $facultyLoad->faculty_id, $facultyLoad->id);

            $facultyLoad->setRelation('faculty', $teacher);

            return response()->json([
                'success' => true,
                'message' => 'Faculty load updated successfully.',
                'data'    => $facultyLoad,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => 'Validation failed'], 422);
        } catch (\Exception $e) {
            Log::error('Update faculty load error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error updating faculty load: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a faculty load (JSON API version)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $facultyLoad = FacultyLoad::query()->findOrFail($id);
            $removed = FacultyLoadSupport::cascadeDeleteForFacultyLoad($facultyLoad);
            $facultyLoad->delete();

            $detail = sprintf(
                'Removed %d schedule(s), %d pending record(s), and cleared %d weekly timetable row(s).',
                $removed['schedules'],
                $removed['pending'] ?? 0,
                $removed['weekly']
            );

            // Return JSON for API requests
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Faculty load deleted successfully. ' . $detail,
                    'removed' => $removed,
                ], 200);
            }

            return redirect()->route('admin.faculty-loading')
                ->with('success', 'Faculty load deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Delete faculty load error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting faculty load: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.faculty-loading')
                ->with('error', 'Error deleting faculty load!');
        }
    }

    /**
     * Get all faculty loads as JSON (for API)
     */
    public function getFacultyLoadsApi()
    {
        $loads = FacultyLoad::get();
        $userIds = $loads->pluck('faculty_id')->filter()->unique();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $loads->each(fn($l) => $l->setRelation('faculty', $users[$l->faculty_id] ?? null));
        return response()->json(['data' => $loads]);
    }

    private function resolveFacultyLoadStatus(float $loadHours, string $requestedStatus): string
    {
        if ($loadHours > 6) {
            return 'overloaded';
        }

        return $requestedStatus === 'overload' ? 'overloaded' : $requestedStatus;
    }

    /**
     * Returns current availability status based on approved active schedule at current day/time.
     */
    private function computeAvailabilityStatus(int $facultyId): string
    {
        if ($facultyId <= 0) {
            return 'available';
        }

        $currentTime = now()->format('H:i');
        $currentDay = now()->format('l');

        $activeSchedule = \App\Models\ClassSchedule::where('faculty_id', $facultyId)
            ->where('day_of_week', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->exists();

        return $activeSchedule ? 'not_available' : 'available';
    }

    /**
     * Check whether the newly added load pushes the faculty member over their
     * designation limit, and write conflict log if exceeded.
     */
    private function checkDesignationLimit(
        int $facultyId,
        int $newClassesAssigned,
        float $newLoadHours,
        int $loadId
    ): void {
        try {
            $schoolYear = date('Y') . '-' . (date('Y') + 1);

            $designation = FacultyDesignation::where('faculty_id', $facultyId)
                ->where('school_year', $schoolYear)
                ->first();

            if (!$designation) {
                return;
            }

            // Sum ALL loads for this teacher (including the one just created)
            $totalClasses = FacultyLoad::where('faculty_id', $facultyId)->sum('classes_assigned');
            $totalHours   = FacultyLoad::where('faculty_id', $facultyId)->sum('load_hours');

            $classesExceeded = $totalClasses > $designation->max_classes;
            $hoursExceeded   = (float) $totalHours > (float) $designation->max_load_hours;

            if (!$classesExceeded && !$hoursExceeded) {
                return;
            }

            $type        = $classesExceeded ? 'designation_exceeded' : 'overload';
            $severity    = $classesExceeded ? 'critical' : 'warning';
            $description = $classesExceeded
                ? "Teacher has {$totalClasses} classes but {$designation->designation_type} limit is {$designation->max_classes}."
                : "Teacher has {$totalHours} load hours but {$designation->designation_type} limit is {$designation->max_load_hours}h.";

            LoadConflictLog::create([
                'faculty_id'       => $facultyId,
                'conflict_type'    => $type,
                'description'      => $description,
                'severity'         => $severity,
                'related_load_id'  => $loadId,
                'detected_at'      => now(),
                'status'           => 'open',
            ]);
        } catch (\Exception $e) {
            // Non-blocking: log the error but do not interrupt the load creation
            Log::error('checkDesignationLimit failed: ' . $e->getMessage());
        }
    }
}
