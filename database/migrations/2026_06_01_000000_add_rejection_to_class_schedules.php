<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add rejection tracking columns to class_schedules on school databases.
     * (Main DB copy was removed in 2026_05_22_100000_cleanup_legacy_unused_database_artifacts.)
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            Schema::connection($conn)->table('class_schedules', function (Blueprint $table) use ($conn) {
                if (! Schema::connection($conn)->hasColumn('class_schedules', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('approved_at');
                }
                if (! Schema::connection($conn)->hasColumn('class_schedules', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('rejected_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            Schema::connection($conn)->table('class_schedules', function (Blueprint $table) use ($conn) {
                $columns = [];
                if (Schema::connection($conn)->hasColumn('class_schedules', 'rejected_at')) {
                    $columns[] = 'rejected_at';
                }
                if (Schema::connection($conn)->hasColumn('class_schedules', 'rejection_reason')) {
                    $columns[] = 'rejection_reason';
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
