<?php

namespace App\Http\Controllers;

use App\Models\MasterWeeklySchedule;
use App\Models\User;
use App\Support\FacultyLoadSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the "Master Loading Schedule" weekly grid feature.
 *
 * Used by both JH admin (middleware: admin + school.db:mysql_jh)
 * and GS admin (middleware: IsGradeSchoolAdmin + school.db:mysql_gs).
 *
 * MasterWeeklySchedule uses the UseSchoolConnection trait, so it
 * automatically queries the correct school DB based on middleware context.
 */
class MasterWeeklyScheduleController extends Controller
{
    /**
     * Pre-defined time slots that form the rows of the weekly grid.
     * slot_order is 1-based positioning (used for stable upsert key).
     */
    public static function timeSlots(): array
    {
        return [
            ['order' => 1, 'label' => '7:45 - 8:45',   'start' => '07:45', 'end' => '08:45', 'type' => 'class'],
            ['order' => 2, 'label' => '8:45 - 9:45',   'start' => '08:45', 'end' => '09:45', 'type' => 'class'],
            ['order' => 3, 'label' => '9:45 - 10:15',  'start' => '09:45', 'end' => '10:15', 'type' => 'homeroom', 'special' => 'JHS/GS HOMEROOM / STUDENT ACTIVITIES'],
            ['order' => 4, 'label' => '10:15 - 11:15', 'start' => '10:15', 'end' => '11:15', 'type' => 'class'],
            ['order' => 5, 'label' => '11:15 - 12:15', 'start' => '11:15', 'end' => '12:15', 'type' => 'class'],
            ['order' => 6, 'label' => 'LUNCH',          'start' => '12:15', 'end' => '13:15', 'type' => 'lunch',    'special' => 'LUNCH'],
            ['order' => 7, 'label' => '1:15 - 2:15',   'start' => '13:15', 'end' => '14:15', 'type' => 'class'],
            ['order' => 8, 'label' => '2:15 - 3:15',   'start' => '14:15', 'end' => '15:15', 'type' => 'class'],
            ['order' => 9, 'label' => '3:15 - 4:15',   'start' => '15:15', 'end' => '16:15', 'type' => 'class'],
            ['order' => 10, 'label' => '4:15 - 5:15',  'start' => '16:15', 'end' => '17:15', 'type' => 'class'],
        ];
    }

    public static function days(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }

    private function weeklyKey(?string $day, ?string $start, ?string $end, ?string $gradeLevel, ?string $sectionName): string
    {
        return implode('|', [
            trim((string) $day),
            substr((string) $start, 0, 5),
            substr((string) $end, 0, 5),
            trim((string) $gradeLevel),
            trim((string) $sectionName),
        ]);
    }

    private function pruneStaleWeeklyRows(int $teacherId, string $schoolYear): void
    {
        $approvedKeys = \App\Models\ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->get(['day_of_week', 'start_time', 'end_time', 'grade_level', 'section_name'])
            ->map(function ($schedule) {
                return $this->weeklyKey(
                    $schedule->day_of_week,
                    $schedule->start_time,
                    $schedule->end_time,
                    $schedule->grade_level,
                    $schedule->section_name
                );
            })
            ->flip();

        MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->get()
            ->each(function (MasterWeeklySchedule $row) use ($approvedKeys) {
                if (($row->entry_type ?? 'class') !== 'class') {
                    return;
                }

                $rowKey = $this->weeklyKey(
                    $row->day_of_week,
                    $row->time_start,
                    $row->time_end,
                    $row->grade_level,
                    $row->section_name
                );

                if (! $approvedKeys->has($rowKey)) {
                    $row->delete();
                }
            });
    }

    /**
     * Show the admin management page (JH admin).
     */
    public function manageJH(Request $request, int $teacherId)
    {
        return $this->buildManageView($request, $teacherId, 'junior-high-admin.master-schedule.manage');
    }

    /**
     * Show the admin management page (GS admin).
     */
    public function manageGS(Request $request, int $teacherId)
    {
        return $this->buildManageView($request, $teacherId, 'grade-school-admin.master-schedule.manage');
    }

    /**
     * Return the read-only paper card view for a teacher (JH admin context).
     * Returns standalone HTML (no layout) so it can be opened in a new tab
     * or loaded into a modal via fetch.
     */
    public function cardViewJH(Request $request, int $teacherId)
    {
        return $this->buildCardView($request, $teacherId);
    }

    /**
     * Return the read-only paper card view for a teacher (GS admin context).
     */
    public function cardViewGS(Request $request, int $teacherId)
    {
        return $this->buildCardView($request, $teacherId);
    }

    public function downloadJH(Request $request, int $teacherId)
    {
        return $this->downloadScheduleFile($request, $teacherId);
    }

    public function downloadGS(Request $request, int $teacherId)
    {
        return $this->downloadScheduleFile($request, $teacherId);
    }

    private function downloadScheduleFile(Request $request, int $teacherId)
    {
        $schoolLevel = FacultyLoadSupport::schoolLevelForConnection();
        if (! FacultyLoadSupport::facultyIdHasRegisteredAccount($teacherId, $schoolLevel)) {
            abort(404, 'This teacher does not have a user account in User Accounts.');
        }

        $teacher = User::findOrFail($teacherId);
        $schoolYear = $request->input('school_year', '2025-2026');
        $semester = $request->input('semester', '1st Semester');

        $this->pruneStaleWeeklyRows($teacherId, $schoolYear);

        $existing = MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->get()
            ->keyBy(fn ($r) => $r->slot_order . '_' . $r->day_of_week);

        $subjectHandled = $existing->first()?->subject_handled ?? '';
        $facultyName = strtoupper(trim($teacher->first_name . ' ' . $teacher->last_name));
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($teacher->first_name . '-' . $teacher->last_name)) ?: 'teacher';
        $filename = 'master-schedule-' . $safeName . '-' . str_replace('/', '-', $schoolYear) . '.csv';

        $slots = self::timeSlots();
        $days = self::days();

        return response()->streamDownload(function () use ($slots, $days, $existing, $facultyName, $schoolYear, $semester, $subjectHandled) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['MASTER LOADING SCHEDULE']);
            fputcsv($out, ['Name of Faculty', $facultyName]);
            fputcsv($out, ['School Year', $schoolYear]);
            fputcsv($out, ['Semester', $semester]);
            fputcsv($out, ['Subject / Grade Level Handled', $subjectHandled]);
            fputcsv($out, []);
            fputcsv($out, array_merge(['TIME'], array_map('strtoupper', $days)));

            foreach ($slots as $slot) {
                if (in_array($slot['type'], ['lunch', 'homeroom'], true)) {
                    fputcsv($out, [$slot['label'], $slot['special'] ?? strtoupper($slot['type'])]);
                    continue;
                }
                $row = [$slot['label']];
                foreach ($days as $day) {
                    $key = $slot['order'] . '_' . $day;
                    $cell = $existing->get($key);
                    $grade = trim((string) ($cell?->grade_section ?? ''));
                    $sub = trim((string) ($cell?->substitute_teacher ?? ''));
                    $row[] = $grade !== '' || $sub !== ''
                        ? trim($grade . ($sub !== '' ? ' | Sub: ' . $sub : ''))
                        : '';
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildCardView(Request $request, int $teacherId)
    {
        $schoolLevel = FacultyLoadSupport::schoolLevelForConnection();
        if (! FacultyLoadSupport::facultyIdHasRegisteredAccount($teacherId, $schoolLevel)) {
            abort(404, 'This teacher does not have a user account in User Accounts.');
        }

        $teacher    = User::findOrFail($teacherId);
        $schoolYear = $request->input('school_year', '2025-2026');
        $semester   = $request->input('semester', '1st Semester');

        $this->pruneStaleWeeklyRows($teacherId, $schoolYear);

        $existing = MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->get()
            ->keyBy(fn($r) => $r->slot_order . '_' . $r->day_of_week);

        $subjectHandled = $existing->first()?->subject_handled ?? '';

        // Build unique "SUBJECT GRADELEVEL" options from teacher's APPROVED class schedules
        $subjectOptions = \App\Models\ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->whereNotNull('subject')
            ->select('subject', 'grade_level')
            ->distinct()
            ->get()
            ->map(function ($cs) {
                $subject = strtoupper(trim($cs->subject ?? ''));
                $grade   = preg_replace('/[^0-9]/u', '', $cs->grade_level ?? '');
                return $grade ? $subject . ' ' . $grade : $subject;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('master-weekly-schedule._card', [
            'teacher'        => $teacher,
            'schoolYear'     => $schoolYear,
            'semester'       => $semester,
            'timeSlots'      => self::timeSlots(),
            'days'           => self::days(),
            'existing'       => $existing,
            'subjectHandled' => $subjectHandled,
            'subjectOptions' => $subjectOptions,
        ]);
    }

    private function buildManageView(Request $request, int $teacherId, string $viewName)
    {
        $schoolLevel = FacultyLoadSupport::schoolLevelForConnection();
        if (! FacultyLoadSupport::facultyIdHasRegisteredAccount($teacherId, $schoolLevel)) {
            abort(404, 'This teacher does not have a user account in User Accounts.');
        }

        $teacher = User::findOrFail($teacherId);
        $schoolYear = $request->input('school_year', '2025-2026');

        $this->pruneStaleWeeklyRows($teacherId, $schoolYear);

        // Load existing cells keyed by "slot_order_DayOfWeek"
        $existing = MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->get()
            ->keyBy(fn($r) => $r->slot_order . '_' . $r->day_of_week);

        // Auto-populate empty cells from approved class schedules,
        // but NOT immediately after the admin clicked Clear.
        $justCleared = session('just_cleared', false);
        $hasContent  = $existing->filter(fn($r) => !empty($r->grade_section))->isNotEmpty();
        if (!$justCleared && !$hasContent) {
            $slotsByStart = collect(self::timeSlots())->keyBy('start');
            \App\Models\ClassSchedule::where('faculty_id', $teacherId)
                ->where('admin_approved', true)
                ->get()
                ->each(function ($cs) use (&$existing, $slotsByStart) {
                    if (!$cs->start_time || !$cs->day_of_week) return;
                    $startH = substr($cs->start_time, 0, 5); // "HH:MM"
                    $slot   = $slotsByStart->get($startH);
                    if (!$slot) return;
                    $key = $slot['order'] . '_' . $cs->day_of_week;
                    // Don't overwrite a cell that already has meaningful content
                    if ($existing->has($key) && !empty($existing[$key]->grade_section)) return;
                    $gradeNum     = preg_replace('/[^0-9]/u', '', $cs->grade_level ?? '');
                    $subjectLabel = strtoupper(trim($cs->subject ?? ''));
                    $sectionName  = $cs->section_name ?? '';
                    // Build "ENGLISH 7 – CHERUBIM" style label for the cell
                    $gradeSection = $subjectLabel . ($gradeNum ? ' ' . $gradeNum : '');
                    if ($sectionName) $gradeSection .= ' – ' . $sectionName;
                    $existing->put($key, (object)[
                        'grade_section'    => $gradeSection,
                        'grade_level'      => $cs->grade_level,
                        'section_name'     => $sectionName,
                        'substitute_teacher'=> null,
                        'subject_handled'  => $subjectLabel . ($gradeNum ? ' ' . $gradeNum : ''),
                        'entry_type'       => 'class',
                        'slot_order'       => $slot['order'],
                        'day_of_week'      => $cs->day_of_week,
                    ]);
                });
        }

        // Subject handled is stored per row, grab from first row
        $subjectHandled = $existing->first()?->subject_handled ?? '';

        // All approved class schedules for this teacher (used for client-side subject filter)
        $teacherSchedules = \App\Models\ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->get(['subject', 'grade_level', 'section_name', 'day_of_week', 'start_time'])
            ->toArray();

        // Build unique "SUBJECT GRADELEVEL" options from teacher's APPROVED class schedules
        $subjectOptions = \App\Models\ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->whereNotNull('subject')
            ->select('subject', 'grade_level')
            ->distinct()
            ->get()
            ->map(function ($cs) {
                $subject = strtoupper(trim($cs->subject ?? ''));
                $grade   = preg_replace('/[^0-9]/u', '', $cs->grade_level ?? '');
                return $grade ? $subject . ' ' . $grade : $subject;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view($viewName, [
            'teacher'          => $teacher,
            'schoolYear'       => $schoolYear,
            'timeSlots'        => self::timeSlots(),
            'days'             => self::days(),
            'existing'         => $existing,
            'subjectHandled'   => $subjectHandled,
            'subjectOptions'   => $subjectOptions,
            'teacherSchedules' => $teacherSchedules,
        ]);
    }

    /**
     * Save (upsert) the entire grid for a teacher.
     * Expects: school_year, subject_handled, and cells[slot_order][day] = [...fields]
     */
    public function save(Request $request, int $teacherId)
    {
        $request->validate([
            'school_year'      => 'required|string|max:20',
            'subject_handled'  => 'required|string|max:150',
            'cells'            => 'required|array',
            'cells.*.*.grade_section'    => 'nullable|string|max:150',
            'cells.*.*.substitute_teacher'=> 'nullable|string|max:150',
            'cells.*.*.entry_type'       => 'nullable|string|in:class,lunch,homeroom,free',
        ]);

        $teacher = User::findOrFail($teacherId);
        $schoolYear     = $request->input('school_year');
        $subjectHandled = $request->input('subject_handled');
        $cells          = $request->input('cells', []);
        $slots          = self::timeSlots();

        // Pre-load approved class schedules keyed by "day|HH:MM" for fast per-cell lookup
        $approvedCS = \App\Models\ClassSchedule::where('faculty_id', $teacherId)
            ->where('admin_approved', true)
            ->where('status', 'active')
            ->get(['day_of_week', 'start_time', 'grade_level', 'section_name'])
            ->keyBy(fn($cs) => $cs->day_of_week . '|' . substr($cs->start_time, 0, 5));

        foreach ($slots as $slot) {
            foreach (self::days() as $day) {
                $cell = $cells[$slot['order']][$day] ?? [];

                $entryType = $slot['type'];
                if (!in_array($entryType, ['class', 'lunch', 'homeroom', 'free'])) {
                    $entryType = 'class';
                }
                if (isset($cell['entry_type']) && in_array($cell['entry_type'], ['class', 'lunch', 'homeroom', 'free'])) {
                    $entryType = $cell['entry_type'];
                }

                $gradeSection = trim($cell['grade_section'] ?? '');

                // Resolve grade_level and section_name from matching approved class_schedule
                $matchedCS   = $approvedCS->get($day . '|' . $slot['start']);
                $gradeLevel  = $matchedCS?->grade_level;
                $sectionName = $matchedCS?->section_name;

                // Fall back to parsing grade_section text if no DB match
                if (!$gradeLevel && $gradeSection) {
                    if (preg_match('/\b([0-9]{1,2})\b/u', $gradeSection, $match)) {
                        $gradeLevel = $match[1];
                    }
                }
                if (!$sectionName && $gradeSection) {
                    if (preg_match('/[–\-\/]\s*(.+)$/u', $gradeSection, $match)) {
                        $sectionName = trim($match[1]);
                    }
                }

                MasterWeeklySchedule::updateOrCreate(
                    [
                        'faculty_id'  => $teacherId,
                        'school_year' => $schoolYear,
                        'slot_order'  => $slot['order'],
                        'day_of_week' => $day,
                    ],
                    [
                        'subject_handled'   => $subjectHandled,
                        'time_label'        => $slot['label'],
                        'time_start'        => $slot['start'],
                        'time_end'          => $slot['end'],
                        'entry_type'        => $entryType,
                        'grade_section'     => $gradeSection ?: null,
                        'grade_level'       => $gradeLevel,
                        'section_name'      => $sectionName,
                        'substitute_teacher'=> $cell['substitute_teacher'] ?? null,
                        'special_label'     => $slot['special'] ?? null,
                        'created_by'        => Auth::id(),
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Weekly schedule saved for ' . $teacher->first_name . ' ' . $teacher->last_name . '.');
    }

    /**
     * Clear all cells for a teacher + school year.
     */
    public function clear(Request $request, int $teacherId)
    {
        $request->validate(['school_year' => 'required|string|max:20']);
        $schoolYear = $request->input('school_year');

        MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->delete();

        return redirect()->back()
            ->with('success', 'Weekly schedule cleared.')
            ->with('just_cleared', true);
    }

    /**
     * Return grid data as JSON (used by teacher view).
     */
    public function getData(Request $request, int $teacherId)
    {
        $schoolYear = $request->input('school_year', '2025-2026');

        $this->pruneStaleWeeklyRows($teacherId, $schoolYear);

        $cells = MasterWeeklySchedule::where('faculty_id', $teacherId)
            ->where('school_year', $schoolYear)
            ->orderBy('slot_order')
            ->get();

        return response()->json([
            'success'   => true,
            'teacher'   => User::find($teacherId),
            'timeSlots' => self::timeSlots(),
            'days'      => self::days(),
            'cells'     => $cells,
        ]);
    }
}
