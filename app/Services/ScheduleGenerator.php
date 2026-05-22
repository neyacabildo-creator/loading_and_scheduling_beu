<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\LoadConflictLog;
use App\Models\Room;
use App\Models\User;

/**
 * ScheduleGenerator
 *
 * Automatically generates class schedules based on faculty loads and room
 * availability.  The algorithm is a greedy slot-filler that:
 *  1. Loads all active FacultyLoad records (teacher + subject + classes needed).
 *  2. Tries every day × time-slot combination for each class needed.
 *  3. Checks teacher availability (no double-booking) and room availability.
 *  4. Returns proposals + conflicts — nothing is persisted until confirm().
 *
 * Call generate() to get the dry-run result, then persist() to save it.
 */
class ScheduleGenerator
{
    /** Standard 1-hour school time slots (Philippines BEU schedule) */
    public const DEFAULT_SLOTS = [
        ['start' => '07:00', 'end' => '08:00'],
        ['start' => '08:00', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '11:00'],
        ['start' => '11:00', 'end' => '12:00'],
        ['start' => '13:00', 'end' => '14:00'],
        ['start' => '14:00', 'end' => '15:00'],
        ['start' => '15:00', 'end' => '16:00'],
        ['start' => '16:00', 'end' => '17:00'],
    ];

    public const DEFAULT_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    private array $config;

    /** In-memory busy maps for the current generation run */
    private array $teacherBusy = [];
    private array $roomBusy    = [];

    /** Per-teacher per-day class counter for this run */
    private array $teacherDailyCount = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'school_year'  => date('Y') . '-' . (date('Y') + 1),
            'days'         => self::DEFAULT_DAYS,
            'slots'        => self::DEFAULT_SLOTS,
            'max_per_day'  => 2,
            'sections'     => [],
            'school_level' => 'junior_high',
        ], $config);
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Dry-run the generator.
     *
     * @return array{
     *   proposed: array,
     *   conflicts: array,
     *   unscheduled: array,
     *   stats: array
     * }
     */
    public function generate(): array
    {
        $this->teacherBusy        = [];
        $this->roomBusy           = [];
        $this->teacherDailyCount  = [];

        // Seed busy maps from existing non-cancelled/non-deleted schedules
        $existing = ClassSchedule::whereNotIn('status', ['cancelled', 'deleted', 'rejected'])->get();
        foreach ($existing as $s) {
            $this->markTeacherBusy((int)$s->faculty_id, $s->day_of_week, $s->start_time);
            if ($s->room_id) {
                $this->markRoomBusy((int)$s->room_id, $s->day_of_week, $s->start_time);
            }
        }

        $rooms = Room::where('status', 'available')->get();
        $loads = FacultyLoad::where('status', '!=', 'inactive')->whereNotNull('subject')->get();

        $userIds = $loads->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        $proposed    = [];
        $conflicts   = [];
        $unscheduled = [];

        $sections   = $this->config['sections'];
        $sectionIdx = 0;

        foreach ($loads as $load) {
            if (!$load->faculty_id) {
                continue;
            }

            $teacher     = $users[$load->faculty_id] ?? null;
            $teacherName = $teacher
                ? trim($teacher->first_name . ' ' . $teacher->last_name)
                : "Faculty #{$load->faculty_id}";

            $classesNeeded = max(1, (int)$load->classes_assigned);

            for ($i = 0; $i < $classesNeeded; $i++) {
                $section = !empty($sections)
                    ? $sections[$sectionIdx % count($sections)]
                    : 'TBA';
                $sectionIdx++;

                [$slotFound, $entry, $conflict] = $this->findSlot(
                    $load, $teacher, $teacherName, $section, $rooms
                );

                if ($slotFound) {
                    $proposed[] = $entry;
                    if ($conflict) {
                        // Placed but without a room
                        $conflicts[] = $conflict;
                    }
                } else {
                    $unscheduled[] = [
                        'teacher'      => $teacherName,
                        'subject'      => $load->subject,
                        'class_number' => $i + 1,
                        'reason'       => $conflict['message'] ?? 'No available day/time slot.',
                    ];
                }
            }
        }

        return [
            'proposed'    => $proposed,
            'conflicts'   => $conflicts,
            'unscheduled' => $unscheduled,
            'stats'       => [
                'total_proposed'    => count($proposed),
                'total_conflicts'   => count($conflicts),
                'total_unscheduled' => count($unscheduled),
            ],
        ];
    }

    /**
     * Detect conflicts in a list of already-existing or newly-proposed entries.
     * Returns an array of conflict descriptions.
     */
    public static function detectConflicts(array $entries): array
    {
        $conflicts = [];
        $teacherMap = [];
        $roomMap    = [];

        foreach ($entries as $idx => $entry) {
            $key = "{$entry['faculty_id']}|{$entry['day_of_week']}|{$entry['start_time']}";
            if (isset($teacherMap[$key])) {
                $conflicts[] = [
                    'type'    => 'teacher_double_booking',
                    'message' => "Teacher '{$entry['teacher_name']}' is double-booked on "
                               . "{$entry['day_of_week']} {$entry['start_time']}.",
                    'indices' => [$teacherMap[$key], $idx],
                ];
            } else {
                $teacherMap[$key] = $idx;
            }

            if (!empty($entry['room_id'])) {
                $rKey = "{$entry['room_id']}|{$entry['day_of_week']}|{$entry['start_time']}";
                if (isset($roomMap[$rKey])) {
                    $conflicts[] = [
                        'type'    => 'room_double_booking',
                        'message' => "Room '{$entry['room_number']}' is double-booked on "
                                   . "{$entry['day_of_week']} {$entry['start_time']}.",
                        'indices' => [$roomMap[$rKey], $idx],
                    ];
                } else {
                    $roomMap[$rKey] = $idx;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Persist the proposed schedules to ClassSchedule and log conflicts.
     *
     * @param  array $proposed   Output from generate()['proposed']
     * @param  array $conflicts  Output from generate()['conflicts']
     * @param  int   $adminId    Auth::id() of the admin confirming
     * @return int   Number of schedule records created
     */
    public function persist(array $proposed, array $conflicts, int $adminId): int
    {
        $now   = now();
        $count = 0;

        foreach ($proposed as $entry) {
            $row = [
                'faculty_id'     => $entry['faculty_id'],
                'subject'        => $entry['subject'],
                'grade_level'    => $entry['grade_level'] ?? null,
                'section_name'   => $entry['section_name'] ?? null,
                'room_id'        => $entry['room_id'] ?? null,
                'day_of_week'    => $entry['day_of_week'],
                'start_time'     => $entry['start_time'],
                'end_time'       => $entry['end_time'],
                'status'         => 'pending',
                'admin_approved' => false,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
            ClassSchedule::create($row);
            $count++;
        }

        // Log conflicts to load_conflict_log for admin review
        foreach ($conflicts as $c) {
            LoadConflictLog::create([
                'faculty_id'    => null,
                'conflict_type' => $c['type'] ?? 'scheduling',
                'description'   => $c['message'] ?? '',
                'severity'      => ($c['type'] ?? '') === 'cross_school' ? 'high' : 'medium',
                'detected_at'   => $now,
                'status'        => 'open',
            ]);
        }

        return $count;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Try every day × slot combination to find a free slot for one class.
     *
     * @return array [bool $placed, array|null $entry, array|null $conflict]
     */
    private function findSlot(
        FacultyLoad $load,
        ?User $teacher,
        string $teacherName,
        string $section,
        $rooms
    ): array {
        foreach ($this->config['days'] as $day) {
            $dailyCount = $this->teacherDailyCount[$load->faculty_id][$day] ?? 0;
            if ($dailyCount >= $this->config['max_per_day']) {
                continue;
            }

            foreach ($this->config['slots'] as $slot) {
                $start = $slot['start'];
                $end   = $slot['end'];

                // Teacher busy?
                if ($this->isTeacherBusy((int)$load->faculty_id, $day, $start)) {
                    continue;
                }

                // Find a free room
                $room     = $this->findFreeRoom($rooms, $day, $start);
                $conflict = null;

                if (!$room) {
                    $conflict = [
                        'type'    => 'no_room',
                        'teacher' => $teacherName,
                        'day'     => $day,
                        'time'    => "$start–$end",
                        'subject' => $load->subject,
                        'message' => "No available room for {$teacherName} on {$day} {$start}–{$end}. Scheduled without a room.",
                    ];
                }

                $entry = [
                    'faculty_id'    => $load->faculty_id,
                    'teacher_name'  => $teacherName,
                    'subject'       => $load->subject,
                    'grade_level'   => str_contains($section, ' - ') ? trim(explode(' - ', $section, 2)[0]) : null,
                    'section_name'  => str_contains($section, ' - ') ? trim(explode(' - ', $section, 2)[1]) : trim($section),
                    'room_id'       => $room?->id,
                    'room_number'   => $room?->room_number ?? 'TBA',
                    'day_of_week'   => $day,
                    'start_time'    => $start,
                    'end_time'      => $end,
                    'status'        => 'pending',
                    'admin_approved' => false,
                ];

                // Mark as busy for subsequent passes
                $this->markTeacherBusy((int)$load->faculty_id, $day, $start);
                if ($room) {
                    $this->markRoomBusy((int)$room->id, $day, $start);
                }
                $this->teacherDailyCount[$load->faculty_id][$day] = $dailyCount + 1;

                return [true, $entry, $conflict];
            }
        }

        return [false, null, ['message' => "No available slot for {$teacherName} — {$load->subject}."]];
    }

    private function markTeacherBusy(int $facultyId, string $day, string $start): void
    {
        $this->teacherBusy["{$facultyId}|{$day}|{$start}"] = true;
    }

    private function markRoomBusy(int $roomId, string $day, string $start): void
    {
        $this->roomBusy["{$roomId}|{$day}|{$start}"] = true;
    }

    private function isTeacherBusy(int $facultyId, string $day, string $start): bool
    {
        return isset($this->teacherBusy["{$facultyId}|{$day}|{$start}"]);
    }

    private function isRoomBusy(int $roomId, string $day, string $start): bool
    {
        return isset($this->roomBusy["{$roomId}|{$day}|{$start}"]);
    }

    private function findFreeRoom($rooms, string $day, string $start)
    {
        foreach ($rooms as $room) {
            if (!$this->isRoomBusy((int)$room->id, $day, $start)) {
                return $room;
            }
        }
        return null;
    }
}
