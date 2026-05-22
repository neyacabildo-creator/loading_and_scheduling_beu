<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $schoolConnections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConnections as $conn) {
            if (!Schema::connection($conn)->hasTable('pending_schedules')) {
                continue;
            }

            $this->dropPendingScheduleTriggers($conn);

            if (Schema::connection($conn)->hasColumn('pending_schedules', 'room_id')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) {
                    $table->dropColumn('room_id');
                });
            }

            $this->createPendingScheduleTriggersWithoutRoom($conn);
        }
    }

    public function down(): void
    {
        foreach ($this->schoolConnections as $conn) {
            if (!Schema::connection($conn)->hasTable('pending_schedules')) {
                continue;
            }

            $this->dropPendingScheduleTriggers($conn);

            if (!Schema::connection($conn)->hasColumn('pending_schedules', 'room_id')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) {
                    $table->unsignedBigInteger('room_id')->nullable()->after('section_name');
                });
            }

            $this->createPendingScheduleTriggersWithRoom($conn);
        }
    }

    private function dropPendingScheduleTriggers(string $conn): void
    {
        foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $trigger) {
            try {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            } catch (\Throwable $e) {
                // Ignore if trigger does not exist on this DB.
            }
        }
    }

    private function createPendingScheduleTriggersWithoutRoom(string $conn): void
    {
        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_ai`
            AFTER INSERT ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `pending_schedules`
                        (schedule_id, faculty_id, subject, grade_level, section_name,
                         day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                         NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id     = NEW.faculty_id,
                        subject        = NEW.subject,
                        grade_level    = NEW.grade_level,
                        section_name   = NEW.section_name,
                        day_of_week    = NEW.day_of_week,
                        schedule_date  = NEW.schedule_date,
                        start_time     = NEW.start_time,
                        end_time       = NEW.end_time,
                        student_count  = NEW.student_count,
                        status         = COALESCE(NEW.status,'pending'),
                        admin_approved = NEW.admin_approved,
                        updated_at     = NOW();
                END IF;
            END");

        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_au`
            AFTER UPDATE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `pending_schedules`
                        (schedule_id, faculty_id, subject, grade_level, section_name,
                         day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name,
                         NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id     = NEW.faculty_id,
                        subject        = NEW.subject,
                        grade_level    = NEW.grade_level,
                        section_name   = NEW.section_name,
                        day_of_week    = NEW.day_of_week,
                        schedule_date  = NEW.schedule_date,
                        start_time     = NEW.start_time,
                        end_time       = NEW.end_time,
                        student_count  = NEW.student_count,
                        status         = COALESCE(NEW.status,'pending'),
                        admin_approved = NEW.admin_approved,
                        updated_at     = NOW();
                ELSE
                    DELETE FROM `pending_schedules` WHERE schedule_id = NEW.id;
                END IF;
            END");

        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_ad`
            AFTER DELETE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                DELETE FROM `pending_schedules` WHERE schedule_id = OLD.id;
            END");
    }

    private function createPendingScheduleTriggersWithRoom(string $conn): void
    {
        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_ai`
            AFTER INSERT ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `pending_schedules`
                        (schedule_id, faculty_id, subject, grade_level, section_name, room_id,
                         day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name, NEW.room_id,
                         NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id     = NEW.faculty_id,
                        subject        = NEW.subject,
                        grade_level    = NEW.grade_level,
                        section_name   = NEW.section_name,
                        room_id        = NEW.room_id,
                        day_of_week    = NEW.day_of_week,
                        schedule_date  = NEW.schedule_date,
                        start_time     = NEW.start_time,
                        end_time       = NEW.end_time,
                        student_count  = NEW.student_count,
                        status         = COALESCE(NEW.status,'pending'),
                        admin_approved = NEW.admin_approved,
                        updated_at     = NOW();
                END IF;
            END");

        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_au`
            AFTER UPDATE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `pending_schedules`
                        (schedule_id, faculty_id, subject, grade_level, section_name, room_id,
                         day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_level, NEW.section_name, NEW.room_id,
                         NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id     = NEW.faculty_id,
                        subject        = NEW.subject,
                        grade_level    = NEW.grade_level,
                        section_name   = NEW.section_name,
                        room_id        = NEW.room_id,
                        day_of_week    = NEW.day_of_week,
                        schedule_date  = NEW.schedule_date,
                        start_time     = NEW.start_time,
                        end_time       = NEW.end_time,
                        student_count  = NEW.student_count,
                        status         = COALESCE(NEW.status,'pending'),
                        admin_approved = NEW.admin_approved,
                        updated_at     = NOW();
                ELSE
                    DELETE FROM `pending_schedules` WHERE schedule_id = NEW.id;
                END IF;
            END");

        DB::connection($conn)->unprepared("CREATE TRIGGER `pending_schedules_ad`
            AFTER DELETE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                DELETE FROM `pending_schedules` WHERE schedule_id = OLD.id;
            END");
    }
};
