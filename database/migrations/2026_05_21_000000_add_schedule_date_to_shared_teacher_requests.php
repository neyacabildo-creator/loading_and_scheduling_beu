<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                continue;
            }

            Schema::connection($connection)->table('shared_teacher_requests', function (Blueprint $table) use ($connection) {
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'schedule_date')) {
                    $table->date('schedule_date')->nullable()->after('day_of_week');
                }
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'schedule_id')) {
                    $table->unsignedBigInteger('schedule_id')->nullable()->after('schedule_date');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                continue;
            }

            Schema::connection($connection)->table('shared_teacher_requests', function (Blueprint $table) use ($connection) {
                if (Schema::connection($connection)->hasColumn('shared_teacher_requests', 'schedule_id')) {
                    $table->dropColumn('schedule_id');
                }
                if (Schema::connection($connection)->hasColumn('shared_teacher_requests', 'schedule_date')) {
                    $table->dropColumn('schedule_date');
                }
            });
        }
    }
};
