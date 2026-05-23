<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the operational tables for a school-level database.
 *
 * Runs on BOTH loading_scheduling_jh and loading_scheduling_gs.
 * Shared tables (users, roles, sessions, etc.) remain on the default DB.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $schoolConnections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConnections as $connection) {
            $this->createSchoolTables($connection);
        }
    }

    private function createSchoolTables(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('rooms')) {
            Schema::connection($connection)->create('rooms', function (Blueprint $table) {
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

        if (! Schema::connection($connection)->hasTable('faculty_loads')) {
            Schema::connection($connection)->create('faculty_loads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');
                $table->string('subject')->nullable();
                $table->string('department')->nullable();
                $table->integer('classes_assigned')->default(0);
                $table->decimal('load_hours', 5, 2)->default(0);
                $table->enum('status', ['active', 'part-time', 'overloaded'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($connection)->hasTable('dss_recommendations')) {
            Schema::connection($connection)->create('dss_recommendations', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
                $table->text('issue');
                $table->text('solution');
                $table->enum('status', ['pending', 'accepted', 'rejected', 'implemented'])->default('pending');
                $table->unsignedBigInteger('related_faculty_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($connection)->hasTable('export_logs')) {
            Schema::connection($connection)->create('export_logs', function (Blueprint $table) {
                $table->id();
                $table->string('format');
                $table->text('data_selected');
                $table->string('filename');
                $table->string('file_path')->nullable();
                $table->bigInteger('file_size')->nullable();
                $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            Schema::connection($connection)->create('class_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');
                $table->string('subject');
                $table->string('grade_section');
                $table->unsignedBigInteger('room_id')->nullable();
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
    }

    public function down(): void
    {
        foreach ($this->schoolConnections as $connection) {
            Schema::connection($connection)->dropIfExists('class_schedules');
            Schema::connection($connection)->dropIfExists('faculty_loads');
            Schema::connection($connection)->dropIfExists('dss_recommendations');
            Schema::connection($connection)->dropIfExists('export_logs');
            Schema::connection($connection)->dropIfExists('rooms');
        }
    }
};
