<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherLeaveRequestSupport
{
    public const TABLE = 'teacher_leave_requests';

    /** Values stored in teacher_leave_requests.leave_type */
    public const LEAVE_TYPES = [
        'absent',
        'sick_leave',
        'vacation_leave',
        'emergency_leave',
        'official_business',
        'other',
    ];

    public static function ensureTable(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable(self::TABLE)) {
            throw new \RuntimeException(
                'Teacher leave requests table is missing. Run database migrations for ' . $connection . '.'
            );
        }
    }

    public static function normalizeLeaveType(string $type): string
    {
        $type = trim($type);

        return match ($type) {
            'leave_other' => 'other',
            'absent', 'sick_leave', 'vacation_leave', 'emergency_leave', 'official_business', 'other' => $type,
            default => 'other',
        };
    }

    public static function leaveTypeLabel(?string $type): string
    {
        return TeacherPresenceSupport::typeLabel(
            $type === 'other' ? 'leave_other' : $type
        );
    }

    public static function listForTeacher(string $connection, int $teacherId): Collection
    {
        self::ensureTable($connection);

        return DB::connection($connection)
            ->table(self::TABLE)
            ->where('teacher_id', $teacherId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                $r->request_type = $r->leave_type;
                $r->faculty_id = $r->teacher_id;

                return $r;
            });
    }

    /**
     * @return array{success: bool, id?: int, message: string}
     */
    public static function store(Request $request, string $connection): array
    {
        self::ensureTable($connection);

        $validated = $request->validate([
            'leave_type'       => 'nullable|in:' . implode(',', array_merge(self::LEAVE_TYPES, ['leave_other'])),
            'request_type'     => 'nullable|in:' . implode(',', array_merge(self::LEAVE_TYPES, ['leave_other'])),
            'date_from'        => 'required|date',
            'date_to'          => 'required|date|after_or_equal:date_from',
            'reason'           => 'required|string|min:3|max:1000',
            'proposed_changes' => 'nullable|string|max:1000',
            'admin_notes'      => 'nullable|string|max:1000',
        ]);

        $leaveType = self::normalizeLeaveType(
            (string) ($validated['leave_type'] ?? $validated['request_type'] ?? 'other')
        );

        $dateFrom = Carbon::parse($validated['date_from'])->startOfDay();
        $dateTo = Carbon::parse($validated['date_to'])->startOfDay();
        $totalDays = max(1, $dateFrom->diffInDays($dateTo) + 1);

        $notes = trim((string) ($validated['proposed_changes'] ?? ''));
        $adminNotes = $notes !== '' ? $notes : null;

        $id = DB::connection($connection)->table(self::TABLE)->insertGetId([
            'teacher_id'          => Auth::id(),
            'leave_type'          => $leaveType,
            'date_from'           => $dateFrom->toDateString(),
            'date_to'             => $dateTo->toDateString(),
            'total_days'          => $totalDays,
            'reason'              => $validated['reason'],
            'supporting_document' => null,
            'status'              => 'pending',
            'admin_notes'         => $adminNotes,
            'reviewed_by'         => null,
            'reviewed_at'         => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return [
            'success' => true,
            'id'      => $id,
            'message' => 'Absence/leave request submitted.',
        ];
    }

    /**
     * Load leave rows for admin All Requests (excludes shared teachers).
     */
    public static function listForAdmin(string $connection, array $excludeTeacherIds = []): Collection
    {
        if (! Schema::connection($connection)->hasTable(self::TABLE)) {
            return collect();
        }

        $query = DB::connection($connection)
            ->table(self::TABLE)
            ->orderByRaw("FIELD(status,'pending','approved','rejected','cancelled')")
            ->orderByDesc('created_at');

        $rows = $query->get()->filter(function ($r) use ($excludeTeacherIds) {
            return ! in_array((int) $r->teacher_id, $excludeTeacherIds, true);
        });

        if ($rows->isEmpty()) {
            return collect();
        }

        $userIds = $rows->pluck('teacher_id')
            ->merge($rows->pluck('reviewed_by'))
            ->filter()
            ->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $presenceMap = TeacherPresenceSupport::activeStatusMapForTeachers(
            $connection,
            $rows->pluck('teacher_id')->map(fn ($id) => (int) $id)->all()
        );

        return $rows->map(function ($r) use ($users, $presenceMap) {
            $teacherId = (int) $r->teacher_id;
            $user = $users->get($teacherId);
            $reviewer = $users->get($r->reviewed_by);
            $from = substr((string) $r->date_from, 0, 10);
            $to = substr((string) $r->date_to, 0, 10);

            return (object) [
                'id'                 => $r->id,
                'source'             => 'teacher_leave_requests',
                'status'             => $r->status,
                'leave_type'         => $r->leave_type,
                'request_type'       => $r->leave_type,
                'request_type_label' => self::leaveTypeLabel($r->leave_type),
                'date_from'          => $from,
                'date_to'            => $to,
                'total_days'         => $r->total_days,
                'leave_dates'        => Carbon::parse($from)->format('M d, Y') . ' – ' . Carbon::parse($to)->format('M d, Y'),
                'reason'             => $r->reason,
                'admin_notes'        => $r->admin_notes,
                'created_at'         => $r->created_at,
                'reviewed_at'        => $r->reviewed_at,
                'user'               => $user,
                'reviewer'           => $reviewer,
                'presence'           => $presenceMap[$teacherId] ?? null,
            ];
        })->values();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function review(string $connection, int $id, string $status, ?string $notes, ?int $reviewerId = null)
    {
        self::ensureTable($connection);

        if (! in_array($status, ['approved', 'rejected'], true)) {
            return back()->with('error', 'Invalid status.');
        }

        $row = DB::connection($connection)->table(self::TABLE)->where('id', $id)->first();
        if (! $row) {
            return back()->with('error', 'Leave request not found.');
        }

        DB::connection($connection)->table(self::TABLE)->where('id', $id)->update([
            'status'      => $status,
            'admin_notes' => $notes,
            'reviewed_by' => $reviewerId ?? Auth::id(),
            'reviewed_at' => now(),
            'updated_at'  => now(),
        ]);

        TeacherPortalNotificationSupport::notifyTeacherLeaveRequestDecision(
            $connection,
            $row,
            $status,
            $notes
        );

        return back()->with('success', 'Leave request ' . $status . '.');
    }

    /**
     * API review (JH/GS admin JSON endpoints).
     *
     * @return array{success: bool, message: string}
     */
    public static function reviewApi(string $connection, int $id, string $status, ?string $notes, ?int $reviewerId = null): array
    {
        self::ensureTable($connection);

        $row = DB::connection($connection)->table(self::TABLE)->where('id', $id)->first();
        if (! $row) {
            return ['success' => false, 'message' => 'Leave request not found.'];
        }

        DB::connection($connection)->table(self::TABLE)->where('id', $id)->update([
            'status'      => $status,
            'admin_notes' => $notes,
            'reviewed_by' => $reviewerId ?? Auth::id(),
            'reviewed_at' => now(),
            'updated_at'  => now(),
        ]);

        TeacherPortalNotificationSupport::notifyTeacherLeaveRequestDecision(
            $connection,
            $row,
            $status,
            $notes
        );

        return ['success' => true, 'message' => 'Leave request ' . $status . '.'];
    }
}
