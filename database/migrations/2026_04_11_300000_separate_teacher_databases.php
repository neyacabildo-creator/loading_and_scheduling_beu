<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates teacher-specific tables from admin tables.
 *
 * BEFORE (wrong):
 *   mysql_jh / mysql_gs contain BOTH admin and teacher tables mixed together.
 *
 * AFTER (correct):
 *   mysql_jh            → admin tables only (rooms, faculty_loads, class_schedules, etc.)
 *   mysql_jh_teacher    → teacher tables only (subject_assignments, schedule_adjustment_requests)
 *   mysql_gs            → admin tables only
 *   mysql_gs_teacher    → teacher tables only
 *
 * This migration:
 *  1. Creates subject_assignments + schedule_adjustment_requests in the teacher DBs
 *     (if they don't exist there yet — handles current state where they're in admin DBs)
 *  2. Drops those tables from the admin DBs (they no longer belong there)
 */
return new class extends Migration
{
    /** @var array<string, string> Maps admin connection → teacher connection */
    private array $pairs = [
        'mysql_jh' => 'mysql_jh_teacher',
        'mysql_gs' => 'mysql_gs_teacher',
    ];

    public function up(): void
    {
        // ------------------------------------------------------------------
        // 0. Create the teacher databases if they don't exist yet
        // ------------------------------------------------------------------
        $jhTeacherDb = config('database.connections.mysql_jh_teacher.database');
        $gsTeacherDb = config('database.connections.mysql_gs_teacher.database');

        \Illuminate\Support\Facades\DB::statement("CREATE DATABASE IF NOT EXISTS `{$jhTeacherDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        \Illuminate\Support\Facades\DB::statement("CREATE DATABASE IF NOT EXISTS `{$gsTeacherDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        foreach ($this->pairs as $adminConn => $teacherConn) {
            // ------------------------------------------------------------------
            // 1. Create teacher tables in the dedicated teacher DB
            // ------------------------------------------------------------------
            if (!Schema::connection($teacherConn)->hasTable('subject_assignments')) {
                Schema::connection($teacherConn)->create('subject_assignments', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');              // references users.id (main DB)
                    $table->string('subject');
                    $table->string('grade_level')->nullable();
                    $table->string('section')->nullable();
                    $table->integer('units')->default(0);
                    $table->enum('status', ['assigned', 'pending', 'unassigned'])->default('assigned');
                    $table->text('notes')->nullable();
                    $table->unsignedBigInteger('assigned_by')->nullable(); // references users.id (main DB)
                    $table->timestamps();
                });
            }

            if (!Schema::connection($teacherConn)->hasTable('schedule_adjustment_requests')) {
                Schema::connection($teacherConn)->create('schedule_adjustment_requests', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('requested_by');            // references users.id (main DB)
                    $table->unsignedBigInteger('schedule_id')->nullable(); // references class_schedules.id (admin DB)
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
                    $table->unsignedBigInteger('reviewed_by')->nullable(); // references users.id (main DB)
                    $table->timestamp('reviewed_at')->nullable();
                    $table->timestamps();
                });
            }

            // ------------------------------------------------------------------
            // 2. Remove teacher tables from the admin DB — they do not belong there
            // ------------------------------------------------------------------
            Schema::connection($adminConn)->dropIfExists('schedule_adjustment_requests');
            Schema::connection($adminConn)->dropIfExists('subject_assignments');
        }
    }

    public function down(): void
    {
        foreach ($this->pairs as $adminConn => $teacherConn) {
            // Restore teacher tables to admin DBs (undo the separation)
            if (!Schema::connection($adminConn)->hasTable('subject_assignments')) {
                Schema::connection($adminConn)->create('subject_assignments', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');
                    $table->string('subject');
                    $table->string('grade_level')->nullable();
                    $table->string('section')->nullable();
                    $table->integer('units')->default(0);
                    $table->enum('status', ['assigned', 'pending', 'unassigned'])->default('assigned');
                    $table->text('notes')->nullable();
                    $table->unsignedBigInteger('assigned_by')->nullable();
                    $table->timestamps();
                });
            }

            if (!Schema::connection($adminConn)->hasTable('schedule_adjustment_requests')) {
                Schema::connection($adminConn)->create('schedule_adjustment_requests', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('requested_by');
                    $table->unsignedBigInteger('schedule_id')->nullable();
                    $table->enum('request_type', [
                        'time_change', 'room_change', 'teacher_reassignment', 'section_change', 'other',
                    ])->default('other');
                    $table->text('reason');
                    $table->text('proposed_changes')->nullable();
                    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                    $table->text('admin_notes')->nullable();
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->timestamps();
                });
            }

            // Remove from teacher DBs
            Schema::connection($teacherConn)->dropIfExists('schedule_adjustment_requests');
            Schema::connection($teacherConn)->dropIfExists('subject_assignments');
        }
    }
};
