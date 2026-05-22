<?php

namespace App\Support;

use Carbon\Carbon;
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
        'other',
    ];

    public static function isAbsenceLeaveType(?string $type): bool
    {
        $type = TeacherLeaveRequestSupport::normalizeLeaveType((string) $type);

        return in_array($type, TeacherLeaveRequestSupport::LEAVE_TYPES, true)
            || in_array((string) $type, self::ABSENCE_LEAVE_TYPES, true);
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
}
