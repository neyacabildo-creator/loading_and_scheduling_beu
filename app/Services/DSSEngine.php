<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\LoadConflictLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * DSSEngine — Decision Support System core algorithm.
 *
 * Rule-based engine that inspects the current school database (already scoped
 * by the caller via middleware) and produces:
 *  - Recommendations  (categorised, prioritised)
 *  - Notifications    (immediate-action alerts)
 *  - Workload summary (per-teacher load analysis)
 *  - Room allocation  (per-room utilisation)
 *
 * All reads are scoped to the active DB connection set by middleware.
 * Nothing is written to the database by this class.
 */
class DSSEngine
{
    // ─── Configurable thresholds ──────────────────────────────────────────────
    private const OVERLOAD_HOURS   = 30;   // load_hours above this → overloaded
    private const UNDERLOAD_HOURS  = 6;    // load_hours below this → underutilised
    private const MAX_DAILY_CLASSES = 3;   // daily class count above this → schedule gap alert
    private const GAP_HOURS_ALERT   = 2;   // idle hours between consecutive classes on same day
    private const LOW_ROOM_UTIL     = 0.25; // rooms used less than 25 % of available slots
    private const HIGH_ROOM_UTIL    = 0.90; // rooms used more than 90 % → near-capacity alert
    private const MAX_LOAD_CLASSES  = 5;   // more than this triggers overload recommendation

    /** All days in scope */
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    // ─── Cached data for the current analysis run ────────────────────────────
    private Collection $schedules;
    private Collection $loads;
    private Collection $rooms;
    private Collection $teachers;

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Run the full analysis against the currently-active school database.
     *
     * @return array{
     *   recommendations: array,
     *   notifications: array,
     *   workload_summary: array,
     *   room_allocation: array,
     *   stats: array
     * }
     */
    public function analyze(): array
    {
        $this->loadData();

        $recommendations = array_merge(
            $this->detectTimeConflicts(),
            $this->detectWorkloadIssues(),
            $this->detectUnscheduledLoads(),
            $this->detectRoomUtilization(),
            $this->detectSubjectRoomMismatch(),
            $this->detectScheduleGaps()
        );

        // Sort: high → medium → low
        usort($recommendations, fn ($a, $b) =>
            $this->priorityWeight($b['priority']) <=> $this->priorityWeight($a['priority'])
        );

        $stats = [
            'total'  => count($recommendations),
            'high'   => count(array_filter($recommendations, fn ($r) => $r['priority'] === 'high')),
            'medium' => count(array_filter($recommendations, fn ($r) => $r['priority'] === 'medium')),
            'low'    => count(array_filter($recommendations, fn ($r) => $r['priority'] === 'low')),
        ];

        return [
            'recommendations' => $recommendations,
            'notifications'   => $this->buildNotifications($recommendations),
            'workload_summary' => $this->buildWorkloadSummary(),
            'room_allocation'  => $this->buildRoomAllocation(),
            'stats'            => $stats,
        ];
    }

    // =========================================================================
    // Data loading
    // =========================================================================

    private function loadData(): void
    {
        $this->schedules = ClassSchedule::whereNotIn('status', ['cancelled', 'deleted', 'rejected'])->get();
        $this->loads     = FacultyLoad::where('status', '!=', 'inactive')->get();
        $this->rooms     = Room::all();

        $userIds = $this->schedules->pluck('faculty_id')
            ->merge($this->loads->pluck('faculty_id'))
            ->filter()->unique();

        $this->teachers = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();
    }

    // =========================================================================
    // Rule 1 — Time conflict detection
    // =========================================================================

    /**
     * Detect teachers and rooms that are double-booked in class_schedules.
     */
    private function detectTimeConflicts(): array
    {
        $recommendations = [];
        $teacherSlots    = [];   // [faculty_id][day][start_time] = schedule_id
        $roomSlots       = [];   // [room_id][day][start_time] = schedule_id

        foreach ($this->schedules as $s) {
            $fid   = (int) $s->faculty_id;
            $day   = $s->day_of_week;
            $start = substr((string)$s->start_time, 0, 5);   // normalise to HH:MM

            // Teacher double-booking
            if (isset($teacherSlots[$fid][$day][$start])) {
                $teacher = $this->teacherName($fid);
                $recommendations[] = $this->rec(
                    'conflict',
                    'high',
                    "Teacher Double-Booking — {$teacher}",
                    "Teacher {$teacher} is assigned to two different classes on {$day} at {$start}. "
                    . "This creates a direct time conflict that must be resolved before the schedule can be finalised.",
                    "Reassign one of the conflicting classes to another teacher or move it to a different time slot.",
                    ['faculty_id' => $fid, 'day' => $day, 'time' => $start],
                    'admin.class-schedule'
                );
            } else {
                $teacherSlots[$fid][$day][$start] = $s->id;
            }

            // Room double-booking
            if ($s->room_id) {
                $rid = (int) $s->room_id;
                if (isset($roomSlots[$rid][$day][$start])) {
                    $room = $this->rooms->find($rid);
                    $roomNum = $room?->room_number ?? "Room #{$rid}";
                    $recommendations[] = $this->rec(
                        'conflict',
                        'high',
                        "Room Double-Booking — {$roomNum}",
                        "{$roomNum} is assigned to two different classes on {$day} at {$start}. "
                        . "Only one class can occupy a room at a time.",
                        "Assign one of the classes to a different available room for that time slot.",
                        ['room_id' => $rid, 'day' => $day, 'time' => $start],
                        'admin.class-schedule'
                    );
                } else {
                    $roomSlots[$rid][$day][$start] = $s->id;
                }
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Rule 2 — Faculty workload analysis
    // =========================================================================

    /**
     * Detect overloaded, underloaded, and capacity-exceeded faculty loads.
     */
    private function detectWorkloadIssues(): array
    {
        $recommendations = [];

        // Group loads per faculty
        $byFaculty = $this->loads->groupBy('faculty_id');

        foreach ($byFaculty as $facultyId => $facultyLoads) {
            if (!$facultyId) {
                continue;
            }

            $name         = $this->teacherName((int) $facultyId);
            $totalHours   = $facultyLoads->sum('load_hours');
            $totalClasses = $facultyLoads->sum('classes_assigned');

            // Overloaded
            if ($totalHours > self::OVERLOAD_HOURS || $totalClasses > self::MAX_LOAD_CLASSES) {
                $recommendations[] = $this->rec(
                    'balance',
                    'high',
                    "Faculty Overload — {$name}",
                    "{$name} is currently carrying {$totalHours} load hours and {$totalClasses} classes assigned. "
                    . "This exceeds the recommended maximum of " . self::OVERLOAD_HOURS . " hours / "
                    . self::MAX_LOAD_CLASSES . " classes per faculty.",
                    "Redistribute one or more subject assignments to another qualified and available teacher to relieve the load.",
                    ['faculty_id' => $facultyId, 'total_hours' => $totalHours, 'total_classes' => $totalClasses],
                    'admin.faculty-loading'
                );
            }

            // Underloaded (only flag if teacher is active, not part-time)
            $status = $facultyLoads->first()->status ?? '';
            if ($totalHours < self::UNDERLOAD_HOURS && !in_array($status, ['part-time', 'inactive'])) {
                $recommendations[] = $this->rec(
                    'optimize',
                    'low',
                    "Underutilised Faculty — {$name}",
                    "{$name} has only {$totalHours} load hours assigned, well below the standard teaching load. "
                    . "Available capacity is being underutilised.",
                    "Assign additional subjects or classes to this teacher to maximise faculty utilisation.",
                    ['faculty_id' => $facultyId, 'total_hours' => $totalHours],
                    'admin.faculty-loading'
                );
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Rule 3 — Unscheduled faculty loads
    // =========================================================================

    /**
     * Flag faculty loads that have no corresponding class schedule entries.
     */
    private function detectUnscheduledLoads(): array
    {
        $recommendations = [];

        // Faculty IDs that have at least one active schedule
        $scheduledFaculty = $this->schedules->pluck('faculty_id')->filter()->unique();

        foreach ($this->loads as $load) {
            if (!$load->faculty_id) {
                continue;
            }
            if (!$scheduledFaculty->contains($load->faculty_id)) {
                $name = $this->teacherName((int) $load->faculty_id);
                $recommendations[] = $this->rec(
                    'conflict',
                    'medium',
                    "No Schedule Assigned — {$name}",
                    "{$name} has an active faculty load for \"{$load->subject}\" ({$load->load_hours} hours) "
                    . "but no class schedule has been generated or assigned yet.",
                    "Run the Schedule Generator or manually create a class schedule entry for this faculty load.",
                    ['faculty_id' => $load->faculty_id, 'subject' => $load->subject],
                    'admin.class-schedule'
                );
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Rule 4 — Room utilisation
    // =========================================================================

    /**
     * Flag rooms that are under-utilised or near capacity saturation.
     */
    private function detectRoomUtilization(): array
    {
        $recommendations = [];

        // Total available slot-days: 5 days × 9 slots = 45 per room
        $totalSlots = count(self::DAYS) * 9;

        $roomUsage = $this->schedules
            ->whereNotNull('room_id')
            ->groupBy('room_id')
            ->map->count();

        foreach ($this->rooms as $room) {
            if ($room->status === 'maintenance') {
                continue;
            }

            $used       = $roomUsage[$room->id] ?? 0;
            $utilisation = $totalSlots > 0 ? $used / $totalSlots : 0;

            if ($utilisation < self::LOW_ROOM_UTIL && $room->status === 'available') {
                $pct = round($utilisation * 100);
                $recommendations[] = $this->rec(
                    'optimize',
                    'low',
                    "Low Room Utilisation — {$room->room_number}",
                    "Room {$room->room_number} (Building: {$room->building}, Capacity: {$room->capacity}) is only "
                    . "being used {$pct}% of available teaching slots ({$used}/{$totalSlots}). "
                    . "Significant scheduling capacity is left unused.",
                    "Assign additional classes to this room during its free time slots to improve space utilisation.",
                    ['room_id' => $room->id, 'used_slots' => $used, 'utilisation_pct' => $pct],
                    'admin.class-schedule'
                );
            }

            if ($utilisation > self::HIGH_ROOM_UTIL) {
                $pct = round($utilisation * 100);
                $recommendations[] = $this->rec(
                    'optimize',
                    'medium',
                    "Near-Capacity Room — {$room->room_number}",
                    "Room {$room->room_number} is scheduled at {$pct}% occupancy ({$used}/{$totalSlots} slots). "
                    . "Any additional bookings risk over-saturation and scheduling conflicts.",
                    "Redistribute some classes to other available rooms to prevent future conflicts in this room.",
                    ['room_id' => $room->id, 'used_slots' => $used, 'utilisation_pct' => $pct],
                    'admin.class-schedule'
                );
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Rule 5 — Subject–room specialisation mismatch
    // =========================================================================

    /** Keywords that indicate a subject needs a laboratory room. */
    private const LAB_KEYWORDS = [
        'science', 'chemistry', 'biology', 'physics', 'laboratory',
        'computer', 'ict', 'technology', 'home economics', 'tech-voc',
    ];

    /**
     * Flag science/computer subjects assigned to rooms without lab facilities.
     */
    private function detectSubjectRoomMismatch(): array
    {
        $recommendations = [];
        $roomMap = $this->rooms->keyBy('id');

        foreach ($this->schedules as $s) {
            if (!$s->room_id || !$s->subject) {
                continue;
            }

            $subjectLower = strtolower((string) $s->subject);
            $needsLab     = false;
            foreach (self::LAB_KEYWORDS as $kw) {
                if (str_contains($subjectLower, $kw)) {
                    $needsLab = true;
                    break;
                }
            }

            if (!$needsLab) {
                continue;
            }

            $room = $roomMap[$s->room_id] ?? null;
            if ($room && !$room->has_laboratory) {
                $teacher = $this->teacherName((int) $s->faculty_id);
                $roomNum = $room->room_number;
                $recommendations[] = $this->rec(
                    'improve',
                    'medium',
                    "Lab Subject in Non-Lab Room — {$s->subject}",
                    "\"{$s->subject}\" taught by {$teacher} is scheduled in Room {$roomNum}, "
                    . "which does not have laboratory facilities. This may limit hands-on learning activities.",
                    "Reassign this class to a room equipped with laboratory facilities to meet subject requirements.",
                    ['schedule_id' => $s->id, 'subject' => $s->subject, 'room_id' => $s->room_id],
                    'admin.class-schedule'
                );
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Rule 6 — Schedule gap detection
    // =========================================================================

    /**
     * Detect teachers who have large idle gaps between their classes on the same day.
     */
    private function detectScheduleGaps(): array
    {
        $recommendations = [];

        // Build [faculty_id][day] => sorted list of start times
        $teacherDaySlots = [];
        foreach ($this->schedules as $s) {
            if (!$s->faculty_id) {
                continue;
            }
            $teacherDaySlots[(int) $s->faculty_id][$s->day_of_week][] =
                strtotime('1970-01-01 ' . substr((string)$s->start_time, 0, 5));
        }

        foreach ($teacherDaySlots as $facultyId => $days) {
            foreach ($days as $day => $timestamps) {
                sort($timestamps);
                for ($i = 1; $i < count($timestamps); $i++) {
                    $gapHours = ($timestamps[$i] - $timestamps[$i - 1]) / 3600;
                    if ($gapHours > self::GAP_HOURS_ALERT) {
                        $name = $this->teacherName($facultyId);
                        $from = date('g:i A', $timestamps[$i - 1]);
                        $to   = date('g:i A', $timestamps[$i]);
                        $recommendations[] = $this->rec(
                            'optimize',
                            'low',
                            "Large Schedule Gap — {$name} on {$day}",
                            "{$name} has a " . round($gapHours, 1) . "-hour idle gap between classes on {$day} "
                            . "({$from} → {$to}). This reduces scheduling efficiency and may affect teacher productivity.",
                            "Consider rearranging this teacher's schedule to minimise idle time between consecutive classes.",
                            ['faculty_id' => $facultyId, 'day' => $day, 'gap_hours' => round($gapHours, 1)],
                            'admin.class-schedule'
                        );
                        break; // one recommendation per teacher per day is enough
                    }
                }
            }
        }

        return $recommendations;
    }

    // =========================================================================
    // Workload summary
    // =========================================================================

    /**
     * Build a per-teacher workload summary table.
     */
    private function buildWorkloadSummary(): array
    {
        $byFaculty = $this->loads->groupBy('faculty_id');
        $summary   = [];

        foreach ($byFaculty as $facultyId => $loads) {
            if (!$facultyId) {
                continue;
            }

            $totalHours   = round((float) $loads->sum('load_hours'), 2);
            $totalClasses = (int) $loads->sum('classes_assigned');
            $scheduled    = $this->schedules->where('faculty_id', $facultyId)->count();

            $status = 'normal';
            if ($totalHours > self::OVERLOAD_HOURS || $totalClasses > self::MAX_LOAD_CLASSES) {
                $status = 'overloaded';
            } elseif ($totalHours < self::UNDERLOAD_HOURS) {
                $status = 'underloaded';
            }

            $summary[] = [
                'faculty_id'     => $facultyId,
                'name'           => $this->teacherName((int) $facultyId),
                'subjects'       => $loads->pluck('subject')->filter()->unique()->values(),
                'load_hours'     => $totalHours,
                'classes_assigned' => $totalClasses,
                'scheduled_count'  => $scheduled,
                'status'           => $status,
            ];
        }

        usort($summary, fn ($a, $b) => $b['load_hours'] <=> $a['load_hours']);

        return $summary;
    }

    // =========================================================================
    // Room allocation
    // =========================================================================

    /**
     * Build a per-room allocation/utilisation summary.
     */
    private function buildRoomAllocation(): array
    {
        $totalSlots = count(self::DAYS) * 9;
        $roomUsage  = $this->schedules
            ->whereNotNull('room_id')
            ->groupBy('room_id')
            ->map->count();

        $allocation = [];
        foreach ($this->rooms as $room) {
            $used  = $roomUsage[$room->id] ?? 0;
            $util  = $totalSlots > 0 ? round(($used / $totalSlots) * 100, 1) : 0;

            $allocation[] = [
                'room_id'         => $room->id,
                'room_number'     => $room->room_number,
                'building'        => $room->building ?? '—',
                'capacity'        => $room->capacity ?? '—',
                'has_lab'         => (bool) $room->has_laboratory,
                'has_projector'   => (bool) $room->has_projector,
                'status'          => $room->status,
                'used_slots'      => $used,
                'total_slots'     => $totalSlots,
                'utilisation_pct' => $util,
            ];
        }

        usort($allocation, fn ($a, $b) => $b['utilisation_pct'] <=> $a['utilisation_pct']);

        return $allocation;
    }

    // =========================================================================
    // Notifications
    // =========================================================================

    /**
     * Convert high-priority recommendations into notification-style alerts.
     */
    private function buildNotifications(array $recommendations): array
    {
        $notifications = [];
        foreach ($recommendations as $rec) {
            if ($rec['priority'] === 'high') {
                $notifications[] = [
                    'type'    => $rec['type'],
                    'title'   => $rec['title'],
                    'message' => $rec['issue'],
                    'route'   => $rec['route'],
                ];
            }
        }
        return $notifications;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function teacherName(int $id): string
    {
        $user = $this->teachers[$id] ?? null;
        if ($user) {
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }
        return "Faculty #{$id}";
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high'   => 3,
            'medium' => 2,
            default  => 1,
        };
    }

    /**
     * Build a standardised recommendation array.
     */
    private function rec(
        string $type,
        string $priority,
        string $title,
        string $description,
        string $solution,
        array  $meta   = [],
        string $route  = ''
    ): array {
        return [
            'type'        => $type,        // conflict | balance | optimize | improve
            'priority'    => $priority,    // high | medium | low
            'title'       => $title,
            'issue'       => $description,
            'solution'    => $solution,
            'meta'        => $meta,
            'route'       => $route,
        ];
    }
}
