<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasTable('class_schedules')) {
                continue;
            }
            if (Schema::connection($connection)->hasColumn('class_schedules', 'student_count')) {
                Schema::connection($connection)->table('class_schedules', function (Blueprint $table) {
                    $table->dropColumn('student_count');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasTable('class_schedules')) {
                continue;
            }
            if (!Schema::connection($connection)->hasColumn('class_schedules', 'student_count')) {
                Schema::connection($connection)->table('class_schedules', function (Blueprint $table) {
                    $table->unsignedInteger('student_count')->nullable()->after('end_time');
                });
            }
        }
    }
};
