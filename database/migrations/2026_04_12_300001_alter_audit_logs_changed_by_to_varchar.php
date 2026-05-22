<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Changes the changed_by column in audit_logs from BIGINT UNSIGNED (user ID)
 * to VARCHAR(191) so that MySQL triggers can store the user's name directly
 * via the @audit_user session variable.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            DB::connection($conn)->statement(
                "ALTER TABLE `audit_logs` MODIFY COLUMN `changed_by` VARCHAR(191) NULL"
            );
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            // Wipe text values first so we can revert to integer
            DB::connection($conn)->statement("UPDATE `audit_logs` SET `changed_by` = NULL");
            DB::connection($conn)->statement(
                "ALTER TABLE `audit_logs` MODIFY COLUMN `changed_by` BIGINT UNSIGNED NULL"
            );
        }
    }
};
