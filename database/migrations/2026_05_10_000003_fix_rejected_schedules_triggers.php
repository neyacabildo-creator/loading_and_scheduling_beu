<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops and recreates the rejected_schedules_ai / rejected_schedules_au triggers
 * so they no longer reference the dropped `grade_section` column and instead
 * use `grade_level` + `section_name`.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            // Drop old triggers
            foreach (['rejected_schedules_ai', 'rejected_schedules_au', 'rejected_schedules_ad'] as $trigger) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }

            // Recreate AFTER INSERT trigger
            DB::connection($conn)->unprepared("
                CREATE TRIGGER `rejected_schedules_ai`
                AFTER INSERT ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_level   = NEW.grade_level,
                            section_name  = NEW.section_name,
                            room_id       = NEW.room_id,
                            day_of_week   = NEW.day_of_week,
                            schedule_date = NEW.schedule_date,
                            start_time    = NEW.start_time,
                            end_time      = NEW.end_time,
                            student_count = NEW.student_count,
                            rejected_at   = NOW(),
                            updated_at    = NOW();
                    END IF;
                END
            ");

            // Recreate AFTER UPDATE trigger
            DB::connection($conn)->unprepared("
                CREATE TRIGGER `rejected_schedules_au`
                AFTER UPDATE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_level, section_name, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_level   = NEW.grade_level,
                            section_name  = NEW.section_name,
                            room_id       = NEW.room_id,
                            day_of_week   = NEW.day_of_week,
                            schedule_date = NEW.schedule_date,
                            start_time    = NEW.start_time,
                            end_time      = NEW.end_time,
                            student_count = NEW.student_count,
                            rejected_at   = IF(OLD.status != 'rejected', NOW(), rejected_at),
                            updated_at    = NOW();
                    ELSE
                        DELETE FROM `rejected_schedules` WHERE schedule_id = NEW.id;
                    END IF;
                END
            ");

            // Recreate AFTER DELETE trigger
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

    public function down(): void
    {
        foreach ($this->schoolConns as $conn) {
            foreach (['rejected_schedules_ai', 'rejected_schedules_au', 'rejected_schedules_ad'] as $trigger) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }
        }
    }
};
