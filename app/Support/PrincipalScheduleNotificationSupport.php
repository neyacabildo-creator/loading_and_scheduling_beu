<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class PrincipalScheduleNotificationSupport
{
    public static function schoolLevelForConnection(string $connection): string
    {
        return $connection === 'mysql_gs' ? 'grade_school' : 'junior_high';
    }

    public static function scheduleLabel(object $schedule): string
    {
        $parts = array_filter([
            $schedule->subject ?? null,
            $schedule->grade_level ?? null,
            $schedule->section_name ?? null,
        ]);

        $when = trim(($schedule->day_of_week ?? '') . ' '
            . (isset($schedule->start_time) ? substr((string) $schedule->start_time, 0, 5) : ''));

        $label = $parts !== [] ? implode(' – ', $parts) : 'Class schedule';

        return $when !== '' ? $label . ' (' . $when . ')' : $label;
    }

    public static function afterApprove(string $connection, object $schedule): void
    {
        $label = self::scheduleLabel($schedule);
        $principalId = (int) Auth::id();
        $schoolLevel = self::schoolLevelForConnection($connection);
        $scheduleId = (int) ($schedule->id ?? 0);

        $teacherId = (int) ($schedule->faculty_id ?? 0);
        if ($teacherId > 0) {
            TeacherPortalNotificationSupport::notify(
                $connection,
                $teacherId,
                'Schedule fully approved',
                'The principal has approved your schedule: ' . $label . '. It is now fully confirmed.',
                'principal_schedule_approved',
                'class_schedules',
                $scheduleId ?: null,
                $principalId
            );
        }

        AdminPortalNotificationSupport::notifySchoolAdmins(
            $connection,
            $schoolLevel,
            'Principal approved schedule',
            'The principal approved a schedule: ' . $label . '.',
            'principal_schedule_approved',
            'class_schedules',
            $scheduleId ?: null,
            $principalId
        );
    }

    public static function afterReject(string $connection, object $schedule): void
    {
        $label = self::scheduleLabel($schedule);
        $principalId = (int) Auth::id();
        $schoolLevel = self::schoolLevelForConnection($connection);
        $scheduleId = (int) ($schedule->id ?? 0);

        AdminPortalNotificationSupport::notifySchoolAdmins(
            $connection,
            $schoolLevel,
            'Principal rejected schedule approval',
            'The principal did not approve the schedule: ' . $label . '. Please review it in Schedule Approvals.',
            'principal_schedule_rejected',
            'class_schedules',
            $scheduleId ?: null,
            $principalId
        );
    }
}
