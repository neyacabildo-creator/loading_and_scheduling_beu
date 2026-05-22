<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure class_schedules.status supports rejected and deleted values
     */
    public function up(): void
    {
        $connections = ['mysql_jh', 'mysql_gs'];

        foreach ($connections as $conn) {
            if (Schema::connection($conn)->hasTable('class_schedules')) {
                DB::connection($conn)->statement(
                    "ALTER TABLE `class_schedules` MODIFY `status` ENUM('pending', 'active', 'completed', 'rejected', 'deleted', 'inactive') NOT NULL DEFAULT 'pending'"
                );
            }
        }
    }

    public function down(): void
    {
        $connections = ['mysql_jh', 'mysql_gs'];

        foreach ($connections as $conn) {
            if (Schema::connection($conn)->hasTable('class_schedules')) {
                DB::connection($conn)->statement(
                    "ALTER TABLE `class_schedules` MODIFY `status` ENUM('pending', 'active', 'completed') NOT NULL DEFAULT 'pending'"
                );
            }
        }
    }
};
