<?php

namespace App\Support;

use App\Models\FacultyLoad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScheduleDssSupport
{
    public const OVERLOAD_HOURS_THRESHOLD = 30.0;

    /**
     * Inline slot assistant + teacher adjustment check.
     *
     * @return array{
     *   official_period: bool,
     *   valid_slot: bool,
     *   messages: list<array{type: string, text: string}>,
     *   conflicts: list<array<string, string>>
     * }
     */
    public static function assessSlot(
        string $connection,
        string $schoolLevel,
        ?string $dayOfWeek,
        ?string $startTime,
        ?string $endTime,
        ?int $facultyId = null,
        ?string $scheduleDate = null,
        ?int $excludeScheduleId = null,
    ): array {
        $dayOfWeek = trim((string) $dayOfWeek);
        $start = SchoolScheduleSlots::normalizeHi($startTime);
        $end = SchoolScheduleSlots::normalizeHi($endTime);

        $messages = [];
        $conflicts = [];
        $official = false;

        if ($dayOfWeek !== '' && $start !== '' && $end !== '') {
            $official = SchoolScheduleSlots::isValidClassSlot($schoolLevel, $start, $end, $dayOfWeek);
            $levelLabel = $schoolLevel === 'grade_school' ? 'Grade School' : 'Junior High';
            if ($official) {
                $slot = SchoolScheduleSlots::matchClassSlot($schoolLevel, $start, $end, $dayOfWeek);
                $label = $slot['label'] ?? "{$start}–{$end}";
                $messages[] = ['type' => 'ok', 'text' => "Official {$levelLabel} period ({$label})"];
            } else {
                $messages[] = ['type' => 'warn', 'text' => 'Selected time is not an official class period for this school level'];
            }
        }

        if ($facultyId && $facultyId > 0 && $dayOfWeek !== '' && $start !== '' && $end !== '') {
            foreach (self::facultyTimeConflicts($connection, $facultyId, $dayOfWeek, $start, $end, $excludeScheduleId) as $c) {
                $messages[] = ['type' => 'warn', 'text' => 'Teacher already has class at this time'];
                $conflicts[] = $c;
                break;
            }
        }

        if ($facultyId && $facultyId > 0 && $scheduleDate) {
            $presence = TeacherPresenceSupport::activeStatusForTeacher(
                $connection,
                $facultyId,
                Carbon::parse($scheduleDate)
            );
            if ($presence) {
                $messages[] = [
                    'type' => 'warn',
                    'text' => 'Selected teacher is on approved leave for ' . Carbon::parse($scheduleDate)->format('M d, Y')
                        . ' (' . ($presence['label'] ?? 'On Leave') . ')',
                ];
            }
        }

        return [
            'official_period' => $official,
            'valid_slot'      => $official,
            'messages'        => $messages,
            'conflicts'       => $conflicts,
        ];
    }

    /**
     * @return list<array{subject: string, time: string, section: string}>
     */
    public static function facultyTimeConflicts(
        string $connection,
        int $facultyId,
        string $dayOfWeek,
        string $start,
        string $end,
        ?int $excludeScheduleId = null,
    ): array {
        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            return [];
        }

        $query = DB::connection($connection)->table('class_schedules')
            ->where('faculty_id', $facultyId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) {
                $q->where('admin_approved', true)
                    ->orWhereIn('status', ['active', 'approved', 'pending']);
            })
            ->whereNotIn('status', ['cancelled', 'deleted', 'rejected']);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $conflicts = [];
        foreach ($query->get() as $row) {
            if (! self::timesOverlap($row->start_time, $row->end_time, $start, $end)) {
                continue;
            }
            $conflicts[] = [
                'subject' => (string) ($row->subject ?? 'Class'),
                'time'    => self::formatTimeRange($row->start_time, $row->end_time),
                'section' => trim(((string) ($row->grade_level ?? '')) . ' ' . ((string) ($row->section_name ?? ''))),
            ];
        }

        return $conflicts;
    }

    /**
     * Pre-save faculty load warnings.
     *
     * @return list<string>
     */
    public static function assessFacultyLoadSave(
        string $connection,
        string $schoolLevel,
        int $facultyId,
        float $projectedLoadHours,
        ?int $excludeLoadId = null,
    ): array {
        $warnings = [];

        $prev = config('database.school_connection');
        config(['database.school_connection' => $connection]);

        $existingHours = (float) FacultyLoad::query()
            ->where('faculty_id', $facultyId)
            ->when($excludeLoadId, fn ($q) => $q->where('id', '!=', $excludeLoadId))
            ->sum('load_hours');

        $total = $existingHours + $projectedLoadHours;
        if ($total > self::OVERLOAD_HOURS_THRESHOLD) {
            $warnings[] = 'Overload warning: projected load hours would be '
                . number_format($total, 1) . ' (limit ' . (int) self::OVERLOAD_HOURS_THRESHOLD . ' h).';
        }

        if (FacultyLoadSupport::isSharedTeacher($facultyId)) {
            $count = FacultyLoadSupport::countLoadsForTeacher($facultyId);
            if ($excludeLoadId) {
                $count = max(0, $count - 1);
            }
            if ($count >= FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS) {
                $warnings[] = 'Shared teacher already has '
                    . $count . ' load assignments (maximum ' . FacultyLoadSupport::SHARED_TEACHER_MAX_LOADS . ').';
            }
        }

        $presence = TeacherPresenceSupport::activeStatusForTeacher($connection, $facultyId);
        if ($presence) {
            $warnings[] = 'Teacher is on approved leave ('
                . ($presence['date_from'] ?? '') . ' – ' . ($presence['date_to'] ?? '')
                . '). Avoid assigning new load during this period.';
        }

        $teacherName = null;
        $dup = FacultyLoadSupport::facultyLoadConflictMessage($facultyId, $teacherName, null, null, $excludeLoadId);
        if ($dup) {
            $warnings[] = $dup;
        }

        config(['database.school_connection' => $prev]);

        return $warnings;
    }

    /**
     * Admin request approval impact (human-in-the-loop).
     *
     * @return array{
     *   summary: array<string, string>,
     *   warnings: list<string>,
     *   substitutes: list<array{id: int, name: string, score: int, kind: string}>
     * }
     */
    public static function assessRequestImpact(
        string $connection,
        string $schoolLevel,
        object $row,
    ): array {
        $payload = TeacherAdjustmentRequestSupport::payloadFromRequestRow($row);
        $view = AdminRequestDisplay::teacherRequestView($row);

        $summary = [
            'preferred_date' => $payload['preferred_date'] ?? ($view['adjustment_date'] ?? ''),
            'day'            => $payload['day_of_week'] ?? ($view['day'] ?? ''),
            'time'           => $view['time_range'] ?? '',
            'subject'        => $payload['subject'] ?? ($view['subject'] ?? ''),
            'grade_section'  => $view['grade_section'] ?? '',
        ];

        $warnings = [];
        $day = trim((string) ($summary['day'] ?? ''));
        $start = SchoolScheduleSlots::normalizeHi($payload['preferred_start_time'] ?? null);
        $end = SchoolScheduleSlots::normalizeHi($payload['preferred_end_time'] ?? null);
        $facultyId = (int) ($row->faculty_id ?? $row->requested_by ?? 0);
        $date = $payload['preferred_date'] ?? null;

        if ($day !== '' && $start !== '' && $end !== '') {
            $slot = self::assessSlot($connection, $schoolLevel, $day, $start, $end, $facultyId ?: null, $date);
            foreach ($slot['messages'] as $m) {
                if (($m['type'] ?? '') === 'warn') {
                    $warnings[] = $m['text'];
                }
            }
            foreach ($slot['conflicts'] as $c) {
                $warnings[] = 'Faculty time conflict with ' . ($c['subject'] ?? 'class')
                    . ' (' . ($c['time'] ?? '') . ')';
            }
        }

        $substitutes = [];
        $type = (string) ($row->request_type ?? '');
        if ($type === 'teacher_reassignment' && ! empty($payload['subject'])) {
            $substitutes = self::rankedSubstitutesForReassignment(
                $connection,
                $schoolLevel,
                $facultyId,
                (string) $payload['subject'],
                $payload['grade_level'] ?? null,
                $day,
                $start,
                $end,
            );
        }

        return [
            'summary'      => $summary,
            'warnings'     => array_values(array_unique($warnings)),
            'substitutes'  => $substitutes,
        ];
    }

    /**
     * @return list<array{id: int, name: string, score: int, kind: string}>
     */
    public static function rankedSubstitutesForReassignment(
        string $connection,
        string $schoolLevel,
        int $excludeFacultyId,
        string $subject,
        ?string $gradeLevel,
        string $day,
        string $start,
        string $end,
    ): array {
        $candidates = TeacherAdjustmentRequestSupport::availableTeachersForReassignment(
            $connection,
            $excludeFacultyId,
            $subject,
            $gradeLevel
        );

        $prevConn = config('database.school_connection');
        config(['database.school_connection' => $connection]);

        $scored = [];
        foreach ($candidates as $c) {
            $fid = (int) ($c['id'] ?? 0);
            if ($fid <= 0) {
                continue;
            }
            $score = 0;
            if (($c['kind'] ?? '') === 'teacher') {
                $score += 10;
            }
            $hours = (float) FacultyLoad::query()->where('faculty_id', $fid)->sum('load_hours');
            if ($hours <= 16) {
                $score += 20;
            } elseif ($hours <= 24) {
                $score += 10;
            }
            if ($day !== '' && $start !== '' && $end !== '') {
                if (empty(self::facultyTimeConflicts($connection, $fid, $day, $start, $end))) {
                    $score += 30;
                } else {
                    $score -= 50;
                }
            } else {
                $score += 5;
            }
            $scored[] = [
                'id'    => $fid,
                'name'  => $c['name'] ?? ('Teacher #' . $fid),
                'kind'  => $c['kind'] ?? 'teacher',
                'score' => $score,
            ];
        }

        config(['database.school_connection' => $prevConn]);

        usort($scored, fn ($a, $b) => ($b['score'] <=> $a['score']) ?: strcmp($a['name'], $b['name']));

        return array_slice($scored, 0, 8);
    }

    /**
     * Principal: schedules awaiting approval with policy flags.
     *
     * @return array{total_pending: int, with_policy_flags: int}
     */
    public static function principalPendingScheduleFlags(): array
    {
        $total = 0;
        $flagged = 0;

        foreach (['mysql_jh' => 'junior_high', 'mysql_gs' => 'grade_school'] as $conn => $level) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }
            $rows = DB::connection($conn)->table('class_schedules')
                ->where('admin_approved', 1)
                ->where('principal_approved', 0)
                ->whereNotIn('status', ['cancelled', 'deleted', 'rejected'])
                ->get();

            foreach ($rows as $row) {
                $total++;
                if (self::scheduleHasPolicyFlag($conn, $level, $row)) {
                    $flagged++;
                }
            }
        }

        return ['total_pending' => $total, 'with_policy_flags' => $flagged];
    }

    public static function scheduleHasPolicyFlag(string $connection, string $schoolLevel, object $row): bool
    {
        if (empty($row->room_id)) {
            return true;
        }

        $day = trim((string) ($row->day_of_week ?? ''));
        $start = SchoolScheduleSlots::normalizeHi($row->start_time ?? null);
        $end = SchoolScheduleSlots::normalizeHi($row->end_time ?? null);
        if ($day !== '' && $start !== '' && $end !== '' && ! SchoolScheduleSlots::isValidClassSlot($schoolLevel, $start, $end, $day)) {
            return true;
        }

        $fid = (int) ($row->faculty_id ?? 0);
        if ($fid > 0 && $day !== '' && $start !== '' && $end !== '') {
            $others = self::facultyTimeConflicts($connection, $fid, $day, $start, $end, (int) ($row->id ?? 0));
            if (count($others) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function conflictSuggestedAction(array $conflict): string
    {
        $type = (string) ($conflict['type'] ?? '');
        if (in_array($type, ['room_double_booking', 'no_room'], true)) {
            return 'Change room';
        }
        if (in_array($type, ['teacher_double_booking', 'teacher_busy'], true)) {
            return 'Move to next free slot';
        }

        return 'Review and adjust slot';
    }

    private static function timesOverlap($aStart, $aEnd, $bStart, $bEnd): bool
    {
        $aS = self::timeToComparable($aStart);
        $aE = self::timeToComparable($aEnd);
        $bS = self::timeToComparable($bStart);
        $bE = self::timeToComparable($bEnd);

        return $aS !== '' && $aE !== '' && $bS !== '' && $bE !== '' && $aS < $bE && $bS < $aE;
    }

    private static function timeToComparable($time): string
    {
        return empty($time) ? '' : substr((string) $time, 0, 5);
    }

    private static function formatTimeRange($start, $end): string
    {
        $s = self::timeToComparable($start);
        $e = self::timeToComparable($end);

        return ($s && $e) ? "{$s}–{$e}" : '—';
    }
}
