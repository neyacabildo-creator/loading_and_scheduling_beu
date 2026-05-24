<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScheduleMonitoringSupport
{
    /**
     * Full monitoring payload for the admin DSS page.
     *
     * @return array<string, mixed>
     */
    public static function buildDashboard(string $connection, string $schoolLevel): array
    {
        $schedules = self::activeSchedulesQuery()->get();
        $users = self::resolveUsers($schedules->pluck('faculty_id')->filter()->unique()->all());
        $rooms = self::resolveRooms($schedules->pluck('room_id')->filter()->unique()->all());

        $facultyConflicts = self::detectFacultyConflicts($schedules, $users);
        $roomConflicts = self::detectRoomConflicts($schedules, $rooms);
        $missingData = self::detectMissingData($schedules, $users, $rooms);
        $sharedOverload = self::buildSharedTeacherOverload($connection);
        $workload = self::buildWorkloadFairness($connection, $schoolLevel);
        $teacherHeatmap = self::buildTeacherUtilizationHeatmap($schedules, $users, $schoolLevel);
        $roomHeatmap = self::buildRoomUtilizationHeatmap($schedules, $rooms, $schoolLevel);

        $summary = [
            'faculty_conflicts' => count($facultyConflicts),
            'room_conflicts' => count($roomConflicts),
            'shared_overload' => count(array_filter($sharedOverload, fn ($r) => ($r['status'] ?? '') === 'overloaded')),
            'shared_cross_conflicts' => count(array_filter($sharedOverload, fn ($r) => ! empty($r['conflicts']))),
            'missing_schedule_date' => count($missingData['missing_schedule_date'] ?? []),
            'missing_room' => count($missingData['missing_room'] ?? []),
            'total_issues' => count($facultyConflicts) + count($roomConflicts)
                + count($missingData['missing_schedule_date'] ?? [])
                + count($missingData['missing_room'] ?? [])
                + count(array_filter($sharedOverload, fn ($r) => ($r['status'] ?? '') === 'overloaded' || ! empty($r['conflicts']))),
        ];

        return [
            'summary' => $summary,
            'faculty_conflicts' => $facultyConflicts,
            'room_conflicts' => $roomConflicts,
            'missing_data' => $missingData,
            'shared_overload' => $sharedOverload,
            'workload' => $workload,
            'teacher_heatmap' => $teacherHeatmap,
            'room_heatmap' => $roomHeatmap,
        ];
    }

    /**
     * Sections (grade + section) taught by teachers on leave during the current calendar week.
     *
     * @param  array<int, array<string, mixed>>  $leaveEntries
     * @return array<int, array<string, mixed>>
     */
    public static function sectionsAffectedThisWeek(string $connection, int $facultyId, string $dateFrom, string $dateTo): array
    {
        if ($facultyId <= 0) {
            return [];
        }

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
        $leaveStart = Carbon::parse($dateFrom)->startOfDay();
        $leaveEnd = Carbon::parse($dateTo)->endOfDay();

        if ($leaveEnd->lt($weekStart) || $leaveStart->gt($weekEnd)) {
            return [];
        }

        $overlapStart = $leaveStart->greaterThan($weekStart) ? $leaveStart : $weekStart;
        $overlapEnd = $leaveEnd->lessThan($weekEnd) ? $leaveEnd : $weekEnd;

        $affectedDays = [];
        for ($d = $overlapStart->copy(); $d->lte($overlapEnd); $d->addDay()) {
            $affectedDays[] = $d->format('l');
        }
        $affectedDays = array_unique($affectedDays);

        if (empty($affectedDays)) {
            return [];
        }

        $rows = self::activeSchedulesQuery()
            ->where('faculty_id', $facultyId)
            ->whereIn('day_of_week', $affectedDays)
            ->get(['grade_level', 'section_name', 'subject', 'day_of_week', 'start_time', 'end_time']);

        $sections = [];
        foreach ($rows as $row) {
            $key = ($row->grade_level ?? '') . '|' . ($row->section_name ?? '');
            if ($key === '|' || isset($sections[$key])) {
                continue;
            }
            $sections[$key] = [
                'grade_level' => $row->grade_level ?? '—',
                'section_name' => $row->section_name ?? '—',
                'subject' => $row->subject ?? '—',
                'day_of_week' => $row->day_of_week ?? '—',
                'time' => self::formatTimeRange($row->start_time, $row->end_time),
            ];
        }

        return array_values($sections);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ClassSchedule>
     */
    private static function activeSchedulesQuery()
    {
        return ClassSchedule::query()
            ->where('admin_approved', true)
            ->whereIn('status', ['active', 'approved']);
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @param  Collection<int, User>  $users
     * @return list<array<string, mixed>>
     */
    private static function detectFacultyConflicts(Collection $schedules, Collection $users): array
    {
        $groups = [];
        $indexed = $schedules->filter(fn ($s) => ! empty($s->faculty_id))->values();

        for ($i = 0; $i < $indexed->count(); $i++) {
            for ($j = $i + 1; $j < $indexed->count(); $j++) {
                $a = $indexed[$i];
                $b = $indexed[$j];
                if ((int) $a->faculty_id !== (int) $b->faculty_id) {
                    continue;
                }
                if (! self::sameDay($a->day_of_week, $b->day_of_week)) {
                    continue;
                }
                if (! self::timesOverlap($a->start_time, $a->end_time, $b->start_time, $b->end_time)) {
                    continue;
                }

                $key = min($a->id, $b->id) . ':' . max($a->id, $b->id);
                if (isset($groups[$key])) {
                    continue;
                }

                $faculty = $users->get((int) $a->faculty_id);
                $groups[$key] = [
                    'type' => 'faculty',
                    'faculty_id' => (int) $a->faculty_id,
                    'faculty_name' => self::userDisplayName($faculty, (int) $a->faculty_id),
                    'day' => $a->day_of_week,
                    'time' => self::formatTimeRange($a->start_time, $a->end_time) . ' overlaps ' . self::formatTimeRange($b->start_time, $b->end_time),
                    'rows' => [
                        self::scheduleRow($a, $users, collect()),
                        self::scheduleRow($b, $users, collect()),
                    ],
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @param  Collection<int, Room>  $rooms
     * @return list<array<string, mixed>>
     */
    private static function detectRoomConflicts(Collection $schedules, Collection $rooms): array
    {
        $groups = [];
        $indexed = $schedules->filter(fn ($s) => ! empty($s->room_id))->values();

        for ($i = 0; $i < $indexed->count(); $i++) {
            for ($j = $i + 1; $j < $indexed->count(); $j++) {
                $a = $indexed[$i];
                $b = $indexed[$j];
                if ((int) $a->room_id !== (int) $b->room_id) {
                    continue;
                }
                if (! self::sameDay($a->day_of_week, $b->day_of_week)) {
                    continue;
                }
                if (! self::timesOverlap($a->start_time, $a->end_time, $b->start_time, $b->end_time)) {
                    continue;
                }

                $key = min($a->id, $b->id) . ':' . max($a->id, $b->id);
                if (isset($groups[$key])) {
                    continue;
                }

                $room = $rooms->get((int) $a->room_id);
                $groups[$key] = [
                    'type' => 'room',
                    'room_id' => (int) $a->room_id,
                    'room_name' => $room?->room_number ?? ('Room #' . $a->room_id),
                    'day' => $a->day_of_week,
                    'time' => self::formatTimeRange($a->start_time, $a->end_time) . ' overlaps ' . self::formatTimeRange($b->start_time, $b->end_time),
                    'rows' => [
                        self::scheduleRow($a, collect(), $rooms),
                        self::scheduleRow($b, collect(), $rooms),
                    ],
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array{missing_schedule_date: list<array<string, mixed>>, missing_room: list<array<string, mixed>>}
     */
    private static function detectMissingData(Collection $schedules, Collection $users, Collection $rooms): array
    {
        $hasScheduleDate = Schema::connection((new ClassSchedule)->getConnectionName())
            ->hasColumn('class_schedules', 'schedule_date');

        $missingDate = [];
        $missingRoom = [];

        foreach ($schedules as $schedule) {
            if ($hasScheduleDate && empty($schedule->schedule_date)) {
                $missingDate[] = self::scheduleRow($schedule, $users, $rooms);
            }
            if (empty($schedule->room_id)) {
                $missingRoom[] = self::scheduleRow($schedule, $users, $rooms);
            }
        }

        return [
            'missing_schedule_date' => $missingDate,
            'missing_room' => $missingRoom,
        ];
    }

    /**
     * Cross-school shared-teacher time clashes and combined load limits.
     *
     * @return list<array<string, mixed>>
     */
    public static function buildSharedTeacherOverload(string $connection): array
    {
        $sharedIds = DB::connection($connection)->table('shared_teachers')
            ->where('is_active', true)
            ->pluck('faculty_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($sharedIds)) {
            $sharedIds = User::whereHas('role', fn ($q) => $q->where('name', 'shared_teacher'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (empty($sharedIds)) {
            return [];
        }

        $users = User::whereIn('id', $sharedIds)->get()->keyBy('id');
        $jhScheds = DB::connection('mysql_jh')->table('class_schedules')
            ->whereIn('faculty_id', $sharedIds)
            ->whereIn('status', ['active', 'approved', 'pending'])
            ->get()
            ->groupBy('faculty_id');
        $gsScheds = DB::connection('mysql_gs')->table('class_schedules')
            ->whereIn('faculty_id', $sharedIds)
            ->whereIn('status', ['active', 'approved', 'pending'])
            ->get()
            ->groupBy('faculty_id');

        $jhLoads = DB::connection('mysql_jh')->table('faculty_loads')
            ->whereIn('faculty_id', $sharedIds)
            ->selectRaw('faculty_id, SUM(load_hours) as hours')
            ->groupBy('faculty_id')
            ->pluck('hours', 'faculty_id');
        $gsLoads = DB::connection('mysql_gs')->table('faculty_loads')
            ->whereIn('faculty_id', $sharedIds)
            ->selectRaw('faculty_id, SUM(load_hours) as hours')
            ->groupBy('faculty_id')
            ->pluck('hours', 'faculty_id');

        $defaultMaxHours = 16.0;
        $result = [];

        foreach ($sharedIds as $id) {
            $user = $users->get($id);
            $jhHours = (float) ($jhLoads[$id] ?? 0);
            $gsHours = (float) ($gsLoads[$id] ?? 0);
            $total = $jhHours + $gsHours;
            $status = $total > $defaultMaxHours ? 'overloaded' : ($total > $defaultMaxHours * 0.8 ? 'near_limit' : 'ok');

            $conflicts = [];
            foreach ($jhScheds[$id] ?? [] as $jhs) {
                foreach ($gsScheds[$id] ?? [] as $gss) {
                    if (! self::sameDay($jhs->day_of_week ?? '', $gss->day_of_week ?? '')) {
                        continue;
                    }
                    if (! self::timesOverlap($jhs->start_time, $jhs->end_time, $gss->start_time, $gss->end_time)) {
                        continue;
                    }
                    $conflicts[] = [
                        'day' => $jhs->day_of_week,
                        'jh' => ($jhs->subject ?? '') . ' · ' . ($jhs->section_name ?? '') . ' (' . self::formatTimeRange($jhs->start_time, $jhs->end_time) . ')',
                        'gs' => ($gss->subject ?? '') . ' · ' . ($gss->section_name ?? '') . ' (' . self::formatTimeRange($gss->start_time, $gss->end_time) . ')',
                    ];
                }
            }

            $result[] = [
                'faculty_id' => $id,
                'name' => self::userDisplayName($user, $id),
                'jh_hours' => round($jhHours, 1),
                'gs_hours' => round($gsHours, 1),
                'total_hours' => round($total, 1),
                'max_hours' => $defaultMaxHours,
                'status' => $status,
                'conflicts' => $conflicts,
            ];
        }

        usort($result, fn ($a, $b) => (count($b['conflicts']) <=> count($a['conflicts']))
            ?: (($b['status'] === 'overloaded' ? 1 : 0) <=> ($a['status'] === 'overloaded' ? 1 : 0)));

        return $result;
    }

    /**
     * @return array{dept_average: float, teachers: list<array<string, mixed>>}
     */
    public static function buildWorkloadFairness(string $connection, string $schoolLevel): array
    {
        $loads = FacultyLoad::query()->get();
        $byFaculty = [];
        foreach ($loads as $load) {
            $fid = (int) ($load->faculty_id ?? 0);
            if ($fid <= 0) {
                continue;
            }
            if (! isset($byFaculty[$fid])) {
                $byFaculty[$fid] = [
                    'faculty_id' => $fid,
                    'name' => $load->teacher_name ?? ('Teacher #' . $fid),
                    'load_hours' => 0.0,
                ];
            }
            $byFaculty[$fid]['load_hours'] += (float) ($load->load_hours ?? 0);
        }

        $userIds = array_keys($byFaculty);
        if (! empty($userIds)) {
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($byFaculty as $fid => &$row) {
                $user = $users->get($fid);
                if ($user) {
                    $row['name'] = self::userDisplayName($user, $fid);
                }
                $row['load_hours'] = round($row['load_hours'], 1);
            }
            unset($row);
        }

        $teachers = array_values($byFaculty);
        usort($teachers, fn ($a, $b) => $b['load_hours'] <=> $a['load_hours']);

        $hours = array_column($teachers, 'load_hours');
        $avg = count($hours) > 0 ? array_sum($hours) / count($hours) : 0.0;
        $threshold = max(4.0, $avg * 0.35);

        foreach ($teachers as &$t) {
            $diff = $t['load_hours'] - $avg;
            $t['vs_average'] = round($diff, 1);
            $t['is_outlier'] = abs($diff) >= $threshold && $avg > 0;
            $t['outlier_type'] = $diff > 0 ? 'high' : ($diff < 0 ? 'low' : 'normal');
        }
        unset($t);

        return [
            'dept_average' => round($avg, 1),
            'outlier_threshold' => round($threshold, 1),
            'teachers' => $teachers,
        ];
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array{columns: list<string>, rows: list<array<string, mixed>>}
     */
    private static function buildTeacherUtilizationHeatmap(Collection $schedules, Collection $users, string $schoolLevel): array
    {
        $slotsByDay = SchoolScheduleSlots::classSlotsByDayForSchoolLevel($schoolLevel);
        $columns = [];
        foreach (SchoolScheduleSlots::WEEKDAYS as $day) {
            $daySlots = $slotsByDay[$day] ?? $slotsByDay['default'] ?? [];
            foreach ($daySlots as $slot) {
                $columns[] = [
                    'key' => $day . '|' . $slot['start'],
                    'label' => substr($day, 0, 3) . ' ' . ($slot['label'] ?? $slot['start']),
                    'day' => $day,
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                ];
            }
        }

        $byTeacher = $schedules->filter(fn ($s) => ! empty($s->faculty_id))->groupBy('faculty_id');
        $rows = [];
        $count = 0;
        foreach ($byTeacher as $facultyId => $teacherScheds) {
            if ($count >= 20) {
                break;
            }
            $cells = [];
            foreach ($columns as $col) {
                $filled = $teacherScheds->contains(function ($s) use ($col) {
                    return self::sameDay($s->day_of_week, $col['day'])
                        && self::scheduleFillsSlot($s, $col['start'], $col['end']);
                });
                $cells[$col['key']] = $filled ? 'full' : 'empty';
            }
            $faculty = $users->get((int) $facultyId);
            $rows[] = [
                'id' => (int) $facultyId,
                'name' => self::userDisplayName($faculty, (int) $facultyId),
                'cells' => $cells,
            ];
            $count++;
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array{columns: list<string>, rows: list<array<string, mixed>>}
     */
    private static function buildRoomUtilizationHeatmap(Collection $schedules, Collection $rooms, string $schoolLevel): array
    {
        $slotsByDay = SchoolScheduleSlots::classSlotsByDayForSchoolLevel($schoolLevel);
        $columns = [];
        foreach (SchoolScheduleSlots::WEEKDAYS as $day) {
            $daySlots = $slotsByDay[$day] ?? $slotsByDay['default'] ?? [];
            foreach ($daySlots as $slot) {
                $columns[] = [
                    'key' => $day . '|' . $slot['start'],
                    'label' => substr($day, 0, 3) . ' ' . ($slot['label'] ?? $slot['start']),
                    'day' => $day,
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                ];
            }
        }

        $byRoom = $schedules->filter(fn ($s) => ! empty($s->room_id))->groupBy('room_id');
        $rows = [];
        $count = 0;
        foreach ($byRoom as $roomId => $roomScheds) {
            if ($count >= 15) {
                break;
            }
            $cells = [];
            foreach ($columns as $col) {
                $filled = $roomScheds->contains(function ($s) use ($col) {
                    return self::sameDay($s->day_of_week, $col['day'])
                        && self::scheduleFillsSlot($s, $col['start'], $col['end']);
                });
                $cells[$col['key']] = $filled ? 'full' : 'empty';
            }
            $room = $rooms->get((int) $roomId);
            $rows[] = [
                'id' => (int) $roomId,
                'name' => $room?->room_number ?? ('Room #' . $roomId),
                'cells' => $cells,
            ];
            $count++;
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    private static function scheduleRow(ClassSchedule $schedule, Collection $users, Collection $rooms): array
    {
        $faculty = $users->get((int) $schedule->faculty_id);
        $room = $rooms->get((int) $schedule->room_id);

        return [
            'id' => $schedule->id,
            'subject' => $schedule->subject ?? '—',
            'grade_level' => $schedule->grade_level ?? '—',
            'section_name' => $schedule->section_name ?? '—',
            'faculty_name' => self::userDisplayName($faculty, (int) $schedule->faculty_id),
            'room' => $room?->room_number ?? '—',
            'day_of_week' => $schedule->day_of_week ?? '—',
            'schedule_date' => $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : null,
            'time' => self::formatTimeRange($schedule->start_time, $schedule->end_time),
            'status' => $schedule->status ?? '—',
        ];
    }

    /** @param  list<int>  $ids */
    private static function resolveUsers(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return User::whereIn('id', $ids)->get()->keyBy('id');
    }

    /** @param  list<int>  $ids */
    private static function resolveRooms(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return Room::whereIn('id', $ids)->get()->keyBy('id');
    }

    private static function userDisplayName(?User $user, int $id): string
    {
        if (! $user) {
            return $id > 0 ? 'Teacher #' . $id : 'Unassigned';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->name ?? 'Teacher #' . $id);
    }

    private static function sameDay(?string $a, ?string $b): bool
    {
        if (! $a || ! $b) {
            return false;
        }

        return strcasecmp($a, $b) === 0;
    }

    private static function timesOverlap($aStart, $aEnd, $bStart, $bEnd): bool
    {
        $aS = self::timeToComparable($aStart);
        $aE = self::timeToComparable($aEnd);
        $bS = self::timeToComparable($bStart);
        $bE = self::timeToComparable($bEnd);

        if ($aS === '' || $aE === '' || $bS === '' || $bE === '') {
            return false;
        }

        return $aS < $bE && $bS < $aE;
    }

    private static function scheduleFillsSlot(ClassSchedule $schedule, string $slotStart, string $slotEnd): bool
    {
        return self::timesOverlap($schedule->start_time, $schedule->end_time, $slotStart, $slotEnd);
    }

    private static function timeToComparable($time): string
    {
        if (empty($time)) {
            return '';
        }

        return substr((string) $time, 0, 5);
    }

    private static function formatTimeRange($start, $end): string
    {
        $s = self::timeToComparable($start);
        $e = self::timeToComparable($end);

        return ($s && $e) ? "{$s}–{$e}" : '—';
    }
}
