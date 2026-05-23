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
     * Subjects chosen when the shared teacher account was created (User Accounts).
     *
     * @return list<string>
     */
    public static function assignedSubjectsForFaculty(string $connection, int $facultyId, ?string $schoolLevel = null): array
    {
        if ($facultyId <= 0 || ! self::tableExists($connection)) {
            return [];
        }

        $row = self::registryRowForFaculty($connection, $facultyId, $schoolLevel);
        if (! $row) {
            return [];
        }

        return self::parseSubjectsField($row->subjects ?? null, $row->department ?? null);
    }

    /**
     * @param  list<int>  $facultyIds
     * @return array<int, list<string>>
     */
    public static function subjectsMapForFacultyIds(string $connection, array $facultyIds, ?string $schoolLevel = null): array
    {
        $map = [];
        foreach ($facultyIds as $facultyId) {
            $id = (int) $facultyId;
            if ($id > 0) {
                $map[$id] = self::assignedSubjectsForFaculty($connection, $id, $schoolLevel);
            }
        }

        return $map;
    }

    /**
     * @return object|null
     */
    private static function registryRowForFaculty(string $connection, int $facultyId, ?string $schoolLevel)
    {
        if ($schoolLevel !== null && Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
            $scoped = DB::connection($connection)->table('shared_teachers')
                ->where('faculty_id', $facultyId)
                ->where('is_active', true)
                ->where('school_level', $schoolLevel)
                ->first();
            if ($scoped) {
                return $scoped;
            }
        }

        $rows = DB::connection($connection)->table('shared_teachers')
            ->where('faculty_id', $facultyId)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($rows as $row) {
            $parsed = self::parseSubjectsField($row->subjects ?? null, null);
            if (count($parsed) >= 2) {
                return $row;
            }
        }

        return $rows->first();
    }

    /**
     * @return list<string>
     */
    private static function parseSubjectsField(mixed $raw, ?string $department = null): array
    {
        $decoded = [];

        if (is_array($raw)) {
            $decoded = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                $decoded = array_map('trim', explode(',', $raw));
            }
        }

        $subjects = array_values(array_filter(array_map(
            static fn ($s) => trim((string) $s),
            $decoded
        )));

        if ($subjects === [] && $department !== null && trim($department) !== '' && strcasecmp(trim($department), 'Unassigned') !== 0) {
            $subjects = [trim($department)];
        }

        return $subjects;
    }

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
    public static function activeList(string $connection, ?array $columns = null): array
    {
        if (! self::tableExists($connection)) {
            return [];
        }

        if ($columns === null) {
            $columns = ['id', 'faculty_id', 'teacher_name'];
            if (Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
                $columns[] = 'school_level';
            }
            if (Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
                $columns[] = 'subjects';
            }
        } else {
            $columns = array_values(array_filter(
                $columns,
                fn (string $col) => Schema::connection($connection)->hasColumn('shared_teachers', $col)
            ));
            if ($columns === []) {
                $columns = ['id', 'faculty_id', 'teacher_name'];
            }
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
