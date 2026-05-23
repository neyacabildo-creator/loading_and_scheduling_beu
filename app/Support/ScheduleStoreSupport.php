<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\ScheduleApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe create/update helpers for class_schedules on school DB connections.
 */
class ScheduleStoreSupport
{
    public static function schoolConnection(): string
    {
        $conn = config('database.school_connection');

        return ($conn && array_key_exists($conn, config('database.connections', [])))
            ? $conn
            : 'mysql_jh';
    }

    public static function oppositeConnection(?string $connection = null): string
    {
        $connection = $connection ?? self::schoolConnection();

        return $connection === 'mysql_gs' ? 'mysql_jh' : 'mysql_gs';
    }

    /**
     * @return list<string>
     */
    public static function sharedFacultyIdStrings(?string $connection = null): array
    {
        $connection = $connection ?? self::schoolConnection();

        return array_map(
            'strval',
            SharedTeacherSupport::activeFacultyIds($connection)
        );
    }

    /**
     * Approved schedule on the other school DB for cross-school conflict checks.
     */
    public static function crossSchoolApprovedConflict(
        int $facultyId,
        string $dayOfWeek,
        string $startTime,
        ?string $fromConnection = null
    ): ?object {
        $crossConn = self::oppositeConnection($fromConnection);

        if (! Schema::connection($crossConn)->hasTable('class_schedules')) {
            return null;
        }

        return DB::connection($crossConn)
            ->table('class_schedules')
            ->where('faculty_id', $facultyId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->where('admin_approved', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createPendingSchedule(array $data, ?int $submittedBy = null): ClassSchedule
    {
        $connection = self::schoolConnection();

        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            throw new \RuntimeException(
                'Schedule tables are missing on this database. Run: php artisan migrate --force'
            );
        }

        $attributes = self::filterScheduleAttributes($connection, $data);
        $schedule = ClassSchedule::on($connection)->create($attributes);

        self::createApprovalRecord($connection, (int) $schedule->id, $submittedBy ?? (int) ($data['faculty_id'] ?? 0));

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterScheduleAttributes(string $connection, array $data): array
    {
        if (! empty($data['grade_level']) && ! empty($data['section_name'])
            && Schema::connection($connection)->hasColumn('class_schedules', 'grade_section')
            && empty($data['grade_section'])) {
            $data['grade_section'] = $data['grade_level'] . ' - ' . $data['section_name'];
        }

        $columns = Schema::connection($connection)->getColumnListing('class_schedules');

        return array_intersect_key($data, array_flip($columns));
    }

    public static function createApprovalRecord(string $connection, int $scheduleId, int $submittedBy): void
    {
        if (! Schema::connection($connection)->hasTable('schedule_approvals') || $scheduleId <= 0) {
            return;
        }

        $payload = [
            'schedule_id'  => $scheduleId,
            'submitted_by' => $submittedBy > 0 ? $submittedBy : (int) (auth()->id() ?? 0),
            'status'       => 'pending',
        ];

        $columns = Schema::connection($connection)->getColumnListing('schedule_approvals');
        $payload = array_intersect_key($payload, array_flip($columns));

        if ($payload['submitted_by'] <= 0) {
            unset($payload['submitted_by']);
        }

        ScheduleApproval::on($connection)->create($payload);
    }
}
