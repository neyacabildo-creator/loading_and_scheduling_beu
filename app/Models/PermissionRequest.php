<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PermissionRequest extends Model
{
    use HasFactory;

    /** All permission requests are stored in the dedicated principal database */
    protected $connection = 'mysql_principal';

    protected $fillable = [
        'requester_id',
        'reviewed_by',
        'action_type',
        'details',
        'school_level',
        'related_model',
        'related_id',
        'status',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ── Action type labels ──────────────────────────────────────────────────
    public const ACTION_TYPES = [
        'delete_schedule'   => 'Delete Schedule',
        'override_load'     => 'Override Faculty Load',
        'bulk_approve'      => 'Bulk Approve Schedules',
        'modify_approved'   => 'Modify Approved Schedule',
        'add_admin'         => 'Add New Admin Account',
        'remove_teacher'    => 'Remove Teacher',
        'change_room'       => 'Change Room Assignment',
        'other'             => 'Other',
    ];

    // ── Relationships ───────────────────────────────────────────────────────
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function actionLabel(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? ucfirst(str_replace('_', ' ', $this->action_type));
    }

    public function detailsSummary(int $limit = 80): string
    {
        return Str::limit(trim((string) ($this->details ?? '')), $limit) ?: '—';
    }
}
