<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates audit_logs and AFTER INSERT/UPDATE/DELETE triggers on school DB tables.
 * Column lists are resolved at runtime so triggers match the actual schema
 * (grade_section vs grade_level/section_name, etc.).
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $tableColumnCandidates = [
        'class_schedules' => [
            'id', 'faculty_id', 'subject', 'grade_section', 'grade_level', 'section_name',
            'room_id', 'day_of_week', 'schedule_date', 'start_time', 'end_time', 'student_count',
            'status', 'admin_approved', 'approved_at', 'approved_by', 'version',
            'last_modified_by_admin', 'change_log', 'principal_approved',
            'principal_approved_at', 'principal_approved_by',
        ],
        'rooms' => [
            'id', 'room_number', 'building', 'capacity', 'school_level',
            'has_laboratory', 'has_projector', 'has_ac', 'status',
        ],
        'faculty_loads' => [
            'id', 'faculty_id', 'teacher_name', 'subject', 'department', 'grade_level',
            'classes_assigned', 'load_hours', 'status', 'notes',
        ],
        'dss_recommendations' => [
            'id', 'type', 'priority', 'issue', 'solution', 'status', 'related_faculty_id',
        ],
        'export_logs' => [
            'id', 'format', 'filename', 'file_path', 'file_size', 'status', 'created_by',
        ],
        'schedule_approvals' => [
            'id', 'schedule_id', 'submitted_by', 'status',
            'reviewed_by', 'reviewed_at', 'admin_notes', 'revision_count',
        ],
    ];

    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('audit_logs')) {
                Schema::connection($conn)->create('audit_logs', function (Blueprint $t) {
                    $t->id();
                    $t->string('table_name', 100);
                    $t->unsignedBigInteger('record_id')->nullable();
                    $t->enum('action', ['INSERT', 'UPDATE', 'DELETE']);
                    $t->json('old_data')->nullable();
                    $t->json('new_data')->nullable();
                    $t->unsignedBigInteger('changed_by')->nullable();
                    $t->timestamp('changed_at')->useCurrent();

                    $t->index(['table_name', 'record_id']);
                    $t->index('changed_at');
                    $t->index('action');
                });
            }

            foreach ($this->tableColumnCandidates as $table => $candidates) {
                if (! Schema::connection($conn)->hasTable($table)) {
                    continue;
                }

                $columns = $this->resolveColumns($conn, $table, $candidates);
                if ($columns === []) {
                    continue;
                }

                $this->makeTriggers($conn, $table, $columns);
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            foreach (array_keys($this->tableColumnCandidates) as $table) {
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_insert`");
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_update`");
                DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_delete`");
            }
            Schema::connection($conn)->dropIfExists('audit_logs');
        }
    }

    /** @param list<string> $candidates */
    private function resolveColumns(string $conn, string $table, array $candidates): array
    {
        return array_values(array_filter(
            $candidates,
            fn (string $col) => Schema::connection($conn)->hasColumn($table, $col)
        ));
    }

    /** @param list<string> $cols */
    private function makeTriggers(string $conn, string $table, array $cols): void
    {
        $newPairs = implode(', ', array_map(fn ($c) => "'{$c}', NEW.`{$c}`", $cols));
        $oldPairs = implode(', ', array_map(fn ($c) => "'{$c}', OLD.`{$c}`", $cols));

        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_insert`");
        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_update`");
        DB::connection($conn)->unprepared("DROP TRIGGER IF EXISTS `{$table}_after_delete`");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_insert`
            AFTER INSERT ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'INSERT', JSON_OBJECT({$newPairs}), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_update`
            AFTER UPDATE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `new_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', NEW.id, 'UPDATE', JSON_OBJECT({$oldPairs}), JSON_OBJECT({$newPairs}), @audit_user, NOW())
        ");

        DB::connection($conn)->unprepared("
            CREATE TRIGGER `{$table}_after_delete`
            AFTER DELETE ON `{$table}`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`table_name`, `record_id`, `action`, `old_data`, `changed_by`, `changed_at`)
            VALUES
                ('{$table}', OLD.id, 'DELETE', JSON_OBJECT({$oldPairs}), @audit_user, NOW())
        ");
    }
};
