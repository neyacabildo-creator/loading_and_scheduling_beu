<?php

namespace App\Support;

/**
 * Teacher portals read/write division data on the admin database (mysql_jh / mysql_gs).
 * The separate mysql_*_teacher databases are legacy; operational tables live with admin data.
 */
class TeacherDatabaseSupport
{
    public static function adminConnectionForSchool(string $schoolLevel): string
    {
        return $schoolLevel === 'grade_school' ? 'mysql_gs' : 'mysql_jh';
    }

    /** Alias used by grade-school / junior-high teacher controllers. */
    public static function connectionForSchool(string $schoolLevel): string
    {
        return self::adminConnectionForSchool($schoolLevel);
    }

    /** @deprecated Alias — same as admin connection */
    public static function teacherConnectionForSchool(string $schoolLevel): string
    {
        return self::adminConnectionForSchool($schoolLevel);
    }

    /**
     * Resolve admin DB from middleware, user school_level, or route context.
     */
    public static function connectionFromContext(?string $schoolLevel = null): string
    {
        $configured = config('database.school_connection');
        if (in_array($configured, ['mysql_jh', 'mysql_gs'], true)) {
            return $configured;
        }

        if ($schoolLevel !== null) {
            return self::adminConnectionForSchool($schoolLevel);
        }

        $user = auth()->user();
        if ($user && ! empty($user->school_level)) {
            $level = (string) $user->school_level;

            return str_contains(strtolower($level), 'grade') ? 'mysql_gs' : 'mysql_jh';
        }

        return 'mysql_jh';
    }
}
