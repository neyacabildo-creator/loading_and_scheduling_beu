<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills grade_level and section_name in pending_schedules from class_schedules.
 * Also backfills rejected_schedules for completeness.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            // Backfill pending_schedules
            DB::connection($conn)->statement("
                UPDATE pending_schedules ps
                JOIN class_schedules cs ON cs.id = ps.schedule_id
                SET ps.grade_level  = cs.grade_level,
                    ps.section_name = cs.section_name
                WHERE (ps.grade_level IS NULL OR ps.grade_level = '')
                   OR (ps.section_name IS NULL OR ps.section_name = '')
            ");

            // Backfill rejected_schedules if the columns exist
            try {
                DB::connection($conn)->statement("
                    UPDATE rejected_schedules rs
                    JOIN class_schedules cs ON cs.id = rs.schedule_id
                    SET rs.grade_level  = cs.grade_level,
                        rs.section_name = cs.section_name
                    WHERE (rs.grade_level IS NULL OR rs.grade_level = '')
                       OR (rs.section_name IS NULL OR rs.section_name = '')
                ");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("backfill rejected_schedules ({$conn}): " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Not reversible — we never want to set grade_level/section_name back to NULL
    }
};
