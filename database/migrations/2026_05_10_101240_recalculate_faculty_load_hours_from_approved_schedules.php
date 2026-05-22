<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 1. Backfill grade_level + section_name in pending_schedules from class_schedules.
 * 2. Remove already-approved/rejected schedules from pending_schedules.
 * 3. Recalculate load_hours for all faculty_loads based on approved class_schedules.
 */
return new class extends Migration
{
    private array $schoolConns = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConns as $conn) {
            // 1. Backfill pending_schedules grade_level + section_name
            try {
                DB::connection($conn)->statement("
                    UPDATE pending_schedules ps
                    JOIN class_schedules cs ON cs.id = ps.schedule_id
                    SET ps.grade_level  = cs.grade_level,
                        ps.section_name = cs.section_name
                    WHERE (ps.grade_level IS NULL OR ps.grade_level = '')
                       OR (ps.section_name IS NULL OR ps.section_name = '')
                ");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("backfill pending_schedules grade ({$conn}): " . $e->getMessage());
            }

            // 2. Remove approved/rejected schedules from pending_schedules
            try {
                DB::connection($conn)->statement("
                    DELETE ps FROM pending_schedules ps
                    JOIN class_schedules cs ON cs.id = ps.schedule_id
                    WHERE cs.admin_approved = 1 OR cs.status IN ('active', 'rejected')
                ");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("cleanup pending_schedules ({$conn}): " . $e->getMessage());
            }

            // 3. Recalculate load_hours for all faculty loads from approved schedules
            try {
                $rows = DB::connection($conn)->select("
                    SELECT faculty_id,
                           ROUND(COALESCE(SUM(
                               HOUR(TIMEDIFF(end_time, start_time)) + MINUTE(TIMEDIFF(end_time, start_time)) / 60.0
                           ), 0), 2) AS total_hours
                    FROM class_schedules
                    WHERE admin_approved = 1
                      AND status NOT IN ('rejected')
                      AND faculty_id IS NOT NULL
                    GROUP BY faculty_id
                ");

                foreach ($rows as $row) {
                    $hours  = (float) $row->total_hours;
                    $status = $hours > 6 ? 'overloaded' : 'active';

                    DB::connection($conn)->table('faculty_loads')
                        ->where('faculty_id', $row->faculty_id)
                        ->whereIn('status', ['active', 'overloaded', 'inactive'])
                        ->update([
                            'load_hours' => $hours,
                            'status'     => $status,
                            'updated_at' => now(),
                        ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("recalculate load_hours ({$conn}): " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};

