<?php

namespace App\Support;

use App\Models\User;
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
        if ($facultyId <= 0) {
            return [];
        }

        $merged = self::subjectsFromUserRecord($facultyId);
        foreach (self::registryConnectionsForPortal($connection, $schoolLevel) as $conn) {
            $merged = self::mergeSubjectLists($merged, self::subjectsFromRegistryConnection($conn, $facultyId, $schoolLevel));
        }

        $merged = self::normalizeSubjectList($merged);

        if ($merged !== []) {
            self::persistSubjectsOnUser($facultyId, $merged);
        }

        return $merged;
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
     * @return list<string>
     */
    public static function subjectsFromUserRecord(int $facultyId): array
    {
        if ($facultyId <= 0 || ! Schema::connection('mysql')->hasColumn('users', 'shared_teacher_subjects')) {
            return [];
        }

        $user = User::find($facultyId);
        if (! $user || $user->role?->name !== 'shared_teacher') {
            return [];
        }

        $raw = $user->shared_teacher_subjects;
        if (is_array($raw)) {
            return self::normalizeSubjectList($raw);
        }

        return [];
    }

    /**
     * @param  list<string>  $subjects
     */
    public static function persistSubjectsOnUser(int $facultyId, array $subjects): void
    {
        $subjects = self::normalizeSubjectList($subjects);
        if ($subjects === [] || ! Schema::connection('mysql')->hasColumn('users', 'shared_teacher_subjects')) {
            return;
        }

        User::where('id', $facultyId)->update(['shared_teacher_subjects' => $subjects]);
    }

    /**
     * @return list<string>
     */
    private static function subjectsFromRegistryConnection(string $connection, int $facultyId, ?string $schoolLevel): array
    {
        if (! self::tableExists($connection)) {
            return [];
        }

        $base = DB::connection($connection)->table('shared_teachers')
            ->where('faculty_id', $facultyId)
            ->where('is_active', true);

        if ($schoolLevel !== null && Schema::connection($connection)->hasColumn('shared_teachers', 'school_level')) {
            $scoped = (clone $base)->where('school_level', $schoolLevel)->get();
            $rows = $scoped->isNotEmpty() ? $scoped : $base->orderByDesc('updated_at')->get();
        } else {
            $rows = $base->orderByDesc('updated_at')->get();
        }

        $merged = [];
        foreach ($rows as $row) {
            $merged = self::mergeSubjectLists(
                $merged,
                self::parseSubjectsField($row->subjects ?? null, $row->department ?? null)
            );
        }

        return self::normalizeSubjectList($merged);
    }

    /**
     * Prefer the portal DB, but also read the other school DB (legacy / cross-portal creates).
     *
     * @return list<string>
     */
    private static function registryConnectionsForPortal(string $connection, ?string $schoolLevel): array
    {
        $primary = $connection;
        $secondary = $connection === 'mysql_gs' ? 'mysql_jh' : 'mysql_gs';

        return array_values(array_unique([$primary, $secondary]));
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<string>
     */
    private static function mergeSubjectLists(array $a, array $b): array
    {
        return self::normalizeSubjectList(array_merge($a, $b));
    }

    /**
     * @param  list<string>  $subjects
     * @return list<string>
     */
    private static function normalizeSubjectList(array $subjects): array
    {
        $map = [];
        foreach ($subjects as $subject) {
            $subject = trim((string) $subject);
            if ($subject === '' || strcasecmp($subject, 'Unassigned') === 0) {
                continue;
            }
            $map[strtolower($subject)] = $subject;
        }

        return array_values($map);
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

        $subjects = self::normalizeSubjectList($decoded);

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
