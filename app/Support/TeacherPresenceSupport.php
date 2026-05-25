<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherPresenceSupport
{
    /** Form/API values for leave requests (maps to teacher_leave_requests.leave_type). */
    public const ABSENCE_LEAVE_TYPES = [
        'absent',
        'sick_leave',
        'vacation_leave',
        'emergency_leave',
        'official_business',
        'leave_other',
    ];

    /** Stored in teacher_requests.request_type for schedule adjustments (not leave). */
    public const SCHEDULE_ADJUSTMENT_TYPES = [
        'time_change',
        'room_change',
        'teacher_reassignment',
        'section_change',
        'schedule_change',
        'other',
    ];

    public static function isScheduleAdjustmentType(?string $type): bool
    {
        return in_array(trim((string) $type), self::SCHEDULE_ADJUSTMENT_TYPES, true);
    }

    public static function isAbsenceLeaveType(?string $type): bool
    {
        $type = trim((string) $type);
        if ($type === '' || self::isScheduleAdjustmentType($type)) {
            return false;
        }

        return in_array($type, self::ABSENCE_LEAVE_TYPES, true);
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'absent'             => 'Absent',
            'sick_leave'         => 'Sick Leave',
            'vacation_leave'     => 'Vacation Leave',
            'emergency_leave'    => 'Emergency Leave',
            'official_business'  => 'Official Business',
            'leave_other', 'other' => 'Other Leave',
            default              => $type ? ucfirst(str_replace('_', ' ', (string) $type)) : 'Leave',
        };
    }

    /**
     * Active approved absence/leave covering today (from teacher_leave_requests).
     *
     * @return array{status: string, label: string, request_type: string, date_from: string, date_to: string}|null
     */
    public static function activeStatusForTeacher(string $connection, int $facultyId, ?Carbon $onDate = null): ?array
    {
        if ($facultyId <= 0) {
            return null;
        }

        $today = ($onDate ?? now())->toDateString();

        if (Schema::connection($connection)->hasTable(TeacherLeaveRequestSupport::TABLE)) {
            $row = DB::connection($connection)
                ->table(TeacherLeaveRequestSupport::TABLE)
                ->where('teacher_id', $facultyId)
                ->where('status', 'approved')
                ->where('date_from', '<=', $today)
                ->where('date_to', '>=', $today)
                ->orderByDesc('date_to')
                ->first();

            if ($row) {
                $type = (string) ($row->leave_type ?? 'other');

                return [
                    'status'       => $type === 'absent' ? 'absent' : 'on_leave',
                    'label'        => $type === 'absent' ? 'Absent' : 'On Leave',
                    'request_type' => $type,
                    'date_from'    => substr((string) $row->date_from, 0, 10),
                    'date_to'      => substr((string) $row->date_to, 0, 10),
                ];
            }
        }

        return self::activeStatusFromLegacyTeacherRequests($connection, $facultyId, $today);
    }

    /**
     * @param  array<int>  $facultyIds
     * @return array<int, array{status: string, label: string, request_type?: string, date_from?: string, date_to?: string}>
     */
    public static function activeStatusMapForTeachers(string $connection, array $facultyIds): array
    {
        $map = [];
        foreach (array_unique(array_filter(array_map('intval', $facultyIds))) as $id) {
            $status = self::activeStatusForTeacher($connection, $id);
            if ($status) {
                $map[$id] = $status;
            }
        }

        return $map;
    }

    /** Legacy rows still in teacher_requests before migration. */
    private static function activeStatusFromLegacyTeacherRequests(string $connection, int $facultyId, string $today): ?array
    {
        if (! Schema::connection($connection)->hasTable('teacher_requests')) {
            return null;
        }

        $legacyTypes = ['absent', 'sick_leave', 'vacation_leave', 'emergency_leave', 'official_business', 'leave_other'];

        $rows = DB::connection($connection)
            ->table('teacher_requests')
            ->where('faculty_id', $facultyId)
            ->where('status', 'approved')
            ->whereIn('request_type', $legacyTypes)
            ->orderByDesc('created_at')
            ->get();

        foreach ($rows as $candidate) {
            $from = ! empty($candidate->date_from)
                ? substr((string) $candidate->date_from, 0, 10)
                : null;
            $to = ! empty($candidate->date_to)
                ? substr((string) $candidate->date_to, 0, 10)
                : null;
            if (! $from || ! $to) {
                $parsed = TeacherAdjustmentRequestSupport::parseProposed($candidate->proposed_changes ?? null);
                $from = $from ?? (! empty($parsed['date_from']) ? substr((string) $parsed['date_from'], 0, 10) : null);
                $to = $to ?? (! empty($parsed['date_to']) ? substr((string) $parsed['date_to'], 0, 10) : null);
            }
            if ($from && $to && $from <= $today && $to >= $today) {
                $type = (string) ($candidate->request_type ?? 'other');

                return [
                    'status'       => $type === 'absent' ? 'absent' : 'on_leave',
                    'label'        => $type === 'absent' ? 'Absent' : 'On Leave',
                    'request_type' => $type,
                    'date_from'    => $from,
                    'date_to'      => $to,
                ];
            }
        }

        return null;
    }

    /**
     * Approved leave/absence rows covering today for admin banners.
     *
     * @return array{regular: array<int, array<string, mixed>>, shared: array<int, array<string, mixed>>}
     */
    public static function collectActiveLeaveBannerData(string $connection, array $sharedTeacherIds = []): array
    {
        $sharedSet = array_flip(array_map('intval', $sharedTeacherIds));
        $regular = [];
        $shared = [];

        if (! Schema::connection($connection)->hasTable(TeacherLeaveRequestSupport::TABLE)) {
            return ['regular' => [], 'shared' => []];
        }

        $today = now()->toDateString();
        $rows = DB::connection($connection)
            ->table(TeacherLeaveRequestSupport::TABLE)
            ->where('status', 'approved')
            ->where('date_from', '<=', $today)
            ->where('date_to', '>=', $today)
            ->orderBy('date_from')
            ->get();

        $userIds = $rows->pluck('teacher_id')->unique()->filter();
        $users = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        foreach ($rows as $row) {
            $tid = (int) $row->teacher_id;
            $user = $users->get($tid);
            $name = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher')
                : 'Teacher #' . $tid;
            $from = substr((string) $row->date_from, 0, 10);
            $to = substr((string) $row->date_to, 0, 10);
            $totalDays = (int) ($row->total_days ?? 0);
            if ($totalDays <= 0 && $from && $to) {
                try {
                    $totalDays = max(1, Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
                } catch (\Throwable) {
                    $totalDays = 1;
                }
            }
            $type = (string) ($row->leave_type ?? 'other');
            $entry = [
                'id'          => $tid,
                'name'        => $name,
                'label'       => $type === 'absent' ? 'Absent' : 'On Leave',
                'type'        => $type,
                'status'      => $type === 'absent' ? 'absent' : 'on_leave',
                'date_from'   => $from,
                'date_to'     => $to,
                'total_days'  => $totalDays,
                'date_range'  => TeacherLeaveRequestSupport::formatDateRangeLabel($from, $to),
            ];
            if (isset($sharedSet[$tid])) {
                $shared[$tid] = $entry;
            } else {
                $regular[$tid] = $entry;
            }
        }

        return ['regular' => array_values($regular), 'shared' => array_values($shared)];
    }

    /**
     * Faculty load status when teacher is on approved leave (overrides schedule-based availability).
     */
    public static function applyPresenceToFacultyLoadRow(array $data, ?array $presence): array
    {
        if (! $presence) {
            return $data;
        }

        $data['presence_status'] = $presence['status'] ?? null;
        $data['presence_label'] = $presence['label'] ?? null;
        $data['status'] = 'unavailable';
        $days = (int) ($presence['total_days'] ?? 0);
        if ($days <= 0 && ! empty($presence['date_from']) && ! empty($presence['date_to'])) {
            $days = max(1, Carbon::parse($presence['date_from'])->diffInDays(Carbon::parse($presence['date_to'])) + 1);
        }
        $data['availability_note'] = ($presence['label'] ?? 'On Leave')
            . ($days > 0 ? " ({$days} day" . ($days === 1 ? '' : 's') . ')' : '')
            . (! empty($presence['date_to']) ? ' until ' . Carbon::parse($presence['date_to'])->format('M j, Y') : '');

        return $data;
    }

    /**
     * Enrich active status with total_days for API consumers.
     *
     * @return array<string, mixed>|null
     */
    public static function activeStatusForTeacherWithDays(string $connection, int $facultyId, ?Carbon $onDate = null): ?array
    {
        $status = self::activeStatusForTeacher($connection, $facultyId, $onDate);
        if (! $status) {
            return null;
        }

        if (Schema::connection($connection)->hasTable(TeacherLeaveRequestSupport::TABLE)) {
            $today = ($onDate ?? now())->toDateString();
            $row = DB::connection($connection)
                ->table(TeacherLeaveRequestSupport::TABLE)
                ->where('teacher_id', $facultyId)
                ->where('status', 'approved')
                ->where('date_from', '<=', $today)
                ->where('date_to', '>=', $today)
                ->orderByDesc('date_to')
                ->first();
            if ($row) {
                $totalDays = (int) ($row->total_days ?? 0);
                if ($totalDays <= 0) {
                    $totalDays = max(1, Carbon::parse($row->date_from)->diffInDays(Carbon::parse($row->date_to)) + 1);
                }
                $status['total_days'] = $totalDays;
            }
        }

        return $status;
    }
}
