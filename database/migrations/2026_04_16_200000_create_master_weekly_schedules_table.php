<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the master_weekly_schedules table in BOTH school-level databases
 * (loading_scheduling_jh and loading_scheduling_gs).
 *
 * Each row represents one time-slot × day cell in a teacher's weekly grid schedule
 * (the printed "Master Loading Schedule" form shown to faculty).
 */
return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            if (!Schema::connection($conn)->hasTable('master_weekly_schedules')) {
                Schema::connection($conn)->create('master_weekly_schedules', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');          // references users.id (main DB)
                    $table->string('school_year', 20)->default('2025-2026');
                    $table->string('subject_handled', 150)->nullable(); // e.g. "FILIPINO 8"
                    $table->tinyInteger('slot_order')->unsigned();       // 1-9 for ordering rows
                    $table->string('time_label', 30);                  // e.g. "7:45-8:45", "LUNCH"
                    $table->time('time_start')->nullable();
                    $table->time('time_end')->nullable();
                    $table->enum('day_of_week', ['Monday','Tuesday','Wednesday','Thursday','Friday']);
                    $table->enum('entry_type', ['class','lunch','homeroom','free'])->default('free');
                    $table->string('grade_section', 150)->nullable();   // e.g. "Fil 8 - St. Ma. Goretti"
                    $table->string('grade_level', 20)->nullable();
                    $table->string('section_name', 100)->nullable();
                    $table->text('section_students')->nullable();        // legacy substitute teacher or student list
                    $table->string('substitute_teacher', 150)->nullable();
                    $table->string('special_label', 150)->nullable();   // "LUNCH" / "JHS HOMEROOM/STUDENT ACTIVITIES"
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();

                    $table->index('faculty_id');
                    $table->index(['faculty_id', 'school_year']);
                    $table->unique(['faculty_id', 'school_year', 'slot_order', 'day_of_week'], 'mws_unique_cell');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            Schema::connection($conn)->dropIfExists('master_weekly_schedules');
        }
    }
};
