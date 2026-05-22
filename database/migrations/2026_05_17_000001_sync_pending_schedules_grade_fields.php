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
            if (Schema::connection($conn)->hasTable('pending_schedules')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) use ($conn) {
                    if (!Schema::connection($conn)->hasColumn('pending_schedules', 'grade_level')) {
                        $table->string('grade_level', 50)->nullable()->after('subject');
                    }
                    if (!Schema::connection($conn)->hasColumn('pending_schedules', 'section_name')) {
                        $table->string('section_name', 100)->nullable()->after('grade_level');
                    }
                });

                $this->recreatePendingScheduleTriggers($conn);

                DB::connection($conn)->statement("UPDATE pending_schedules ps
                    JOIN class_schedules cs ON cs.id = ps.schedule_id
                    SET ps.subject = cs.subject,
                        ps.grade_level = cs.grade_level,
                        ps.section_name = cs.section_name
                    WHERE (ps.subject IS NULL OR ps.subject = '')
                       OR (ps.grade_level IS NULL OR ps.grade_level = '')
                       OR (ps.section_name IS NULL OR ps.section_name = '')");
            }

            if (Schema::connection($conn)->hasTable('rejected_schedules')) {
                Schema::connection($conn)->table('rejected_schedules', function (Blueprint $table) use ($conn) {
                    if (!Schema::connection($conn)->hasColumn('rejected_schedules', 'grade_level')) {
                        $table->string('grade_level', 50)->nullable()->after('subject');
                    }
                    if (!Schema::connection($conn)->hasColumn('rejected_schedules', 'section_name')) {
                        $table->string('section_name', 100)->nullable()->after('grade_level');
                    }
                });

                DB::connection($conn)->statement("UPDATE rejected_schedules rs
                    JOIN class_schedules cs ON cs.id = rs.schedule_id
                    SET rs.subject = cs.subject,
                        rs.grade_level = cs.grade_level,
                        rs.section_name = cs.section_name
                    WHERE (rs.subject IS NULL OR rs.subject = '')
                       OR (rs.grade_level IS NULL OR rs.grade_level = '')
                       OR (rs.section_name IS NULL OR rs.section_name = '')");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->schoolConnections as $conn) {
            if (Schema::connection($conn)->hasTable('pending_schedules')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) use ($conn) {
                    if (Schema::connection($conn)->hasColumn('pending_schedules', 'section_name')) {
                        $table->dropColumn('section_name');
                    }
                    if (Schema::connection($conn)->hasColumn('pending_schedules', 'grade_level')) {
                        $table->dropColumn('grade_level');
                    }
                });
            }
            if (Schema::connection($conn)->hasTable('rejected_schedules')) {
                Schema::connection($conn)->table('rejected_schedules', function (Blueprint $table) use ($conn) {
                    if (Schema::connection($conn)->hasColumn('rejected_schedules', 'section_name')) {
                        $table->dropColumn('section_name');
                    }
                    if (Schema::connection($conn)->hasColumn('rejected_schedules', 'grade_level')) {
                        $table->dropColumn('grade_level');
                    }
                });
            }
        }
    }

    private function recreatePendingScheduleTriggers(string $conn): void
    {
        foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $trigger) {
            try {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            } catch (\Exception $e) {
                // ignore
            }
        }

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
                        status        = COALESCE(NEW.status,'pending'),
                        admin_approved= NEW.admin_approved,
                        updated_at    = NOW();
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
                        status        = COALESCE(NEW.status,'pending'),
                        admin_approved= NEW.admin_approved,
                        updated_at    = NOW();
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
