<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe access to shared_teachers on school DB connections (mysql_jh / mysql_gs).
 * Tables may be absent until migrations have been run on each database.
 */
class SharedTeacherSupport
{
    public static function tableExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('shared_teachers');
    }

    /**
     * @return list<int|string>
     */
    public static function activeFacultyIds(string $connection): array
    {
        if (! self::tableExists($connection)) {
            return [];
        }

        return DB::connection($connection)->table('shared_teachers')
            ->where('is_active', true)
            ->pluck('faculty_id')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    public static function activeList(string $connection, array $columns = ['id', 'faculty_id', 'teacher_name', 'school_level', 'subjects']): array
    {
        if (! self::tableExists($connection)) {
            return [];
        }

        return DB::connection($connection)->table('shared_teachers')
            ->where('is_active', true)
            ->orderBy('teacher_name')
            ->get($columns)
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();
    }

    /**
     * Approved schedule rows on another school DB for cross-school conflict display.
     *
     * @param  list<int|string>  $facultyIds
     * @param  list<string>  $columns
     */
    public static function crossSchoolConflicts(string $connection, array $facultyIds, array $columns): Collection
    {
        if ($facultyIds === [] || ! Schema::connection($connection)->hasTable('class_schedules')) {
            return collect();
        }

        return DB::connection($connection)->table('class_schedules')
            ->whereIn('faculty_id', $facultyIds)
            ->where('admin_approved', true)
            ->whereNotNull('day_of_week')
            ->whereNotNull('start_time')
            ->get($columns);
    }
}
