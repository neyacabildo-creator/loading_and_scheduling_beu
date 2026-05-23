<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPortalNotificationSupport
{
    public const TABLE = 'admin_notifications';

    public static function ensureTable(string $connection): void
    {
        if (Schema::connection($connection)->hasTable(self::TABLE)) {
            return;
        }

        Schema::connection($connection)->create(self::TABLE, function ($table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('type', 60)->default('general');
            $table->string('title', 200);
            $table->text('message');
            $table->string('related_type', 60)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
            $table->index('admin_user_id');
            $table->index(['admin_user_id', 'is_read']);
        });
    }

    public static function notify(
        string $connection,
        int $adminUserId,
        string $title,
        string $message,
        string $type = 'general',
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $sentBy = null
    ): void {
        if ($adminUserId <= 0) {
            return;
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return;
        }

        DB::connection($connection)->table(self::TABLE)->insert([
            'admin_user_id' => $adminUserId,
            'type'          => $type,
            'title'         => $title,
            'message'       => $message,
            'related_type'  => $relatedType,
            'related_id'    => $relatedId,
            'is_read'       => false,
            'read_at'       => null,
            'sent_by'       => $sentBy ?? Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Notify all active school-level admins on the given connection.
     */
    public static function notifySchoolAdmins(
        string $connection,
        string $schoolLevel,
        string $title,
        string $message,
        string $type = 'general',
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $sentBy = null
    ): void {
        $roleNames = $schoolLevel === 'junior_high'
            ? ['admin_junior_high', 'admin']
            : ['admin_grade_school', 'admin'];

        $admins = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($schoolLevel) {
                $q->where('school_level', $schoolLevel)
                    ->orWhereNull('school_level');
            })
            ->whereHas('role', fn ($q) => $q->whereIn('name', $roleNames))
            ->get();

        foreach ($admins as $admin) {
            self::notify(
                $connection,
                (int) $admin->id,
                $title,
                $message,
                $type,
                $relatedType,
                $relatedId,
                $sentBy
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listForAdmin(string $connection, int $adminUserId, int $limit = 50): array
    {
        if ($adminUserId <= 0) {
            return [];
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return [];
        }

        return DB::connection($connection)
            ->table(self::TABLE)
            ->where('admin_user_id', $adminUserId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id'           => (int) $row->id,
                'type'         => (string) ($row->type ?? 'general'),
                'title'        => (string) ($row->title ?? ''),
                'message'      => (string) ($row->message ?? ''),
                'related_type' => $row->related_type,
                'related_id'   => $row->related_id ? (int) $row->related_id : null,
                'is_read'      => (bool) ($row->is_read ?? false),
                'created_at'   => $row->created_at ? (string) $row->created_at : null,
            ])
            ->all();
    }

    public static function unreadCount(string $connection, int $adminUserId): int
    {
        if ($adminUserId <= 0) {
            return 0;
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return 0;
        }

        return (int) DB::connection($connection)
            ->table(self::TABLE)
            ->where('admin_user_id', $adminUserId)
            ->where('is_read', false)
            ->count();
    }

    public static function markRead(string $connection, int $adminUserId, ?int $notificationId = null): void
    {
        if ($adminUserId <= 0) {
            return;
        }

        try {
            self::ensureTable($connection);
        } catch (\Throwable) {
            return;
        }

        $query = DB::connection($connection)
            ->table(self::TABLE)
            ->where('admin_user_id', $adminUserId)
            ->where('is_read', false);

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $query->update([
            'is_read'    => true,
            'read_at'    => now(),
            'updated_at' => now(),
        ]);
    }

    public static function schoolLevelForConnection(string $connection): string
    {
        return $connection === 'mysql_gs' ? 'grade_school' : 'junior_high';
    }

    public static function notifyNewTeacherRequest(
        string $connection,
        string $teacherName,
        string $requestLabel,
        string $relatedType,
        ?int $relatedId,
        ?int $sentBy = null
    ): void {
        self::notifySchoolAdmins(
            $connection,
            self::schoolLevelForConnection($connection),
            'New teacher request',
            $teacherName . ' submitted a ' . $requestLabel . '. Review it in All Requests.',
            'teacher_request',
            $relatedType,
            $relatedId,
            $sentBy
        );
    }
}
