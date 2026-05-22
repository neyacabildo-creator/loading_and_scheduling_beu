<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates a `rejected_schedules` TABLE inside each school database
 * (loading_scheduling_jh and loading_scheduling_gs).
 *
 * AFTER INSERT / UPDATE / DELETE triggers on class_schedules keep this table
 * in sync automatically:
 *   - A row is added/updated when status becomes 'rejected'
 *   - A row is removed when the class_schedule is deleted or un-rejected
 *
 * The table is also seeded from any already-rejected rows in class_schedules.
 */
return new class extends Migration
{
    private array $schools = [
        'mysql_jh' => 'junior_high',
        'mysql_gs' => 'grade_school',
    ];

    public function up(): void
    {
        foreach ($this->schools as $conn => $level) {

            // ── 1. Create the table ────────────────────────────────────────────
            Schema::connection($conn)->create('rejected_schedules', function (Blueprint $table) {
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
                $table->text('rejection_reason')->nullable();  // from change_log / admin_notes
                $table->unsignedBigInteger('rejected_by')->nullable();  // user id
                $table->string('rejected_by_name', 191)->nullable();    // user name
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->index('faculty_id');
                $table->index('rejected_at');
            });

            // ── 2. Seed from already-rejected class_schedules ─────────────────
            try {
                $rows = DB::connection($conn)->select(
                    "SELECT cs.id, cs.faculty_id, cs.subject, cs.grade_section, cs.room_id,
                            cs.day_of_week, cs.schedule_date, cs.start_time, cs.end_time,
                            cs.student_count, cs.change_log, cs.approved_by,
                            cs.created_at, cs.updated_at,
                            sa.admin_notes, sa.reviewed_by, sa.reviewed_at
                     FROM class_schedules cs
                     LEFT JOIN schedule_approvals sa ON sa.schedule_id = cs.id
                     WHERE cs.status = 'rejected'"
                );

                foreach ($rows as $r) {
                    // Try to extract reason from change_log JSON
                    $reason = null;
                    if ($r->admin_notes) {
                        $reason = $r->admin_notes;
                    } elseif ($r->change_log) {
                        $log = json_decode($r->change_log, true);
                        if (is_array($log)) {
                            foreach (array_reverse($log) as $entry) {
                                if (($entry['action'] ?? '') === 'rejected') {
                                    $reason = $entry['reason'] ?? null;
                                    break;
                                }
                            }
                        }
                    }

                    DB::connection($conn)->table('rejected_schedules')->insertOrIgnore([
                        'schedule_id'      => $r->id,
                        'faculty_id'       => $r->faculty_id,
                        'subject'          => $r->subject,
                        'grade_section'    => $r->grade_section,
                        'room_id'          => $r->room_id,
                        'day_of_week'      => $r->day_of_week,
                        'schedule_date'    => $r->schedule_date,
                        'start_time'       => $r->start_time,
                        'end_time'         => $r->end_time,
                        'student_count'    => $r->student_count,
                        'rejection_reason' => $reason,
                        'rejected_by'      => $r->reviewed_by ?? $r->approved_by,
                        'rejected_at'      => $r->reviewed_at ?? $r->updated_at,
                        'created_at'       => $r->created_at,
                        'updated_at'       => $r->updated_at,
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("rejected_schedules seed ({$level}): " . $e->getMessage());
            }

            // ── 3. AFTER INSERT trigger ────────────────────────────────────────
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `rejected_schedules_ai`;
                CREATE TRIGGER `rejected_schedules_ai`
                AFTER INSERT ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_section, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, NOW(), NOW(), NOW())
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
                            rejected_at   = NOW(),
                            updated_at    = NOW();
                    END IF;
                END
            ");

            // ── 4. AFTER UPDATE trigger ────────────────────────────────────────
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `rejected_schedules_au`;
                CREATE TRIGGER `rejected_schedules_au`
                AFTER UPDATE ON `class_schedules`
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'rejected' THEN
                        INSERT INTO `rejected_schedules`
                            (schedule_id, faculty_id, subject, grade_section, room_id,
                             day_of_week, schedule_date, start_time, end_time,
                             student_count, rejected_at, created_at, updated_at)
                        VALUES
                            (NEW.id, NEW.faculty_id, NEW.subject, NEW.grade_section, NEW.room_id,
                             NEW.day_of_week, NEW.schedule_date, NEW.start_time, NEW.end_time,
                             NEW.student_count, NOW(), NOW(), NOW())
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
                            rejected_at   = IF(OLD.status != 'rejected', NOW(), rejected_at),
                            updated_at    = NOW();
                    ELSE
                        -- Schedule was un-rejected (re-approved or edited back to pending)
                        DELETE FROM `rejected_schedules` WHERE schedule_id = NEW.id;
                    END IF;
                END
            ");

            // ── 5. AFTER DELETE trigger ────────────────────────────────────────
            DB::connection($conn)->unprepared("
                DROP TRIGGER IF EXISTS `rejected_schedules_ad`;
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
        foreach (array_keys($this->schools) as $conn) {
            foreach (['rejected_schedules_ai', 'rejected_schedules_au', 'rejected_schedules_ad'] as $trigger) {
                try {
                    DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `{$trigger}`");
                } catch (\Exception $e) {}
            }
            Schema::connection($conn)->dropIfExists('rejected_schedules');
        }
    }
};
