<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add principal_approved columns to class_schedules on both school databases.
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            Schema::connection($conn)->table('class_schedules', function (Blueprint $table) use ($conn) {
                if (!Schema::connection($conn)->hasColumn('class_schedules', 'principal_approved')) {
                    $table->boolean('principal_approved')->default(false)->after('admin_approved');
                }
                if (!Schema::connection($conn)->hasColumn('class_schedules', 'principal_approved_at')) {
                    $table->timestamp('principal_approved_at')->nullable()->after('principal_approved');
                }
                if (!Schema::connection($conn)->hasColumn('class_schedules', 'principal_approved_by')) {
                    $table->unsignedBigInteger('principal_approved_by')->nullable()->after('principal_approved_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            Schema::connection($conn)->table('class_schedules', function (Blueprint $table) {
                $table->dropColumn(['principal_approved', 'principal_approved_at', 'principal_approved_by']);
            });
        }
    }
};
