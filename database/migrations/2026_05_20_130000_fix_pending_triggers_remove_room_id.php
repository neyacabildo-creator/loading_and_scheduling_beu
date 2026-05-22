<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * pending_schedules / rejected_schedules no longer have room_id — recreate sync triggers.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            if (!Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

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
            }
        }
    }

    public function down(): void
    {
        foreach ($this->schoolConns as $conn) {
            foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad',
                'rejected_schedules_ai', 'rejected_schedules_au', 'rejected_schedules_ad'] as $trigger) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }
        }
    }
};
