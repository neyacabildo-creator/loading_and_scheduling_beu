<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops and recreates the three schedule_approvals audit triggers in BOTH
 * mysql_jh and mysql_gs, removing all references to admin_notes (which the
 * user manually dropped from the table).
 *
 * Triggers fixed:
 *   schedule_approvals_after_insert
 *   schedule_approvals_after_update
 *   schedule_approvals_after_delete
 */
return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            $db = DB::connection($conn);

            // ── Drop old triggers that reference admin_notes ──────────────────
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_insert`');
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_update`');
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_delete`');

            // ── Recreate without admin_notes ──────────────────────────────────
            $db->unprepared("
                CREATE TRIGGER `schedule_approvals_after_insert`
                AFTER INSERT ON `schedule_approvals`
                FOR EACH ROW
                INSERT INTO `audit_logs`
                    (`table_name`, `record_id`, `action`, `new_data`, `changed_by`, `changed_at`)
                VALUES (
                    'schedule_approvals',
                    NEW.id,
                    'INSERT',
                    JSON_OBJECT(
                        'id',           NEW.`id`,
                        'schedule_id',  NEW.`schedule_id`,
                        'submitted_by', NEW.`submitted_by`,
                        'status',       NEW.`status`,
                        'reviewed_by',  NEW.`reviewed_by`,
                        'reviewed_at',  NEW.`reviewed_at`,
                        'revision_count', NEW.`revision_count`
                    ),
                    @audit_user,
                    NOW()
                )
            ");

            $db->unprepared("
                CREATE TRIGGER `schedule_approvals_after_update`
                AFTER UPDATE ON `schedule_approvals`
                FOR EACH ROW
                INSERT INTO `audit_logs`
                    (`table_name`, `record_id`, `action`, `old_data`, `new_data`, `changed_by`, `changed_at`)
                VALUES (
                    'schedule_approvals',
                    NEW.id,
                    'UPDATE',
                    JSON_OBJECT(
                        'id',           OLD.`id`,
                        'schedule_id',  OLD.`schedule_id`,
                        'submitted_by', OLD.`submitted_by`,
                        'status',       OLD.`status`,
                        'reviewed_by',  OLD.`reviewed_by`,
                        'reviewed_at',  OLD.`reviewed_at`,
                        'revision_count', OLD.`revision_count`
                    ),
                    JSON_OBJECT(
                        'id',           NEW.`id`,
                        'schedule_id',  NEW.`schedule_id`,
                        'submitted_by', NEW.`submitted_by`,
                        'status',       NEW.`status`,
                        'reviewed_by',  NEW.`reviewed_by`,
                        'reviewed_at',  NEW.`reviewed_at`,
                        'revision_count', NEW.`revision_count`
                    ),
                    @audit_user,
                    NOW()
                )
            ");

            $db->unprepared("
                CREATE TRIGGER `schedule_approvals_after_delete`
                AFTER DELETE ON `schedule_approvals`
                FOR EACH ROW
                INSERT INTO `audit_logs`
                    (`table_name`, `record_id`, `action`, `old_data`, `changed_by`, `changed_at`)
                VALUES (
                    'schedule_approvals',
                    OLD.id,
                    'DELETE',
                    JSON_OBJECT(
                        'id',           OLD.`id`,
                        'schedule_id',  OLD.`schedule_id`,
                        'submitted_by', OLD.`submitted_by`,
                        'status',       OLD.`status`,
                        'reviewed_by',  OLD.`reviewed_by`,
                        'reviewed_at',  OLD.`reviewed_at`,
                        'revision_count', OLD.`revision_count`
                    ),
                    @audit_user,
                    NOW()
                )
            ");
        }
    }

    public function down(): void
    {
        // Restore triggers with admin_notes — only needed if you re-add the column
        foreach ($this->connections as $conn) {
            $db = DB::connection($conn);
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_insert`');
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_update`');
            $db->unprepared('DROP TRIGGER IF EXISTS `schedule_approvals_after_delete`');
        }
    }
};
