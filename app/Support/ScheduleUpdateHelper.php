<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleUpdateHelper
{
    public static function mergeNormalizedInput(Request $request): void
    {
        $merge = [];

        foreach (['start_time', 'end_time'] as $field) {
            $value = $request->input($field);
            if ($value === null || $value === '') {
                continue;
            }
            $value = (string) $value;
            if (preg_match('/^\d{1,2}:\d{2}/', $value)) {
                $parts = explode(':', $value);
                $merge[$field] = sprintf('%02d:%02d', (int) $parts[0], (int) ($parts[1] ?? 0));
            }
        }

        if ($request->has('faculty_id')) {
            $facultyId = $request->input('faculty_id');
            $merge['faculty_id'] = ($facultyId === '' || $facultyId === null) ? null : (int) $facultyId;
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }
    }

    /**
     * Returns an error message when schedule_date does not fall on day_of_week.
     */
    public static function dayDateMismatchMessage(?string $dayOfWeek, ?string $scheduleDate): ?string
    {
        $day = trim((string) $dayOfWeek);
        $date = trim((string) $scheduleDate);
        if ($day === '' || $date === '') {
            return null;
        }

        try {
            $actual = Carbon::parse($date)->format('l');
        } catch (\Throwable) {
            return 'Schedule date is invalid.';
        }

        if ($actual !== $day) {
            return 'Schedule date must fall on ' . $day . ' (the selected date is a ' . $actual . ').';
        }

        return null;
    }

    public static function validationRules(): array
    {
        return [
            'faculty_id'    => 'nullable|integer|min:1',
            'subject'       => 'nullable|string|max:255',
            'grade_level'   => 'nullable|string|max:50',
            'section_name'  => 'nullable|string|max:100',
            'schedule_date' => 'nullable|date',
            'start_time'    => ['nullable', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'end_time'      => ['nullable', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'day_of_week'   => 'nullable|string|max:20',
            'status'        => 'nullable|in:pending,active,completed,approved',
        ];
    }
}
