<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the teacher_loading_schedules table in a school-level database.
 *
 * This migration is run on BOTH loading_scheduling_jh and loading_scheduling_gs.
 * Usage:
 *   php artisan migrate --database=mysql_jh
 *   php artisan migrate --database=mysql_gs
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection($this->getConnection())->hasTable('teacher_loading_schedules')) {
            Schema::connection($this->getConnection())->create('teacher_loading_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');       // references users.id in loading_scheduling
                $table->string('school_year', 20);              // e.g. "2025-2026"
                $table->enum('semester', ['1st', '2nd', 'Summer'])->default('1st');
                $table->string('subject_code', 50)->nullable();
                $table->string('subject_name', 150);
                $table->string('grade_level', 50)->nullable();
                $table->string('section', 50)->nullable();
                $table->decimal('units', 5, 2)->default(0);
                $table->decimal('load_hours', 5, 2)->default(0); // hours per week
                $table->string('day_of_week', 30)->nullable();   // e.g. "Mon/Wed/Fri"
                $table->time('time_start')->nullable();
                $table->time('time_end')->nullable();
                $table->string('room', 50)->nullable();
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable(); // references users.id
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index('faculty_id');
                $table->index(['faculty_id', 'school_year', 'semester']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('teacher_loading_schedules');
    }
};
