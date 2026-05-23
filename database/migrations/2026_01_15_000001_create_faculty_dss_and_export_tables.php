<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safe to re-run after a partial failed deploy (MySQL DDL does not roll back).
        if (! Schema::hasTable('faculty_loads')) {
        Schema::create('faculty_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
            $table->string('department');
            $table->integer('classes_assigned')->default(0);
            $table->decimal('load_hours', 5, 2)->default(0);
            $table->enum('status', ['active', 'part-time', 'overloaded'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('dss_recommendations')) {
        Schema::create('dss_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->text('issue');
            $table->text('solution');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'implemented'])->default('pending');
            $table->foreignId('related_faculty_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('export_logs')) {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('format');
            $table->text('data_selected');
            $table->string('filename');
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
        }

        // Rooms must exist before class_schedules (FK references rooms.id).
        if (! Schema::hasTable('rooms')) {
        Schema::create('rooms', function (Blueprint $table) {
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

        if (! Schema::hasTable('class_schedules')) {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->string('grade_section');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('student_count')->default(0);
            $table->enum('status', ['pending', 'active', 'completed'])->default('active');
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('dss_recommendations');
        Schema::dropIfExists('faculty_loads');
    }
};
