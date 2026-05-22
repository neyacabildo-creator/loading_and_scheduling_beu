<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            Schema::connection($conn)->table('master_weekly_schedules', function (Blueprint $table) use ($conn) {
                if (!Schema::connection($conn)->hasColumn('master_weekly_schedules', 'grade_level')) {
                    $table->string('grade_level', 20)->nullable()->after('grade_section');
                }
                if (!Schema::connection($conn)->hasColumn('master_weekly_schedules', 'section_name')) {
                    $table->string('section_name', 100)->nullable()->after('grade_level');
                }
                if (!Schema::connection($conn)->hasColumn('master_weekly_schedules', 'substitute_teacher')) {
                    $table->string('substitute_teacher', 150)->nullable()->after('section_students');
                }
            });

            if (Schema::connection($conn)->hasTable('master_weekly_schedules')
                && Schema::connection($conn)->hasColumn('master_weekly_schedules', 'section_students')
                && Schema::connection($conn)->hasColumn('master_weekly_schedules', 'substitute_teacher')) {
                DB::connection($conn)->table('master_weekly_schedules')
                    ->whereNotNull('section_students')
                    ->whereNull('substitute_teacher')
                    ->update(['substitute_teacher' => DB::raw('section_students')]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            Schema::connection($conn)->table('master_weekly_schedules', function (Blueprint $table) use ($conn) {
                if (Schema::connection($conn)->hasColumn('master_weekly_schedules', 'substitute_teacher')) {
                    $table->dropColumn('substitute_teacher');
                }
                if (Schema::connection($conn)->hasColumn('master_weekly_schedules', 'section_name')) {
                    $table->dropColumn('section_name');
                }
                if (Schema::connection($conn)->hasColumn('master_weekly_schedules', 'grade_level')) {
                    $table->dropColumn('grade_level');
                }
            });
        }
    }
};
