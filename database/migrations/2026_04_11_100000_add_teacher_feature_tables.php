<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds teacher-specific operational tables to the dedicated teacher databases.
 *
 * Tables added to BOTH mysql_jh_teacher and mysql_gs_teacher:
 *  - subject_assignments   (Assign Subjects to Faculty use case)
 *  - schedule_adjustment_requests (Request Schedule Adjustments use case)
 *
 * NOTE: Teacher tables are kept SEPARATE from admin tables.
 *   Admin tables (rooms, faculty_loads, class_schedules, etc.) → mysql_jh / mysql_gs
 *   Teacher tables (subject_assignments, schedule_adj_requests) → mysql_jh_teacher / mysql_gs_teacher
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            config('database.connections.mysql_jh_teacher.database'),
            config('database.connections.mysql_gs_teacher.database'),
        ] as $database) {
            $escaped = str_replace('`', '``', $database);
            \Illuminate\Support\Facades\DB::statement(
                "CREATE DATABASE IF NOT EXISTS `{$escaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        }

        foreach (['mysql_jh_teacher', 'mysql_gs_teacher'] as $conn) {
            // Subject Assignments
            if (!Schema::connection($conn)->hasTable('subject_assignments')) {
                Schema::connection($conn)->create('subject_assignments', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');         // references users.id (main DB)
                    $table->string('subject');
                    $table->string('grade_level')->nullable();
                    $table->string('section')->nullable();
                    $table->integer('units')->default(0);
                    $table->enum('status', ['assigned', 'pending', 'unassigned'])->default('assigned');
                    $table->text('notes')->nullable();
                    $table->unsignedBigInteger('assigned_by')->nullable(); // references users.id
                    $table->timestamps();
                });
            }

            // Schedule Adjustment Requests
            if (!Schema::connection($conn)->hasTable('schedule_adjustment_requests')) {
                Schema::connection($conn)->create('schedule_adjustment_requests', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('requested_by');          // references users.id
                    $table->unsignedBigInteger('schedule_id')->nullable(); // references class_schedules.id
                    $table->enum('request_type', [
                        'time_change',
                        'room_change',
                        'teacher_reassignment',
                        'section_change',
                        'other',
                    ])->default('other');
                    $table->text('reason');
                    $table->text('proposed_changes')->nullable();
                    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                    $table->text('admin_notes')->nullable();
                    $table->unsignedBigInteger('reviewed_by')->nullable(); // references users.id
                    $table->timestamp('reviewed_at')->nullable();
                    $table->timestamps();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh_teacher', 'mysql_gs_teacher'] as $conn) {
            Schema::connection($conn)->dropIfExists('schedule_adjustment_requests');
            Schema::connection($conn)->dropIfExists('subject_assignments');
        }
    }
};
