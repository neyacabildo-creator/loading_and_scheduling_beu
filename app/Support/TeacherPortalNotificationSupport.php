<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherPortalNotificationSupport
{
    public const TABLE = 'teacher_notifications';

    public static function ensureTable(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable(self::TABLE)) {
            throw new \RuntimeException(
                'Teacher notifications table is missing on ' . $connection . '. Run database migrations.'
            );
        }
    }

    public static function notify(
        string $connection,
        int $teacherId,
        string $title,
        string $message,
        string $type = 'general',
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $sentBy = null
    ): void {
        if ($teacherId <= 0) {
            return;
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return;
        }

        DB::connection($connection)->table(self::TABLE)->insert([
            'teacher_id'   => $teacherId,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
            'is_read'      => false,
            'read_at'      => null,
            'sent_by'      => $sentBy ?? Auth::id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public static function notifySharedTeacherRequestDecision(
        string $connection,
        object $requestRow,
        string $decision,
        ?string $adminNotes = null
    ): void {
        $teacherId = (int) ($requestRow->faculty_id ?? 0);
        $statusWord = $decision === 'approved' ? 'approved' : 'rejected';
        $subject = trim((string) ($requestRow->subject ?? 'Schedule request'));
        $title = 'Shared teacher request ' . $statusWord;
        $message = 'Your schedule request' . ($subject !== '' ? ' (' . $subject . ')' : '')
            . ' was ' . $statusWord . ' by the administrator.';
        if ($adminNotes) {
            $message .= ' Note: ' . $adminNotes;
        }

        self::notify(
            $connection,
            $teacherId,
            $title,
            $message,
            'shared_teacher_request_' . $decision,
            'shared_teacher_requests',
            (int) ($requestRow->id ?? 0) ?: null
        );
    }

    public static function notifyTeacherLeaveRequestDecision(
        string $connection,
        object $requestRow,
        string $decision,
        ?string $adminNotes = null
    ): void {
        $teacherId = (int) ($requestRow->teacher_id ?? 0);
        $statusWord = $decision === 'approved' ? 'approved' : 'rejected';
        $typeLabel = TeacherLeaveRequestSupport::leaveTypeLabel($requestRow->leave_type ?? null);
        $title = 'Absence/leave request ' . $statusWord;
        $message = 'Your ' . $typeLabel . ' request was ' . $statusWord . ' by the administrator.';
        if ($adminNotes) {
            $message .= ' Note: ' . $adminNotes;
        }

        self::notify(
            $connection,
            $teacherId,
            $title,
            $message,
            'teacher_leave_request_' . $decision,
            'teacher_leave_requests',
            (int) ($requestRow->id ?? 0) ?: null
        );
    }

    public static function notifyTeacherRequestDecision(
        string $connection,
        object $requestRow,
        string $decision,
        ?string $adminNotes = null
    ): void {
        $teacherId = (int) ($requestRow->faculty_id ?? 0);
        $statusWord = $decision === 'approved' ? 'approved' : 'rejected';
        $typeLabel = TeacherPresenceSupport::isAbsenceLeaveType($requestRow->request_type ?? null)
            ? TeacherPresenceSupport::typeLabel($requestRow->request_type)
            : AdminRequestDisplay::requestTypeLabel($requestRow->request_type ?? null);

        $title = 'Request ' . $statusWord;
        $message = 'Your ' . $typeLabel . ' request was ' . $statusWord . ' by the administrator.';
        if ($adminNotes) {
            $message .= ' Note: ' . $adminNotes;
        }

        self::notify(
            $connection,
            $teacherId,
            $title,
            $message,
            'teacher_request_' . $decision,
            'teacher_requests',
            (int) ($requestRow->id ?? 0) ?: null
        );
    }

    public static function listForTeacher(string $connection, int $teacherId, int $limit = 30): array
    {
        if ($teacherId <= 0) {
            return [];
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return [];
        }

        return DB::connection($connection)
            ->table(self::TABLE)
            ->where('teacher_id', $teacherId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($n) => (array) $n)
            ->all();
    }

    public static function unreadCount(string $connection, int $teacherId): int
    {
        if ($teacherId <= 0) {
            return 0;
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return 0;
        }

        return (int) DB::connection($connection)
            ->table(self::TABLE)
            ->where('teacher_id', $teacherId)
            ->where('is_read', false)
            ->count();
    }

    public static function markRead(string $connection, int $teacherId, ?int $notificationId = null): void
    {
        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return;
        }

        $query = DB::connection($connection)
            ->table(self::TABLE)
            ->where('teacher_id', $teacherId);

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $query->where('is_read', false)->update([
            'is_read'    => true,
            'read_at'    => now(),
            'updated_at' => now(),
        ]);
    }
}
