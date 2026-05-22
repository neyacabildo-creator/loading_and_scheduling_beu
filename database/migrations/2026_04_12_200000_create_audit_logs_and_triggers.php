<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates an audit_logs table and AFTER INSERT / AFTER UPDATE / AFTER DELETE
 * triggers on every operational table in both school databases (mysql_jh and
 * mysql_gs).
 *
 * Triggers are written as single-statement bodies (no BEGIN…END) so that
 * PHP's PDO driver does not misinterpret internal semicolons.
 *
 * The optional @audit_user MySQL session variable can be set per-request via
 *   DB::connection($conn)->statement('SET @audit_user = ?', [Auth::id()])
 * to record which application user triggered the change.  It falls back to
 * NULL when not set.
 */
return new class extends Migration
{
    /** Tables to instrument, mapped to the columns captured in the JSON snapshot. */
    private array $tables = [
        'class_schedules' => [
            'id','faculty_id','subject','grade_section','room_id',
            'day_of_week','start_time','end_time','student_count',
            'status','admin_approved','approved_at','approved_by','schedule_date',
        ],
        'rooms' => [
            'id','room_number','building','capacity',
            'has_laboratory','has_projector','has_ac','status',
        ],
        'faculty_loads' => [
            'id','faculty_id','subject','department',
            'classes_assigned','load_hours','status',
        ],
        'dss_recommendations' => [
            'id','type','priority','status','related_faculty_id',
        ],
        'export_logs' => [
            'id','format','filename','status','created_by',
        ],
        'schedule_approvals' => [
            'id','schedule_id','submitted_by','status',
            'reviewed_by','reviewed_at','admin_notes','revision_count',
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {

            // 1. Audit log table ─────────────────────────────────────────────
            if (!Schema::connection($conn)->hasTable('audit_logs')) {
                Schema::connection($conn)->create('audit_logs', function (Blueprint $t) {
                    $t->id();
                    $t->string('table_name', 100);
                    $t->unsignedBigInteger('record_id')->nullable();
                    $t->enum('action', ['INSERT', 'UPDATE', 'DELETE']);
                    $t->json('old_data')->nullable();   // populated on UPDATE / DELETE
                    $t->json('new_data')->nullable();   // populated on INSERT / UPDATE
                    $t->unsignedBigInteger('changed_by')->nullable(); // set via @audit_user
                    $t->timestamp('changed_at')->useCurrent();

                    $t->index(['table_name', 'record_id']);
                    $t->index('changed_at');
                    $t->index('action');
                });
            }

            // 2. Triggers ────────────────────────────────────────────────────
            foreach ($this->tables as $table => $columns) {
                if (!Schema::connection($conn)->hasTable($table)) {
                    continue;
                }
                $this->makeTriggers($conn, $table, $columns);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            foreach (array_keys($this->tables) as $table) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_insert`");
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_update`");
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_delete`");
            }
            Schema::connection($conn)->dropIfExists('audit_logs');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function makeTriggers(string $conn, string $table, array $cols): void
    {
        // Build the JSON_OBJECT argument lists once
        $newPairs = implode(', ', array_map(fn ($c) => "'$c', NEW.`$c`", $cols));
        $oldPairs = implode(', ', array_map(fn ($c) => "'$c', OLD.`$c`", $cols));

        // Drop any pre-existing triggers so this migration is re-runnable
        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_insert`");
        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_update`");
        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_delete`");

        // ── AFTER INSERT ────────────────────────────────────────────────────
        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_insert`
            AFTER INSERT ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'INSERT', JSON_OBJECT($newPairs), @audit_user, NOW())
        ");

        // ── AFTER UPDATE ────────────────────────────────────────────────────
        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_update`
            AFTER UPDATE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'UPDATE', JSON_OBJECT($oldPairs), JSON_OBJECT($newPairs), @audit_user, NOW())
        ");

        // ── AFTER DELETE ────────────────────────────────────────────────────
        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_delete`
            AFTER DELETE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', OLD.id, 'DELETE', JSON_OBJECT($oldPairs), @audit_user, NOW())
        ");
    }
};
