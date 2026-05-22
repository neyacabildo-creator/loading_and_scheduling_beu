<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds grade_level and section_name columns to class_schedules
 * in both the default (mysql / loading_scheduling) and the Grade School
 * operational database (mysql_gs / loading_scheduling_gs).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Default connection ───────────────────────────────────────────
        if (Schema::hasTable('class_schedules')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('class_schedules', 'grade_level')) {
                    $table->string('grade_level', 20)->nullable()->after('grade_section')
                          ->comment('e.g. Grade 1 … Grade 6');
                }
                if (!Schema::hasColumn('class_schedules', 'section_name')) {
                    $table->string('section_name', 30)->nullable()->after('grade_level')
                          ->comment('STEPHEN | PETER | ST.PAUL');
                }
            });
        }

        // ── GS operational DB (loading_scheduling_gs) ────────────────────
        if (Schema::connection('mysql_gs')->hasTable('class_schedules')) {
            Schema::connection('mysql_gs')->table('class_schedules', function (Blueprint $table) {
                if (!Schema::connection('mysql_gs')->hasColumn('class_schedules', 'grade_level')) {
                    $table->string('grade_level', 20)->nullable()->after('grade_section')
                          ->comment('e.g. Grade 1 … Grade 6');
                }
                if (!Schema::connection('mysql_gs')->hasColumn('class_schedules', 'section_name')) {
                    $table->string('section_name', 30)->nullable()->after('grade_level')
                          ->comment('STEPHEN | PETER | ST.PAUL');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn(array_filter([
                    Schema::hasColumn('class_schedules', 'grade_level')   ? 'grade_level'   : null,
                    Schema::hasColumn('class_schedules', 'section_name')  ? 'section_name'  : null,
                ]));
            });
        }

        if (Schema::connection('mysql_gs')->hasTable('class_schedules')) {
            Schema::connection('mysql_gs')->table('class_schedules', function (Blueprint $table) {
                $table->dropColumn(array_filter([
                    Schema::connection('mysql_gs')->hasColumn('class_schedules', 'grade_level')  ? 'grade_level'  : null,
                    Schema::connection('mysql_gs')->hasColumn('class_schedules', 'section_name') ? 'section_name' : null,
                ]));
            });
        }
    }
};
