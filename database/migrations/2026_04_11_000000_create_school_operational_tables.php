<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the operational tables for a school-level database.
 *
 * This migration is run on BOTH loading_scheduling_jh and loading_scheduling_gs.
 * Each database is fully isolated — no school_level column is needed since
 * the database itself determines which school's data is stored.
 *
 * Shared tables (users, roles, sessions, etc.) remain in loading_scheduling.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rooms
        if (!Schema::connection($this->getConnection())->hasTable('rooms')) {
            Schema::connection($this->getConnection())->create('rooms', function (Blueprint $table) {
                $table->id();
                $table->string('room_number', 50)->unique();
                $table->string('building')->nullable();
                $table->integer('capacity')->default(30);
                $table->boolean('has_laboratory')->default(false);
                $table->boolean('has_projector')->default(true);
                $table->boolean('has_ac')->default(true);
                $table->enum('status', ['available', 'in-use', 'maintenance'])->default('available');
                $table->timestamps();
            });
        }

        // Faculty Loads
        if (!Schema::connection($this->getConnection())->hasTable('faculty_loads')) {
            Schema::connection($this->getConnection())->create('faculty_loads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');  // references users.id in loading_scheduling
                $table->string('subject')->nullable();
                $table->string('department')->nullable();
                $table->integer('classes_assigned')->default(0);
                $table->decimal('load_hours', 5, 2)->default(0);
                $table->enum('status', ['active', 'part-time', 'overloaded'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // DSS Recommendations
        if (!Schema::connection($this->getConnection())->hasTable('dss_recommendations')) {
            Schema::connection($this->getConnection())->create('dss_recommendations', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
                $table->text('issue');
                $table->text('solution');
                $table->enum('status', ['pending', 'accepted', 'rejected', 'implemented'])->default('pending');
                $table->unsignedBigInteger('related_faculty_id')->nullable();  // references users.id in loading_scheduling
                $table->timestamps();
            });
        }

        // Export Logs
        if (!Schema::connection($this->getConnection())->hasTable('export_logs')) {
            Schema::connection($this->getConnection())->create('export_logs', function (Blueprint $table) {
                $table->id();
                $table->string('format');
                $table->text('data_selected');
                $table->string('filename');
                $table->string('file_path')->nullable();
                $table->bigInteger('file_size')->nullable();
                $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
                $table->unsignedBigInteger('created_by')->nullable();  // references users.id in loading_scheduling
                $table->timestamps();
            });
        }

        // Class Schedules
        if (!Schema::connection($this->getConnection())->hasTable('class_schedules')) {
            Schema::connection($this->getConnection())->create('class_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');  // references users.id in loading_scheduling
                $table->string('subject');
                $table->string('grade_section');
                $table->unsignedBigInteger('room_id')->nullable();  // references rooms.id in this DB
                $table->string('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('student_count')->default(0);
                $table->enum('status', ['pending', 'active', 'completed'])->default('active');
                $table->boolean('admin_approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('last_modified_by_admin')->nullable();
                $table->text('change_log')->nullable();
                $table->date('schedule_date')->nullable();
                $table->timestamps();

                $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
            });
        }

        // Grade Submissions — removed; dropped in 2026_04_11_200000 migration
        // Schema::connection($this->getConnection())->dropIfExists('grade_submissions');
    }

    public function down(): void
    {
        $conn = $this->getConnection();
        Schema::connection($conn)->dropIfExists('class_schedules');
        Schema::connection($conn)->dropIfExists('faculty_loads');
        Schema::connection($conn)->dropIfExists('dss_recommendations');
        Schema::connection($conn)->dropIfExists('export_logs');
        Schema::connection($conn)->dropIfExists('rooms');
    }
};
