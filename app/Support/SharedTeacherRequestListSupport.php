<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * List/filter shared-teacher requests without duplicate peer-mirror rows.
 */
class SharedTeacherRequestListSupport
{
    public const TABLE = 'shared_teacher_requests';

    /**
     * Teacher-facing history: one row per logical request (not JH+GS mirror pair).
     */
    public static function listForTeacher(int $facultyId): Collection
    {
        $rows = collect();

        foreach (['mysql_jh' => 'jh', 'mysql_gs' => 'gs'] as $connection => $levelTag) {
            if (! Schema::connection($connection)->hasTable(self::TABLE)) {
                continue;
            }

            DB::connection($connection)->table(self::TABLE)
                ->where('faculty_id', $facultyId)
                ->orderByDesc('created_at')
                ->get()
                ->each(function ($row) use ($connection, $levelTag, &$rows) {
                    $row->level = $levelTag;
                    $row->_connection = $connection;
                    if (! self::isHiddenMirrorForTeacher($row, $connection)) {
                        $rows->push(self::enrichRow($row, $connection));
                    }
                });
        }

        return $rows->sortByDesc('created_at')->values();
    }

    /**
     * Admin queue: only rows this division should act on (one per pair per DB).
     */
    public static function listForAdmin(string $connection, array $facultyIds): Collection
    {
        if (! Schema::connection($connection)->hasTable(self::TABLE)) {
            return collect();
        }

        $levelTag = $connection === 'mysql_jh' ? 'jh' : 'gs';
        $expectedSchool = $connection === 'mysql_jh' ? 'junior_high' : 'grade_school';

        return DB::connection($connection)->table(self::TABLE)
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($row) use ($facultyIds, $levelTag, $expectedSchool) {
                if (! in_array((int) ($row->faculty_id ?? 0), $facultyIds, true)) {
                    return false;
                }

                return ($row->school_level ?? '') === $expectedSchool
                    || empty($row->pair_key);
            })
            ->map(fn ($row) => self::enrichRow($row, $connection))
            ->values();
    }

    /**
     * Absence/leave requests from both school databases.
     */
    public static function listLeaveForTeacher(int $facultyId): Collection
    {
        $rows = collect();

        foreach (['mysql_jh' => 'jh', 'mysql_gs' => 'gs'] as $connection => $levelTag) {
            if (! Schema::connection($connection)->hasTable(TeacherLeaveRequestSupport::TABLE)) {
                continue;
            }

            try {
                $items = DB::connection($connection)
                    ->table(TeacherLeaveRequestSupport::TABLE)
                    ->where('teacher_id', $facultyId)
                    ->orderByDesc('created_at')
                    ->get();
            } catch (\Throwable) {
                continue;
            }

            foreach ($items as $row) {
                $row->level = $levelTag;
                $row->_connection = $connection;
                $row->request_kind = 'leave';
                $row->leave_type_label = TeacherLeaveRequestSupport::leaveTypeLabel($row->leave_type ?? null);
                $rows->push($row);
            }
        }

        return $rows->sortByDesc(fn ($r) => (string) ($r->created_at ?? ''))->values();
    }

    public static function countPendingForTeacher(int $facultyId): int
    {
        try {
            $schedulePending = (int) self::listForTeacher($facultyId)->where('status', 'pending')->count();
            $leavePending = (int) self::listLeaveForTeacher($facultyId)->where('status', 'pending')->count();

            return $schedulePending + $leavePending;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Combined schedule + leave history for shared-teacher "My Request" page.
     */
    public static function listAllForTeacher(int $facultyId): Collection
    {
        $schedule = self::listForTeacher($facultyId)->map(function ($row) {
            $row->request_kind = 'schedule';

            return $row;
        });

        return $schedule
            ->concat(self::listLeaveForTeacher($facultyId))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Admin-only mirror row (dual approval copy) — hidden from teacher history.
     */
    public static function isHiddenMirrorForTeacher(object $row, string $connection): bool
    {
        if (Schema::connection($connection)->hasColumn(self::TABLE, 'is_peer_mirror')) {
            return (bool) ($row->is_peer_mirror ?? false);
        }

        return false;
    }

    public static function enrichRow(object $row, string $connection): object
    {
        if (empty($row->schedule_date) && ! empty($row->schedule_id)
            && Schema::connection($connection)->hasTable('class_schedules')) {
            $date = DB::connection($connection)->table('class_schedules')
                ->where('id', (int) $row->schedule_id)
                ->value('schedule_date');
            if ($date) {
                $row->schedule_date = substr((string) $date, 0, 10);
            }
        }

        if (empty($row->schedule_date) && ! empty($row->day_of_week)) {
            $row->schedule_date_label = 'Recurring (' . $row->day_of_week . ')';
        }

        return $row;
    }
}
