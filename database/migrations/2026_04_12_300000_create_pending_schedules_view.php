<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Creates a real `pending_schedules` TABLE inside each school database
 * (loading_scheduling_jh and loading_scheduling_gs).
 *
 * AFTER INSERT / UPDATE / DELETE triggers on class_schedules keep the table
 * in sync automatically.
 *
 * The previous version placed this table in the shared loading_scheduling DB —
 * that table and its triggers have been removed.
 */
return new class extends Migration
{
    /** School DB connection names mapped to their human-readable level */
    private array $schools = [
        'mysql_jh' => 'junior_high',
        'mysql_gs' => 'grade_school',
    ];

    public function up(): void
    {
        // ── Remove old main-DB table + school-DB sync triggers if they exist ──
        $this->cleanupOldSetup();

        // ── Create pending_schedules TABLE in each school DB ──────────────────
        foreach ($this->schools as $conn => $level) {
            // Create table
            Schema::connection($conn)->create('pending_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id')->unique();
                $table->unsignedBigInteger('faculty_id')->nullable();
                $table->string('subject', 255)->nullable();
                $table->string('grade_section', 100)->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->string('day_of_week', 20)->nullable();
                $table->date('schedule_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedInteger('student_count')->nullable();
                $table->string('status', 30)->default('pending');
                $table->boolean('admin_approved')->default(false);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->index('status');
            });

            // Seed from existing pending class_schedules
            try {
                $rows = DB::connection($conn)->select(
                    "SELECT id, faculty_id, subject, grade_section, room_id,
                            day_of_week, schedule_date, start_time, end_time,
                            student_count, status, admin_approved, created_at, updated_at
                     FROM class_schedules
                     WHERE admin_approved = 0
                       AND (status = 'pending' OR status IS NULL)"
                );
                foreach ($rows as $r) {
                    DB::connection($conn)->table('pending_schedules')->insertOrIgnore([
                        'schedule_id'   => $r->id,
                        'faculty_id'    => $r->faculty_id,
                        'subject'       => $r->subject,
                        'grade_section' => $r->grade_section,
                        'room_id'       => $r->room_id,
                        'day_of_week'   => $r->day_of_week,
                        'schedule_date' => $r->schedule_date,
                        'start_time'    => $r->start_time,
                        'end_time'      => $r->end_time,
                        'student_count' => $r->student_count,
                        'status'        => $r->status ?? 'pending',
                        'admin_approved'=> $r->admin_approved,
                        'submitted_at'  => $r->created_at,
                        'created_at'    => $r->created_at,
                        'updated_at'    => $r->updated_at,
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("pending_schedules seed ($level): " . $e->getMessage());
            }

            // Install AFTER INSERT trigger
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `pending_schedules_ai`;
                CREATE TRIGGER `pending_schedules_ai`
                AFTER INSERT ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                        INSERT INTO `pending_schedules`
                            (schedule_id, faculty_id, subject, grade_section, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, status, admin_approved, submitted_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                             NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_section = NEW.grade_section,
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

            // Install AFTER UPDATE trigger
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `pending_schedules_au`;
                CREATE TRIGGER `pending_schedules_au`
                AFTER UPDATE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                        INSERT INTO `pending_schedules`
                            (schedule_id, faculty_id, subject, grade_section, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, status, admin_approved, submitted_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                             NOW(), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            faculty_id    = NEW.faculty_id,
                            subject       = NEW.subject,
                            grade_section = NEW.grade_section,
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

            // Install AFTER DELETE trigger
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `pending_schedules_ad`;
                CREATE TRIGGER `pending_schedules_ad`
                AFTER DELETE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    DELETE FROM `pending_schedules` WHERE schedule_id = OLD.id;
                END
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->schools as $conn => $level) {
            foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $t) {
                try { DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `$t`"); } catch (\Exception $e) {}
            }
            Schema::connection($conn)->dropIfExists('pending_schedules');
        }
    }

    private function cleanupOldSetup(): void
    {
        // Remove the 3 cross-DB sync triggers from school DBs (old approach)
        foreach (array_keys($this->schools) as $conn) {
            foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $t) {
                try { DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `$t`"); } catch (\Exception $e) {}
            }
            // Also remove old VIEWs if still present
            try { DB::connection($conn)->statement('DROP VIEW IF EXISTS `pending_schedules`'); } catch (\Exception $e) {}
        }

        // Drop the old table from the main DB if it exists
        Schema::connection('mysql')->dropIfExists('pending_schedules');
    }
};

/**
 * Creates a single `pending_schedules` TABLE in the main `loading_scheduling`
 * database that consolidates pending schedules from both JH and GS school DBs.
 *
 * AFTER INSERT / AFTER UPDATE / AFTER DELETE triggers in each school DB
 * automatically keep this table in sync whenever a class_schedule row changes.
 *
 * The VIEW that previously existed in the school-specific DBs has been removed.
 */
return new class extends Migration
{
    private string $main = 'loading_scheduling';
    private string $jh   = 'loading_scheduling_jh';
    private string $gs   = 'loading_scheduling_gs';

    public function up(): void
    {
        // ── 1. Create consolidated pending_schedules TABLE in main DB ──────────
        Schema::connection('mysql')->create('pending_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('school_level', 30);            // 'junior_high' | 'grade_school'
            $table->unsignedBigInteger('schedule_id');      // PK from school DB
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('grade_section', 100)->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('day_of_week', 20)->nullable();
            $table->date('schedule_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->string('status', 30)->default('pending');
            $table->boolean('admin_approved')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Enforce one row per school + schedule pair
            $table->unique(['school_level', 'schedule_id']);
            $table->index('school_level');
            $table->index('status');
        });

        // ── 2. Seed from existing pending rows in both school DBs ─────────────
        $this->seedSchool('junior_high', $this->jh);
        $this->seedSchool('grade_school', $this->gs);

        // ── 3. Install sync triggers in JH school DB ──────────────────────────
        $this->installTriggers('mysql_jh', 'junior_high');

        // ── 4. Install sync triggers in GS school DB ──────────────────────────
        $this->installTriggers('mysql_gs', 'grade_school');
    }

    public function down(): void
    {
        // Drop triggers first, then the table
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $t) {
                try { DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `$t`"); } catch (\Exception $e) {}
            }
        }
        Schema::connection('mysql')->dropIfExists('pending_schedules');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function seedSchool(string $level, string $dbName): void
    {
        try {
            $rows = DB::connection($level === 'junior_high' ? 'mysql_jh' : 'mysql_gs')
                ->select("SELECT id, faculty_id, subject, grade_section, room_id,
                                  day_of_week, schedule_date, start_time, end_time,
                                  student_count, status, admin_approved, created_at, updated_at
                           FROM class_schedules
                           WHERE admin_approved = 0
                             AND (status = 'pending' OR status IS NULL)");

            foreach ($rows as $r) {
                DB::connection('mysql')->table('pending_schedules')->updateOrInsert(
                    ['school_level' => $level, 'schedule_id' => $r->id],
                    [
                        'faculty_id'    => $r->faculty_id,
                        'subject'       => $r->subject,
                        'grade_section' => $r->grade_section,
                        'room_id'       => $r->room_id,
                        'day_of_week'   => $r->day_of_week,
                        'schedule_date' => $r->schedule_date,
                        'start_time'    => $r->start_time,
                        'end_time'      => $r->end_time,
                        'student_count' => $r->student_count,
                        'status'        => $r->status ?? 'pending',
                        'admin_approved'=> $r->admin_approved,
                        'submitted_at'  => $r->created_at,
                        'created_at'    => $r->created_at,
                        'updated_at'    => $r->updated_at,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("pending_schedules seed ($level): " . $e->getMessage());
        }
    }

    private function installTriggers(string $conn, string $level): void
    {
        $main = $this->main;

        // AFTER INSERT → add to main table if still pending
        DB::connection($conn)->unprepared("
            DROP TRIGGER IF EXISTS `pending_schedules_ai`;
            CREATE TRIGGER `pending_schedules_ai`
            AFTER INSERT ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `{$main}`.`pending_schedules`
                        (school_level, schedule_id, faculty_id, subject, grade_section,
                         room_id, day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        ('{$level}', NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section,
                         NEW.room_id, NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id    = NEW.faculty_id,
                        subject       = NEW.subject,
                        grade_section = NEW.grade_section,
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

        // AFTER UPDATE → upsert or delete from main table depending on approval state
        DB::connection($conn)->unprepared("
            DROP TRIGGER IF EXISTS `pending_schedules_au`;
            CREATE TRIGGER `pending_schedules_au`
            AFTER UPDATE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_approved = 0 AND (NEW.status = 'pending' OR NEW.status IS NULL) THEN
                    INSERT INTO `{$main}`.`pending_schedules`
                        (school_level, schedule_id, faculty_id, subject, grade_section,
                         room_id, day_of_week, schedule_date, start_time, end_time,
                         student_count, status, admin_approved, submitted_at, created_at, updated_at)
                    VALUES
                        ('{$level}', NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section,
                         NEW.room_id, NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                         NEW.student_count, COALESCE(NEW.status,'pending'), NEW.admin_approved,
                         NOW(), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        faculty_id    = NEW.faculty_id,
                        subject       = NEW.subject,
                        grade_section = NEW.grade_section,
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
                    DELETE FROM `{$main}`.`pending_schedules`
                    WHERE school_level = '{$level}' AND schedule_id = NEW.id;
                END IF;
            END
        ");

        // AFTER DELETE → remove from main table
        DB::connection($conn)->unprepared("
            DROP TRIGGER IF EXISTS `pending_schedules_ad`;
            CREATE TRIGGER `pending_schedules_ad`
            AFTER DELETE ON `class_schedules`
            FOR EACH ROW
            BEGIN
                DELETE FROM `{$main}`.`pending_schedules`
                WHERE school_level = '{$level}' AND schedule_id = OLD.id;
            END
        ");
    }
};
