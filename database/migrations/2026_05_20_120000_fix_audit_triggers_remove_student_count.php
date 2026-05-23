<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recreate class_schedules audit triggers without dropped student_count column.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            if (!Schema::connection($conn)->hasTable('class_schedules')
                || !Schema::connection($conn)->hasTable('audit_logs')) {
                continue;
            }

            $this->recreateAuditTriggers($conn);
        }
    }

    public function down(): void
    {
        foreach ($this->schoolConns as $conn) {
            foreach (['class_schedules_after_insert', 'class_schedules_after_update', 'class_schedules_after_delete'] as $trigger) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }
        }
    }

    /** @return list<string> */
    private function classScheduleAuditColumns(string $conn): array
    {
        $candidates = [
            'id',
            'faculty_id',
            'subject',
            'grade_level',
            'section_name',
            'room_id',
            'day_of_week',
            'schedule_date',
            'start_time',
            'end_time',
            'status',
            'admin_approved',
            'approved_at',
            'approved_by',
            'version',
            'last_modified_by_admin',
            'change_log',
            'principal_approved',
            'principal_approved_at',
            'principal_approved_by',
        ];

        return array_values(array_filter(
            $candidates,
            fn (string $col) => Schema::connection($conn)->hasColumn('class_schedules', $col)
        ));
    }

    private function recreateAuditTriggers(string $conn): void
    {
        $table = 'class_schedules';
        $cols  = $this->classScheduleAuditColumns($conn);

        if ($cols === []) {
            return;
        }

        $newPairs = implode(', ', array_map(fn ($c) => "'{$c}', NEW.`{$c}`", $cols));
        $oldPairs = implode(', ', array_map(fn ($c) => "'{$c}', OLD.`{$c}`", $cols));

        foreach (['class_schedules_after_insert', 'class_schedules_after_update', 'class_schedules_after_delete'] as $trigger) {
            DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_insert`
            AFTER INSERT ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'INSERT', JSON_OBJECT({$newPairs}), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_update`
            AFTER UPDATE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'UPDATE', JSON_OBJECT({$oldPairs}), JSON_OBJECT({$newPairs}), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_delete`
            AFTER DELETE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', OLD.id, 'DELETE', JSON_OBJECT({$oldPairs}), @audit_user, NOW())
        ");
    }
};
