<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dual-division approval for shared-teacher schedule requests (JH + GS admins).
 */
class SharedTeacherRequestApprovalSupport
{
    public const TABLE = 'shared_teacher_requests';

    public static function tableHasDualApprovalColumns(string $connection): bool
    {
        return Schema::connection($connection)->hasTable(self::TABLE)
            && Schema::connection($connection)->hasColumn(self::TABLE, 'pair_key')
            && Schema::connection($connection)->hasColumn(self::TABLE, 'jh_approved_at')
            && Schema::connection($connection)->hasColumn(self::TABLE, 'gs_approved_at');
    }

    /**
     * @param  array<string, mixed>  $insert
     * @return array{primary_id: int, pair_key: string}
     */
    public static function insertPairedRequest(string $primaryConnection, array $insert): array
    {
        $pairKey = (string) Str::uuid();
        if (Schema::connection($primaryConnection)->hasColumn(self::TABLE, 'pair_key')) {
            $insert['pair_key'] = $pairKey;
        }

        $primaryId = (int) DB::connection($primaryConnection)->table(self::TABLE)->insertGetId($insert);

        $otherConnection = $primaryConnection === 'mysql_jh' ? 'mysql_gs' : 'mysql_jh';
        if (! Schema::connection($otherConnection)->hasTable(self::TABLE)) {
            return ['primary_id' => $primaryId, 'pair_key' => $pairKey];
        }

        $mirror = $insert;
        $mirror['school_level'] = $primaryConnection === 'mysql_jh' ? 'grade_school' : 'junior_high';
        $mirror['status'] = 'pending';
        $mirror['created_at'] = $insert['created_at'] ?? now();
        $mirror['updated_at'] = $insert['updated_at'] ?? now();
        unset($mirror['id']);

        if (Schema::connection($otherConnection)->hasColumn(self::TABLE, 'pair_key')) {
            $mirror['pair_key'] = $pairKey;
        }
        if (Schema::connection($otherConnection)->hasColumn(self::TABLE, 'is_peer_mirror')) {
            $mirror['is_peer_mirror'] = true;
        }

        DB::connection($otherConnection)->table(self::TABLE)->insert($mirror);

        return ['primary_id' => $primaryId, 'pair_key' => $pairKey];
    }

    /**
     * Record one division's approval; apply schedule changes when both divisions have approved.
     *
     * @return array{fully_approved: bool, applied: bool, message: string}
     */
    public static function recordDivisionApproval(
        string $connection,
        object $row,
        int $adminUserId,
        ?string $reviewerName = null
    ): array {
        if (! self::tableHasDualApprovalColumns($connection)) {
            return ['fully_approved' => true, 'applied' => false, 'message' => ''];
        }

        $pairKey = (string) ($row->pair_key ?? '');
        $isJhConn = $connection === 'mysql_jh';
        $now = now();

        $jhPatch = $isJhConn
            ? ['jh_approved_at' => $now, 'jh_approved_by' => $adminUserId]
            : [];
        $gsPatch = ! $isJhConn
            ? ['gs_approved_at' => $now, 'gs_approved_by' => $adminUserId]
            : [];

        $patch = array_merge($jhPatch, $gsPatch, ['updated_at' => $now]);

        DB::connection($connection)->table(self::TABLE)->where('id', $row->id)->update($patch);

        if ($pairKey !== '') {
            $other = $isJhConn ? 'mysql_gs' : 'mysql_jh';
            if (Schema::connection($other)->hasTable(self::TABLE)) {
                DB::connection($other)->table(self::TABLE)
                    ->where('pair_key', $pairKey)
                    ->update($patch);
            }
        }

        $fresh = DB::connection($connection)->table(self::TABLE)->where('id', $row->id)->first() ?? $row;
        $jhDone = ! empty($fresh->jh_approved_at);
        $gsDone = ! empty($fresh->gs_approved_at);

        if (! $jhDone || ! $gsDone) {
            return [
                'fully_approved' => false,
                'applied'        => false,
                'message'        => $jhDone && ! $gsDone
                    ? 'Junior High approved. Waiting for Grade School admin approval.'
                    : (! $jhDone && $gsDone
                        ? 'Grade School approved. Waiting for Junior High admin approval.'
                        : 'Waiting for both school admins to approve.'),
            ];
        }

        $applyNote = '';
        $applied = false;
        foreach (['mysql_jh' => 'junior_high', 'mysql_gs' => 'grade_school'] as $conn => $schoolLevel) {
            $target = null;
            if ($pairKey !== '' && Schema::connection($conn)->hasColumn(self::TABLE, 'pair_key')) {
                $target = DB::connection($conn)->table(self::TABLE)
                    ->where('pair_key', $pairKey)
                    ->where('school_level', $schoolLevel)
                    ->first();
            }
            if (! $target && $conn === $connection) {
                $target = $fresh;
            }
            if (! $target) {
                continue;
            }
            $result = TeacherAdjustmentRequestSupport::applyApprovedToSchedule(
                $conn,
                $target,
                $reviewerName
            );
            if ($result['applied']) {
                $applied = true;
                $applyNote = $result['message'];
            }
        }

        return [
            'fully_approved' => true,
            'applied'        => $applied,
            'message'        => $applied ? $applyNote : 'Approved by both admins. No matching class schedule was updated.',
        ];
    }

    private static function resolveRowForConnection(string $connection, object $row, string $pairKey): ?object
    {
        if ($pairKey !== '' && Schema::connection($connection)->hasColumn(self::TABLE, 'pair_key')) {
            $paired = DB::connection($connection)->table(self::TABLE)->where('pair_key', $pairKey)->first();
            if ($paired) {
                return $paired;
            }
        }

        return DB::connection($connection)->table(self::TABLE)->where('id', $row->id)->first();
    }

    public static function isSharedTeacherUser(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        $user = User::with('role')->find($userId);

        return $user && $user->role?->name === 'shared_teacher';
    }
}
