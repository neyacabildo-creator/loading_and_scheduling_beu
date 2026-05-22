<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend the class_schedules.status ENUM to include 'rejected'
     * on both school databases.
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            try {
                DB::connection($conn)->statement(
                    "ALTER TABLE class_schedules MODIFY COLUMN `status` ENUM('pending','active','completed','rejected') NOT NULL DEFAULT 'pending'"
                );
            } catch (\Exception $e) {
                // Log but don't fail if one connection isn't available
                \Illuminate\Support\Facades\Log::warning("Could not alter class_schedules.status on {$conn}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            try {
                DB::connection($conn)->statement(
                    "ALTER TABLE class_schedules MODIFY COLUMN `status` ENUM('pending','active','completed') NOT NULL DEFAULT 'pending'"
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not revert class_schedules.status on {$conn}: " . $e->getMessage());
            }
        }
    }
};
