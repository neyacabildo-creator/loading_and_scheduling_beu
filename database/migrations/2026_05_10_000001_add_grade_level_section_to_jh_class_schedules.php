<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds grade_level and section_name columns to class_schedules
 * in the Junior High operational database (mysql_jh / loading_scheduling_jh).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql_jh')->hasTable('class_schedules')) {
            Schema::connection('mysql_jh')->table('class_schedules', function (Blueprint $table) {
                if (!Schema::connection('mysql_jh')->hasColumn('class_schedules', 'grade_level')) {
                    $table->string('grade_level', 20)->nullable()->after('grade_section')
                          ->comment('e.g. Grade 7 … Grade 10');
                }
                if (!Schema::connection('mysql_jh')->hasColumn('class_schedules', 'section_name')) {
                    $table->string('section_name', 30)->nullable()->after('grade_level')
                          ->comment('SERAPHIM | CHERUBIM | MICHAEL | RAPHAEL | GABRIEL | THERESE | ALOYSIUS | AGNES | JOHN | GORETTI');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql_jh')->hasTable('class_schedules')) {
            Schema::connection('mysql_jh')->table('class_schedules', function (Blueprint $table) {
                $table->dropColumn(array_filter([
                    Schema::connection('mysql_jh')->hasColumn('class_schedules', 'grade_level')  ? 'grade_level'  : null,
                    Schema::connection('mysql_jh')->hasColumn('class_schedules', 'section_name') ? 'section_name' : null,
                ]));
            });
        }
    }
};
