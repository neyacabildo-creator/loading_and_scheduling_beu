<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * class_schedules DELETE/UPDATE/INSERT triggers still referenced super_admin_approved
 * after columns were renamed to principal_approved — recreate all sync + audit triggers.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            $this->recreateAuditTriggers($conn);
            $this->recreatePendingAndRejectedTriggers($conn);
        }
    }

    public function down(): void
    {
        // Triggers are recreated on re-run of up(); no rollback body required.
    }

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
        if (! Schema::connection($conn)->hasTable('audit_logs')) {
            return;
        }

        $cols = $this->classScheduleAuditColumns($conn);
        if ($cols === []) {
            return;
        }

        $table = 'class_schedules';
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

    private function recreatePendingAndRejectedTriggers(string $conn): void
    {
        foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $trigger) {
            DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
        foreach (['rejected_schedules_ai', 'rejected_schedules_au', 'rejected_schedules_ad'] as $trigger) {
            DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        if (Schema::connection($conn)->hasTable('pending_schedules')) {
            DB::connection($conn)->unprepared("
                CREATE TRIGGER `pending_schedules_ai`
                AFTER INSERT ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF COALESCE(NEW.admin_approved, 0) = 0 AND COALESCE(NEW.status, 'pending') = 'pending' THEN
                        INSERT INTO `pending_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name,
                             day_of_week, schedule_date, start_time, end_time,
                             status, admin_approved, submitted_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             COALESCE(NEW.status,'pending'), NEW.admin_approved, NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id     = NEW.faculty_id,
                            subject        = NEW.subject,
                            grade_level    = NEW.grade_level,
                            section_name   = NEW.section_name,
                            day_of_week    = NEW.day_of_week,
                            schedule_date  = NEW.schedule_date,
                            start_time     = NEW.start_time,
                            end_time       = NEW.end_time,
                            status         = COALESCE(NEW.status,'pending'),
                            admin_approved = NEW.admin_approved,
                            updated_at     = NOW();
                    END IF;
                END
            ");

            DB::connection($conn)->unprepared("
                CREATE TRIGGER `pending_schedules_au`
                AFTER UPDATE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF COALESCE(NEW.admin_approved, 0) = 0 AND COALESCE(NEW.status, 'pending') = 'pending' THEN
                        INSERT INTO `pending_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name,
                             day_of_week, schedule_date, start_time, end_time,
                             status, admin_approved, submitted_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             COALESCE(NEW.status,'pending'), NEW.admin_approved, NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id     = NEW.faculty_id,
                            subject        = NEW.subject,
                            grade_level    = NEW.grade_level,
                            section_name   = NEW.section_name,
                            day_of_week    = NEW.day_of_week,
                            schedule_date  = NEW.schedule_date,
                            start_time     = NEW.start_time,
                            end_time       = NEW.end_time,
                            status         = COALESCE(NEW.status,'pending'),
                            admin_approved = NEW.admin_approved,
                            updated_at     = NOW();
                    ELSE
                        DELETE FROM `pending_schedules` WHERE schedule_id = NEW.id;
                    END IF;
                END
            ");

            DB::connection($conn)->unprepared("
                CREATE TRIGGER `pending_schedules_ad`
                AFTER DELETE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    DELETE FROM `pending_schedules` WHERE schedule_id = OLD.id;
                END
            ");
        }

        if (Schema::connection($conn)->hasTable('rejected_schedules')) {
            DB::connection($conn)->unprepared("
                CREATE TRIGGER `rejected_schedules_ai`
                AFTER INSERT ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name,
                             day_of_week, schedule_date, start_time, end_time,
                             rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_level   = NEW.grade_level,
                            section_name  = NEW.section_name,
                            day_of_week   = NEW.day_of_week,
                            schedule_date = NEW.schedule_date,
                            start_time    = NEW.start_time,
                            end_time      = NEW.end_time,
                            rejected_at   = NOW(),
                            updated_at    = NOW();
                    END IF;
                END
            ");

            DB::connection($conn)->unprepared("
                CREATE TRIGGER `rejected_schedules_au`
                AFTER UPDATE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name,
                             day_of_week, schedule_date, start_time, end_time,
                             rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_level   = NEW.grade_level,
                            section_name  = NEW.section_name,
                            day_of_week   = NEW.day_of_week,
                            schedule_date = NEW.schedule_date,
                            start_time    = NEW.start_time,
                            end_time      = NEW.end_time,
                            rejected_at   = IF(OLD.status != 'rejected', NOW(), rejected_at),
                            updated_at    = NOW();
                    ELSE
                        DELETE FROM `rejected_schedules` WHERE schedule_id = NEW.id AND NEW.status != 'rejected';
                    END IF;
                END
            ");

            DB::connection($conn)->unprepared("
                CREATE TRIGGER `rejected_schedules_ad`
                AFTER DELETE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    DELETE FROM `rejected_schedules` WHERE schedule_id = OLD.id;
                END
            ");
        }
    }
};
