<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical subject lists for shared-teacher registration (GS / JH admins).
 */
class SchoolSubjectsCatalog
{
    /** @var array<string, list<string>> */
    private const JH_BY_GRADE = [
        'Grade 6'  => [
            'MAPEH',
            'Araling Panlipunan',
            'Computer Education',
            'Advanced Science',
            'Christian Living/Values Education',
            'Mathematics',
            'Advanced Mathematics',
            'Filipino',
            'English',
            'Science',
            'Technology and Livelihood Education',
        ],
        'Grade 7'  => [
            'MAPEH',
            'Araling Panlipunan',
            'Computer Education',
            'Advanced Science',
            'Christian Living/Values Education',
            'Mathematics',
            'Advanced Mathematics',
            'Filipino',
            'English',
            'Science',
            'Technology and Livelihood Education',
        ],
        'Grade 8'  => [
            'MAPEH',
            'Araling Panlipunan',
            'Computer Education',
            'Advanced Science',
            'Christian Living/Values Education',
            'Mathematics',
            'Advanced Mathematics',
            'Filipino',
            'English',
            'Science',
            'Technology and Livelihood Education',
        ],
        'Grade 9'  => [
            'MAPEH',
            'Araling Panlipunan',
            'Computer Education',
            'Advanced Science',
            'Christian Living/Values Education',
            'Mathematics',
            'Advanced Mathematics',
            'Filipino',
            'English',
            'Science',
            'Technology and Livelihood Education',
        ],
        'Grade 10' => [
            'MAPEH',
            'Araling Panlipunan',
            'Computer Education',
            'Advanced Science',
            'Christian Living/Values Education',
            'Mathematics',
            'Advanced Mathematics',
            'Filipino',
            'English',
            'Science',
            'Technology and Livelihood Education',
        ],
    ];

    /** @var array<string, list<string>> */
    private const GS_BY_GRADE = [
        'Kinder 2' => ['Reading', 'Language', 'Filipino', 'Mathematics', 'CLVE/PE/Arts'],
        'Kinder 1' => ['Reading', 'Language', 'Filipino', 'Mathematics', 'CLVE/PE/Arts'],
        'Nursery'  => ['Reading', 'Language', 'Filipino', 'Mathematics', 'CLVE/PE/Arts'],
        'Grade 1' => ['SCIENCE', 'COMPUTER', 'READING AND LITERACY', 'LANGUAGE', 'CLVE', 'MAKABANSA', 'MATHEMATICS'],
        'Grade 2' => ['MAKABANSA', 'ENGLISH', 'FILIPINO', 'SCIENCE', 'MATHEMATICS', 'CLVE', 'COMPUTER'],
        'Grade 3' => ['FILIPINO', 'CLVE', 'MAKABANSA', 'SCIENCE', 'MATHEMATICS', 'READING AND LITERACY', 'ENGLISH', 'COMPUTER'],
        'Grade 4' => ['MATHEMATICS', 'HELE', 'AP', 'MAPEH', 'ENGLISH', 'SCIENCE', 'CLVE', 'FILIPINO', 'COMPUTER'],
        'Grade 5' => ['AP', 'FILIPINO', 'ENGLISH', 'SCIENCE', 'HELE', 'MATHEMATICS', 'MAPEH', 'COMPUTER', 'CLVE'],
    ];

    /** @var list<string> */
    public const GS_SHARED_TEACHER_GRADES = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5'];

    /**
     * All JH subjects (Grades 6–10) for shared-teacher assignment.
     *
     * @return list<string>
     */
    public static function juniorHighSharedTeacherSubjects(): array
    {
        return self::mergeSubjects(
            self::flattenGrades(self::JH_BY_GRADE),
            self::subjectsFromSchedules('mysql_jh')
        );
    }

    /**
     * All GS subjects (Grades 1–5) for shared-teacher assignment.
     *
     * @return list<string>
     */
    public static function gradeSchoolSharedTeacherSubjects(): array
    {
        return self::mergeSubjects(
            self::flattenGrades(self::GS_BY_GRADE),
            self::subjectsFromSchedules('mysql_gs', self::GS_SHARED_TEACHER_GRADES)
        );
    }

    /**
     * @return list<string>
     */
    public static function subjectsForPortal(string $portal): array
    {
        return match (self::normalizePortal($portal)) {
            'grade_school' => self::gradeSchoolSharedTeacherSubjects(),
            'junior_high'  => self::juniorHighSharedTeacherSubjects(),
            default        => self::mergeSubjects(
                self::juniorHighSharedTeacherSubjects(),
                self::gradeSchoolSharedTeacherSubjects()
            ),
        };
    }

    public static function normalizePortal(string $portal): string
    {
        $portal = strtolower(trim($portal));

        return match ($portal) {
            'gs', 'grade_school', 'gradeschool' => 'grade_school',
            'jh', 'junior_high', 'juniorhigh' => 'junior_high',
            default => $portal,
        };
    }

    /**
     * @param  array<string, list<string>>  $byGrade
     * @return list<string>
     */
    private static function flattenGrades(array $byGrade): array
    {
        $out = [];
        foreach ($byGrade as $subjects) {
            foreach ($subjects as $subject) {
                $subject = trim((string) $subject);
                if ($subject !== '') {
                    $out[] = $subject;
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $canonical
     * @param  list<string>  $fromDb
     * @return list<string>
     */
    private static function mergeSubjects(array $canonical, array $fromDb): array
    {
        $map = [];
        foreach (array_merge($canonical, $fromDb) as $subject) {
            $key = strtolower(trim((string) $subject));
            if ($key !== '') {
                $map[$key] = trim((string) $subject);
            }
        }

        $subjects = array_values($map);
        natcasesort($subjects);

        return array_values($subjects);
    }

    /**
     * @param  list<string>|null  $gradeLevels  When set, only subjects from these grade levels are included.
     * @return list<string>
     */
    private static function subjectsFromSchedules(string $connection, ?array $gradeLevels = null): array
    {
        try {
            if (! Schema::connection($connection)->hasTable('class_schedules')) {
                return [];
            }

            $query = DB::connection($connection)
                ->table('class_schedules')
                ->whereNotNull('subject')
                ->where('subject', '!=', '');

            if ($gradeLevels !== null
                && $gradeLevels !== []
                && Schema::connection($connection)->hasColumn('class_schedules', 'grade_level')) {
                $query->whereIn('grade_level', $gradeLevels);
            }

            return $query
                ->distinct()
                ->orderBy('subject')
                ->pluck('subject')
                ->map(fn ($s) => trim((string) $s))
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
