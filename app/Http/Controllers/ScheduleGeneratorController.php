<?php

namespace App\Http\Controllers;

use App\Models\FacultyLoad;
use App\Models\Room;
use App\Models\User;
use App\Services\ScheduleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ScheduleGeneratorController extends Controller
{
    /**
     * Show the generator configuration form.
     * Works for both JH admin and GS admin routes.
     */
    public function show(Request $request)
    {
        [$layout, $schoolLevel, $conn, $isGs, $gradesRange] = $this->resolveContext($request);

        config(['database.school_connection' => $conn]);

        $suggestedSections = $this->buildSuggestedSections($gradesRange);

        $teacherCount = User::whereHas('role', fn($q) => $q->where('name', 'like', '%teacher%'))
            ->where('school_level', $schoolLevel)->count();

        $loadCount  = FacultyLoad::where('status', '!=', 'inactive')->count();
        $roomCount  = Room::where('status', 'available')->count();
        $schoolYear = date('Y') . '-' . (date('Y') + 1);

        config(['database.school_connection' => null]);

        return view('schedule-generator.index', compact(
            'layout', 'schoolLevel', 'suggestedSections',
            'teacherCount', 'loadCount', 'roomCount', 'schoolYear', 'isGs'
        ));
    }

    /**
     * Run the generator (dry-run — no DB writes) and show the preview.
     */
    public function preview(Request $request)
    {
        [$layout, $schoolLevel, $conn, $isGs] = $this->resolveContext($request);

        $validated = $request->validate([
            'school_year' => 'required|string|max:20',
            'days'        => 'required|array|min:1',
            'days.*'      => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'sections'    => 'nullable|string|max:500',
            'max_per_day' => 'required|integer|min:1|max:6',
            'start_hour'  => 'required|date_format:H:i',
            'end_hour'    => 'required|date_format:H:i|after:start_hour',
        ]);

        config(['database.school_connection' => $conn]);

        $slots    = $this->buildSlots($validated['start_hour'], $validated['end_hour']);
        $sections = array_values(array_filter(
            array_map('trim', explode(',', $validated['sections'] ?? ''))
        ));

        $generator = new ScheduleGenerator([
            'school_year'  => $validated['school_year'],
            'days'         => $validated['days'],
            'slots'        => $slots,
            'max_per_day'  => (int) $validated['max_per_day'],
            'sections'     => $sections,
            'school_level' => $schoolLevel,
        ]);

        try {
            $result = $generator->generate();
        } catch (\Exception $e) {
            Log::error('ScheduleGenerator::generate() failed: ' . $e->getMessage(), [
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            config(['database.school_connection' => null]);
            return back()->withErrors(['error' => 'Generator encountered an error: ' . $e->getMessage()]);
        }

        config(['database.school_connection' => null]);

        // Store in session so confirm() can persist without re-running
        Session::put('sg_result', $result);
        Session::put('sg_conn',   $conn);
        Session::put('sg_is_gs',  $isGs);

        return view('schedule-generator.preview', array_merge($result, [
            'layout'      => $layout,
            'school_year' => $validated['school_year'],
            'isGs'        => $isGs,
        ]));
    }

    /**
     * Persist the proposed schedules stored in the session.
     */
    public function confirm(Request $request)
    {
        $result = Session::get('sg_result');
        $conn   = Session::get('sg_conn');
        $isGs   = Session::get('sg_is_gs', false);

        if (!$result || empty($result['proposed'])) {
            return back()->withErrors(['error' => 'Session expired — please re-run the generator.']);
        }

        $proposed = $result['proposed'];
        if ($request->boolean('import_only_clean') && ! empty($result['conflicts'])) {
            $badIndices = [];
            foreach ($result['conflicts'] as $c) {
                foreach ($c['indices'] ?? [] as $idx) {
                    $badIndices[(int) $idx] = true;
                }
            }
            $proposed = array_values(array_filter(
                $proposed,
                fn ($entry, $idx) => ! isset($badIndices[$idx]),
                ARRAY_FILTER_USE_BOTH
            ));
            if (empty($proposed)) {
                return back()->withErrors(['error' => 'No conflict-free rows to import. Adjust generator settings or import all rows.']);
            }
        }

        config(['database.school_connection' => $conn]);

        try {
            $generator = new ScheduleGenerator();
            $count     = $generator->persist($proposed, $result['conflicts'] ?? [], Auth::id());
        } catch (\Exception $e) {
            Log::error('ScheduleGenerator::persist() failed: ' . $e->getMessage());
            config(['database.school_connection' => null]);
            return back()->withErrors(['error' => 'Failed to save schedules: ' . $e->getMessage()]);
        }

        Session::forget(['sg_result', 'sg_conn', 'sg_is_gs']);
        config(['database.school_connection' => null]);

        $route = $isGs ? 'grade-school-admin.class-schedule' : 'admin.class-schedule';
        return redirect()->route($route)
            ->with('success', "Auto-generated {$count} schedule(s) successfully! Pending schedules are ready for your review and approval.");
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve layout, school level, DB connection, and grade range from the route.
     */
    private function resolveContext(Request $request): array
    {
        $isGs        = $request->routeIs('grade-school-admin.*');
        $layout      = $isGs ? 'layouts.grade-school-admin' : 'layouts.admin';
        $schoolLevel = $isGs ? 'grade_school' : 'junior_high';
        $conn        = $isGs ? 'mysql_gs' : 'mysql_jh';
        $gradesRange = $isGs ? range(1, 6) : range(7, 10);

        return [$layout, $schoolLevel, $conn, $isGs, $gradesRange];
    }

    /**
     * Build suggested grade-section strings for the form helper text.
     */
    private function buildSuggestedSections(array $gradesRange): array
    {
        $list = [];
        foreach ($gradesRange as $grade) {
            foreach (['A', 'B', 'C'] as $sec) {
                $list[] = "Grade $grade-$sec";
            }
        }
        return $list;
    }

    /**
     * Build 1-hour time slots between start and end times.
     */
    private function buildSlots(string $startHour, string $endHour): array
    {
        $slots   = [];
        $current = strtotime("1970-01-01 $startHour:00");
        $end     = strtotime("1970-01-01 $endHour:00");

        while ($current + 3600 <= $end) {
            $slots[] = [
                'start' => date('H:i', $current),
                'end'   => date('H:i', $current + 3600),
            ];
            $current += 3600;
        }

        return $slots ?: ScheduleGenerator::DEFAULT_SLOTS;
    }
}
