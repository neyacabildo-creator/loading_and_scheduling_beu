<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add grade_level to faculty_loads in both school databases.
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasColumn('faculty_loads', 'grade_level')) {
                Schema::connection($connection)->table('faculty_loads', function (Blueprint $table) {
                    $table->string('grade_level')->nullable()->after('teacher_name');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (Schema::connection($connection)->hasColumn('faculty_loads', 'grade_level')) {
                Schema::connection($connection)->table('faculty_loads', function (Blueprint $table) {
                    $table->dropColumn('grade_level');
                });
            }
        }
    }
};
