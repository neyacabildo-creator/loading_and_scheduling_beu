<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\FacultyLoadSupport;

class FacultyLoadingController extends Controller
{
    /**
     * Display faculty loading page with statistics
     */
    public function index()
    {
        // Get total faculty (teachers) – Junior High only
        $totalFaculty = User::where('school_level', 'junior_high')
            ->whereHas('role', function($q) { 
                $q->where('name', 'like', '%teacher%'); 
            })->count();
        
        // Get total classes
        $totalClasses = FacultyLoad::sum('classes_assigned') ?? 0;
        
        // Calculate average load (total load hours / total faculty)
        $totalLoadHours = FacultyLoad::sum('load_hours') ?? 0;
        $avgLoad = $totalFaculty > 0 ? round($totalLoadHours / $totalFaculty, 2) : 0;
        
        // Count teachers who have any day with >5 approved subjects (daily limit = 5)
        $overloaded = ClassSchedule::selectRaw('faculty_id')
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->whereNotNull('faculty_id')
            ->groupBy('faculty_id', 'day_of_week')
            ->havingRaw('COUNT(*) > 5')
            ->get()
            ->pluck('faculty_id')
            ->unique()
            ->count();
        
        // Get all faculty loads for the table (explicit loading avoids cross-connection eager load)
        $facultyLoads = FacultyLoad::paginate(5);
        $userIds = $facultyLoads->pluck('faculty_id')->filter()->unique();
        if ($userIds->isNotEmpty()) {
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            $facultyLoads->each(function ($fl) use ($users) {
                $fl->setRelation('faculty', $users[$fl->faculty_id] ?? null);
            });
        }

        // Teachers for add/edit modal dropdowns
        $teachers = User::where('school_level', 'junior_high')
            ->whereHas('role', function ($q) {
                $q->where('name', 'like', '%teacher%');
            })->orderBy('first_name')->get();

        // Shared teacher user IDs for badge rendering in the view
        $sharedTeacherUserIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
            ->pluck('id')->map(fn($id) => (string) $id)->toArray();

        // Subjects: from class_schedules + sensible defaults
        $defaultSubjects = ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Technology and Livelihood Education','Edukasyon sa Pagpapakatao','Values Education'];
        $dbSubjects = ClassSchedule::distinct()->pluck('subject')->filter()->values()->toArray();
        $subjects   = collect(array_unique(array_merge($dbSubjects, $defaultSubjects)))->sort()->values()->toArray();

        $sharedTeacherIds = DB::connection('mysql_jh')->table('shared_teachers')
            ->where('is_active', true)->pluck('faculty_id')->map(fn ($id) => (int) $id)->all();
        $leaveBanner = \App\Support\TeacherPresenceSupport::collectActiveLeaveBannerData('mysql_jh', $sharedTeacherIds);

        return view('junior-high-admin.faculty-loading', [
            'leaveBanner' => $leaveBanner,
            'totalFaculty' => $totalFaculty,
            'totalClasses' => $totalClasses,
            'avgLoad' => $avgLoad,
            'overloaded' => $overloaded,
            'facultyLoads' => $facultyLoads,
            'teachers'    => $teachers,
            'subjects'    => $subjects,
            'sharedTeacherUserIds' => $sharedTeacherUserIds,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $teachers = User::where('school_level', 'junior_high')
            ->whereHas('role', function($q) { 
                $q->where('name', 'like', '%teacher%'); 
            })->get();
        $sharedTeacherUserIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
            ->pluck('id')->map(fn($id) => (string) $id)->toArray();

        return view('junior-high-admin.faculty-loading-form', [
            'teachers' => $teachers,
            'facultyLoad' => null,
            'sharedTeacherUserIds' => $sharedTeacherUserIds,
        ]);
    }

    /**
     * Store new faculty load
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::with('role')->find($value);
                    if (!$user || !$user->role || stripos($user->role->name, 'teacher') === false) {
                        $fail('The selected faculty member must be a registered teacher or instructor.');
                    }
                },
            ],
            'subject' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
            'classes_assigned' => 'nullable|integer|min:0',
            'load_hours' => 'required|numeric|min:0',
            'status' => 'required|in:available,unavailable,not_available',
        ]);

        $teacher = User::find($validated['faculty_id']);
        $validated['teacher_name'] = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : null;

        $dupMsg = FacultyLoadSupport::facultyLoadConflictMessage(
            (int) $validated['faculty_id'],
            $validated['teacher_name'] ?? null,
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null
        );
        if ($dupMsg !== null) {
            return redirect()->back()->withInput()->with('error', $dupMsg);
        }

        try {
            FacultyLoadSupport::assertSharedTeacherLoadLimit((int) $validated['faculty_id']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if (($validated['status'] ?? null) === 'unavailable') {
            $validated['status'] = 'not_available';
        }
        $validated['load_hours'] = $this->computeLoadHoursFromSchedules(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null
        );
        $validated['classes_assigned'] = \App\Support\FacultyLoadStats::countOngoingClasses(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null
        );
        $validated['status'] = $this->computeAvailabilityStatus((int) $validated['faculty_id']);

        $load = FacultyLoad::create($validated);
        FacultyLoadSupport::refreshTeacherLoadingScheduleRow($load);
        FacultyLoadSupport::applySharedTeacherLoadConflict((int) $load->faculty_id, $load->id);

        return redirect()->route('admin.faculty-loading')->with('success', 'Faculty load created successfully');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $facultyLoad = FacultyLoad::findOrFail($id);
        if ($facultyLoad->faculty_id) {
            $facultyLoad->setRelation('faculty', User::find($facultyLoad->faculty_id));
        }
        $teachers = User::where('school_level', 'junior_high')
            ->whereHas('role', function($q) { 
                $q->where('name', 'like', '%teacher%'); 
            })->get();
        $sharedTeacherUserIds = User::whereHas('role', fn($q) => $q->where('name', 'shared_teacher'))
            ->pluck('id')->map(fn($id) => (string) $id)->toArray();

        return view('junior-high-admin.faculty-loading-form', [
            'teachers' => $teachers,
            'facultyLoad' => $facultyLoad,
            'sharedTeacherUserIds' => $sharedTeacherUserIds,
        ]);
    }

    /**
     * Update faculty load
     */
    public function update(Request $request, $id)
    {
        $facultyLoad = FacultyLoad::findOrFail($id);
        $oldGrade = $facultyLoad->grade_level;
        $oldSubject = $facultyLoad->subject;

        $validated = $request->validate([
            'faculty_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::with('role')->find($value);
                    if (!$user || !$user->role || stripos($user->role->name, 'teacher') === false) {
                        $fail('The selected faculty member must be a registered teacher or instructor.');
                    }
                },
            ],
            'subject' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
            'classes_assigned' => 'nullable|integer|min:0',
            'load_hours' => 'required|numeric|min:0',
            'status' => 'required|in:available,unavailable,not_available',
        ]);

        $teacher = User::find($validated['faculty_id']);
        $validated['teacher_name'] = $teacher ? trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name : null;

        $dupMsg = FacultyLoadSupport::facultyLoadConflictMessage(
            (int) $validated['faculty_id'],
            $validated['teacher_name'] ?? null,
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null,
            (int) $facultyLoad->id
        );
        if ($dupMsg !== null) {
            return redirect()->back()->withInput()->with('error', $dupMsg);
        }

        if (($validated['status'] ?? null) === 'unavailable') {
            $validated['status'] = 'not_available';
        }
        $validated['load_hours'] = $this->computeLoadHoursFromSchedules(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null,
            $validated['subject'] ?? null
        );
        $validated['classes_assigned'] = \App\Support\FacultyLoadStats::countOngoingClasses(
            (int) $validated['faculty_id'],
            $validated['grade_level'] ?? null
        );
        $validated['status'] = $this->computeAvailabilityStatus((int) $validated['faculty_id']);

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

        return redirect()->route('admin.faculty-loading')->with('success', 'Faculty load updated successfully');
    }

    private function resolveFacultyLoadStatus(float $loadHours, string $requestedStatus): string
    {
        if ($loadHours > 6) {
            return 'overloaded';
        }

        return $requestedStatus === 'overload' ? 'overloaded' : $requestedStatus;
    }

    private function computeAvailabilityStatus(int $facultyId): string
    {
        if ($facultyId <= 0) {
            return 'available';
        }

        $currentTime = now()->format('H:i');
        $currentDay = now()->format('l');

        $activeSchedule = ClassSchedule::where('faculty_id', $facultyId)
            ->where('day_of_week', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->exists();

        return $activeSchedule ? 'not_available' : 'available';
    }

    private function computeLoadHoursFromSchedules(int $facultyId, ?string $gradeLevel = null, ?string $subjectsCsv = null): float
    {
        if ($facultyId <= 0) {
            return 0.0;
        }

        $query = ClassSchedule::where('faculty_id', $facultyId)
            ->where('admin_approved', true)
            ->where('status', 'active');

        if (!empty($gradeLevel)) {
            $query->where('grade_level', $gradeLevel);
        }

        $schedules = $query->get(['subject', 'start_time', 'end_time']);

        $selectedSubjects = collect(explode(',', (string) $subjectsCsv))
            ->map(fn($s) => strtolower(trim($s)))
            ->filter()
            ->values();

        $totalMins = 0;
        foreach ($schedules as $schedule) {
            $subject = strtolower(trim((string) $schedule->subject));
            if ($selectedSubjects->isNotEmpty() && !$selectedSubjects->contains($subject)) {
                continue;
            }

            $duration = $this->timeToMinutes($schedule->end_time) - $this->timeToMinutes($schedule->start_time);
            if ($duration > 0) {
                $totalMins += $duration;
            }
        }

        return round($totalMins / 60, 2);
    }

    private function timeToMinutes(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $parts = explode(':', $time);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }
}
