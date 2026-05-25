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

    public static function countPendingForTeacher(int $facultyId): int
    {
        return (int) self::listForTeacher($facultyId)->where('status', 'pending')->count();
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
