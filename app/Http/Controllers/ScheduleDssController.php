<?php

namespace App\Http\Controllers;

use App\Support\ScheduleDssSupport;
use Illuminate\Http\Request;

class ScheduleDssController extends Controller
{
    public function assessSlot(Request $request)
    {
        [$connection, $schoolLevel] = $this->resolveSchool($request);

        $validated = $request->validate([
            'day_of_week'    => 'nullable|string|max:20',
            'start_time'     => 'nullable|string|max:10',
            'end_time'       => 'nullable|string|max:10',
            'faculty_id'     => 'nullable|integer|min:1',
            'schedule_date'  => 'nullable|date',
            'exclude_schedule_id' => 'nullable|integer|min:1',
        ]);

        $result = ScheduleDssSupport::assessSlot(
            $connection,
            $schoolLevel,
            $validated['day_of_week'] ?? null,
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
            isset($validated['faculty_id']) ? (int) $validated['faculty_id'] : null,
            $validated['schedule_date'] ?? null,
            $validated['exclude_schedule_id'] ?? null,
        );

        return response()->json($result);
    }

    public function assessFacultyLoad(Request $request)
    {
        [$connection, $schoolLevel] = $this->resolveSchool($request);

        $validated = $request->validate([
            'faculty_id'           => 'required|integer|min:1',
            'load_hours'           => 'required|numeric|min:0',
            'exclude_load_id'      => 'nullable|integer|min:1',
        ]);

        config(['database.school_connection' => $connection]);

        $warnings = ScheduleDssSupport::assessFacultyLoadSave(
            $connection,
            $schoolLevel,
            (int) $validated['faculty_id'],
            (float) $validated['load_hours'],
            $validated['exclude_load_id'] ?? null,
        );

        config(['database.school_connection' => null]);

        return response()->json(['warnings' => $warnings]);
    }

    public function checkAdjustmentSlot(Request $request)
    {
        $validated = $request->validate([
            'day_of_week'         => 'required|string|max:20',
            'preferred_start_time'=> 'required|string|max:10',
            'preferred_end_time'  => 'required|string|max:10',
            'preferred_date'      => 'nullable|date',
            'faculty_id'          => 'nullable|integer|min:1',
        ]);

        if ($request->routeIs('grade-school-teacher.*') || str_contains($request->path(), 'grade-school-teacher')) {
            $connection = 'mysql_gs';
            $schoolLevel = 'grade_school';
            $facultyId = (int) ($validated['faculty_id'] ?? auth()->id());
        } else {
            $connection = 'mysql_jh';
            $schoolLevel = 'junior_high';
            $facultyId = (int) ($validated['faculty_id'] ?? auth()->id());
        }

        $result = ScheduleDssSupport::assessSlot(
            $connection,
            $schoolLevel,
            $validated['day_of_week'],
            $validated['preferred_start_time'],
            $validated['preferred_end_time'],
            $facultyId,
            $validated['preferred_date'] ?? null,
        );

        return response()->json([
            'valid_slot' => $result['valid_slot'],
            'conflicts'  => $result['conflicts'],
            'messages'   => $result['messages'],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveSchool(Request $request): array
    {
        if ($request->routeIs('grade-school-admin.*') || $request->input('school_level') === 'grade_school') {
            return ['mysql_gs', 'grade_school'];
        }

        return ['mysql_jh', 'junior_high'];
    }
}
