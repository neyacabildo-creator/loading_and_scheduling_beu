<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the legacy `grade_section` combined column from:
 *   - class_schedules  (mysql, mysql_jh, mysql_gs)
 *   - pending_schedules  (mysql_jh, mysql_gs)
 *   - rejected_schedules (mysql_jh, mysql_gs)
 *
 * Adds `grade_level` + `section_name` to pending_schedules and
 * rejected_schedules (mirroring class_schedules) and recreates all
 * triggers that previously referenced the dropped column.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    // ── Audit snapshot columns for class_schedules (grade_section removed,
    //    grade_level + section_name added)
    private array $auditCols = [
        'id', 'faculty_id', 'subject', 'grade_level', 'section_name', 'room_id',
        'day_of_week', 'start_time', 'end_time', 'student_count',
        'status', 'admin_approved', 'approved_at', 'approved_by', 'schedule_date',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        // 1. Drop triggers that reference grade_section on class_schedules ────
        foreach ($this->schoolConns as $conn) {
            foreach ([
                'class_schedules_after_insert',
                'class_schedules_after_update',
                'class_schedules_after_delete',
                'pending_schedules_ai',
                'pending_schedules_au',
                'pending_schedules_ad',
            ] as $trigger) {
                try {
                    DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Drop trigger {$trigger} on {$conn}: " . $e->getMessage());
                }
            }
        }

        // 2. Drop grade_section from class_schedules on all connections ───────
        foreach (['mysql', ...$this->schoolConns] as $conn) {
            if (Schema::connection($conn)->hasColumn('class_schedules', 'grade_section')) {
                Schema::connection($conn)->table('class_schedules', function (Blueprint $table) {
                    $table->dropColumn('grade_section');
                });
            }
        }

        // 3. Update pending_schedules + rejected_schedules on school DBs ──────
        foreach ($this->schoolConns as $conn) {
            // ── pending_schedules ──────────────────────────────────────────
            if (Schema::connection($conn)->hasTable('pending_schedules')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) use ($conn) {
                    if (!Schema::connection($conn)->hasColumn('pending_schedules', 'grade_level')) {
                        $table->string('grade_level', 20)->nullable()->after('subject');
                    }
                    if (!Schema::connection($conn)->hasColumn('pending_schedules', 'section_name')) {
                        $table->string('section_name', 30)->nullable()->after('grade_level');
                    }
                });
                if (Schema::connection($conn)->hasColumn('pending_schedules', 'grade_section')) {
                    Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) {
                        $table->dropColumn('grade_section');
                    });
                }
            }

            // ── rejected_schedules ────────────────────────────────────────
            if (Schema::connection($conn)->hasTable('rejected_schedules')) {
                Schema::connection($conn)->table('rejected_schedules', function (Blueprint $table) use ($conn) {
                    if (!Schema::connection($conn)->hasColumn('rejected_schedules', 'grade_level')) {
                        $table->string('grade_level', 20)->nullable()->after('subject');
                    }
                    if (!Schema::connection($conn)->hasColumn('rejected_schedules', 'section_name')) {
                        $table->string('section_name', 30)->nullable()->after('grade_level');
                    }
                });
                if (Schema::connection($conn)->hasColumn('rejected_schedules', 'grade_section')) {
                    Schema::connection($conn)->table('rejected_schedules', function (Blueprint $table) {
                        $table->dropColumn('grade_section');
                    });
                }
            }
        }

        // 4. Recreate triggers without grade_section ───────────────────────────
        foreach ($this->schoolConns as $conn) {
            $this->recreateAuditTriggers($conn);
            $this->recreatePendingTriggers($conn);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        // Re-add grade_section to class_schedules
        foreach (['mysql', ...$this->schoolConns] as $conn) {
            if (!Schema::connection($conn)->hasColumn('class_schedules', 'grade_section')) {
                Schema::connection($conn)->table('class_schedules', function (Blueprint $table) {
                    $table->string('grade_section', 100)->nullable();
                });
            }
        }

        // Re-add grade_section to pending_schedules / rejected_schedules
        foreach ($this->schoolConns as $conn) {
            if (Schema::connection($conn)->hasTable('pending_schedules')
                && !Schema::connection($conn)->hasColumn('pending_schedules', 'grade_section')) {
                Schema::connection($conn)->table('pending_schedules', function (Blueprint $table) {
                    $table->string('grade_section', 100)->nullable();
                });
            }
            if (Schema::connection($conn)->hasTable('rejected_schedules')
                && !Schema::connection($conn)->hasColumn('rejected_schedules', 'grade_section')) {
                Schema::connection($conn)->table('rejected_schedules', function (Blueprint $table) {
                    $table->string('grade_section', 100)->nullable();
                });
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function recreateAuditTriggers(string $conn): void
    {
        $cols     = $this->auditCols;
        $newPairs = implode(', ', array_map(fn ($c) => "'$c', NEW.`$c`", $cols));
        $oldPairs = implode(', ', array_map(fn ($c) => "'$c', OLD.`$c`", $cols));

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_insert`
            AFTER INSERT ON `class_schedules`
            FOR EACH ROW
            INSERT INTO `audit_logs` (table_name, record_id, action, old_data, new_data, changed_by, changed_at)
            VALUES ('class_schedules', NEW.id, 'INSERT', NULL, JSON_OBJECT($newPairs), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_update`
            AFTER UPDATE ON `class_schedules`
            FOR EACH ROW
            INSERT INTO `audit_logs` (table_name, record_id, action, old_data, new_data, changed_by, changed_at)
            VALUES ('class_schedules', NEW.id, 'UPDATE', JSON_OBJECT($oldPairs), JSON_OBJECT($newPairs), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `class_schedules_after_delete`
            AFTER DELETE ON `class_schedules`
            FOR EACH ROW
            INSERT INTO `audit_logs` (table_name, record_id, action, old_data, new_data, changed_by, changed_at)
            VALUES ('class_schedules', OLD.id, 'DELETE', JSON_OBJECT($oldPairs), NULL, @audit_user, NOW())
        ");
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function recreatePendingTriggers(string $conn): void
    {
        DB::connection($conn)->unprepared("
            CREATE TRIGGER `pending_schedules_ai`
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
            END
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `pending_schedules_au`
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
};
