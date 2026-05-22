<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Backfills grade_level, section_name, and subject_handled in master_weekly_schedules
 *    from matching approved class_schedules (matched by faculty_id + day_of_week + start_time).
 * 2. Drops the legacy section_students column from both school DBs.
 */
return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            // Backfill grade_level, section_name, subject_handled from class_schedules
            if (Schema::connection($conn)->hasTable('master_weekly_schedules')
                && Schema::connection($conn)->hasTable('class_schedules')) {

                DB::connection($conn)->statement("
                    UPDATE master_weekly_schedules m
                    INNER JOIN (
                        SELECT faculty_id, day_of_week, start_time,
                               grade_level, section_name, UPPER(subject) AS subject
                        FROM class_schedules
                        WHERE admin_approved = 1
                          AND status = 'active'
                    ) cs ON cs.faculty_id  = m.faculty_id
                         AND cs.day_of_week = m.day_of_week
                         AND cs.start_time  = m.time_start
                    SET m.grade_level    = COALESCE(NULLIF(TRIM(m.grade_level), ''),    cs.grade_level),
                        m.section_name   = COALESCE(NULLIF(TRIM(m.section_name), ''),   cs.section_name),
                        m.subject_handled= COALESCE(NULLIF(TRIM(m.subject_handled), ''),cs.subject)
                    WHERE m.entry_type = 'class'
                ");
            }

            // Drop section_students column
            if (Schema::connection($conn)->hasTable('master_weekly_schedules')
                && Schema::connection($conn)->hasColumn('master_weekly_schedules', 'section_students')) {
                Schema::connection($conn)->table('master_weekly_schedules', function (Blueprint $table) {
                    $table->dropColumn('section_students');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            if (Schema::connection($conn)->hasTable('master_weekly_schedules')
                && !Schema::connection($conn)->hasColumn('master_weekly_schedules', 'section_students')) {
                Schema::connection($conn)->table('master_weekly_schedules', function (Blueprint $table) {
                    $table->text('section_students')->nullable()->after('section_name');
                });
            }
        }
    }
};
