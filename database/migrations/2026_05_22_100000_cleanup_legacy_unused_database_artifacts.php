<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes legacy tables superseded by school-level databases (mysql_jh / mysql_gs).
 * Safe to run multiple times (dropIfExists only).
 */
return new class extends Migration
{
    /** Main DB — auth/users only; operational data lives in school DBs. */
    private array $mainDbLegacyTables = [
        'grade_submissions',
        'shared_teacher_blocks',
        'shared_teacher_schedule_requests',
        'teacher_requests',
        'pending_schedules',
        'class_schedules',
        'faculty_loads',
        'rooms',
        'dss_recommendations',
        'export_logs',
        'schedule_approvals',
        'subject_assignments',
        'schedule_adjustment_requests',
    ];

    private array $schoolConnections = ['mysql_jh', 'mysql_gs'];

    /** Superseded tables on school admin DBs (replaced by shared_teacher_requests, etc.). */
    private array $schoolDbLegacyTables = [
        'grade_submissions',
        'shared_teacher_blocks',
        'shared_teacher_schedule_requests',
    ];

    public function up(): void
    {
        foreach ($this->mainDbLegacyTables as $table) {
            if (Schema::connection('mysql')->hasTable($table)) {
                Schema::connection('mysql')->drop($table);
            }
        }

        foreach ($this->schoolConnections as $conn) {
            foreach ($this->schoolDbLegacyTables as $table) {
                Schema::connection($conn)->dropIfExists($table);
            }
        }

        // Drop sync triggers that targeted the old main-DB pending_schedules table.
        foreach ($this->schoolConnections as $conn) {
            foreach (['pending_schedules_ai', 'pending_schedules_au', 'pending_schedules_ad'] as $trigger) {
                try {
                    DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `{$trigger}`");
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            try {
                DB::connection($conn)->statement('DROP VIEW IF EXISTS `pending_schedules`');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty — legacy artifacts should not be recreated.
    }
};
