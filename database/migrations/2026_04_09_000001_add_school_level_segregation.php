<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add school_level column to segregate Grade School and Junior High School data
     */
    public function up(): void
    {
        // Add school_level to class_schedules table
        if (Schema::hasTable('class_schedules') && !Schema::hasColumn('class_schedules', 'school_level')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])->default('grade_school')->after('status');
            });
        }

        // Add school_level to faculty_loads table
        if (Schema::hasTable('faculty_loads') && !Schema::hasColumn('faculty_loads', 'school_level')) {
            Schema::table('faculty_loads', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])->default('grade_school')->after('notes');
            });
        }

        // Add school_level to rooms table
        if (Schema::hasTable('rooms') && !Schema::hasColumn('rooms', 'school_level')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])->default('grade_school')->after('status');
            });
        }

        // Add school_level to dss_recommendations table
        if (Schema::hasTable('dss_recommendations') && !Schema::hasColumn('dss_recommendations', 'school_level')) {
            Schema::table('dss_recommendations', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])->default('grade_school')->after('status');
            });
        }

        // Add school_level to grade_submissions table
        if (Schema::hasTable('grade_submissions') && !Schema::hasColumn('grade_submissions', 'school_level')) {
            Schema::table('grade_submissions', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])->default('grade_school')->after('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('class_schedules', 'school_level')) {
                $table->dropColumn('school_level');
            }
        });

        Schema::table('faculty_loads', function (Blueprint $table) {
            if (Schema::hasColumn('faculty_loads', 'school_level')) {
                $table->dropColumn('school_level');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'school_level')) {
                $table->dropColumn('school_level');
            }
        });

        Schema::table('dss_recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('dss_recommendations', 'school_level')) {
                $table->dropColumn('school_level');
            }
        });

        if (Schema::hasTable('grade_submissions')) {
            Schema::table('grade_submissions', function (Blueprint $table) {
                if (Schema::hasColumn('grade_submissions', 'school_level')) {
                    $table->dropColumn('school_level');
                }
            });
        }
    }
};
