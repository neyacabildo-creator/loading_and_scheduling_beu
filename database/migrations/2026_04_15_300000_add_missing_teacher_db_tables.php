<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds the remaining teacher-side tables to BOTH teacher databases.
 *
 * Tables added to loading_scheduling_jh_teacher AND loading_scheduling_gs_teacher:
 *  1. teacher_loading_schedules   – teacher's personal loading schedule per semester
 *  2. teacher_feedbacks           – feedback submitted by teachers
 *  3. teacher_notifications       – system notifications/alerts for teachers
 *  4. teacher_leave_requests      – leave / absence requests submitted by teachers
 *
 * All faculty_id / teacher_id columns reference users.id in the main (shared) database.
 */
return new class extends Migration
{
    private array $teacherConnections = [
        'mysql_jh_teacher',
        'mysql_gs_teacher',
    ];

    public function up(): void
    {
        foreach ($this->teacherConnections as $conn) {
            // ---------------------------------------------------------------
            // 1. teacher_loading_schedules
            //    Stores each teacher's assigned loading schedule per semester.
            //    faculty_id → users.id (main DB)
            //    approved_by → users.id (main DB)
            // ---------------------------------------------------------------
            if (!Schema::connection($conn)->hasTable('teacher_loading_schedules')) {
                Schema::connection($conn)->create('teacher_loading_schedules', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');
                    $table->string('school_year', 20);                           // e.g. "2025-2026"
                    $table->enum('semester', ['1st', '2nd', 'Summer'])->default('1st');
                    $table->string('subject_code', 50)->nullable();
                    $table->string('subject_name', 150);
                    $table->string('grade_level', 50)->nullable();
                    $table->string('section', 50)->nullable();
                    $table->decimal('units', 5, 2)->default(0);
                    $table->decimal('load_hours', 5, 2)->default(0);
                    $table->string('day_of_week', 30)->nullable();               // e.g. "Mon/Wed/Fri"
                    $table->time('time_start')->nullable();
                    $table->time('time_end')->nullable();
                    $table->string('room', 50)->nullable();
                    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                    $table->text('remarks')->nullable();
                    $table->unsignedBigInteger('approved_by')->nullable();
                    $table->timestamp('approved_at')->nullable();
                    $table->timestamps();

                    $table->index('faculty_id');
                    $table->index(['faculty_id', 'school_year', 'semester']);
                    $table->index('status');
                });
            }

            // ---------------------------------------------------------------
            // 2. teacher_feedbacks
            //    Feedback submitted by teachers about workload, schedules, etc.
            //    teacher_id → users.id (main DB)
            // ---------------------------------------------------------------
            if (!Schema::connection($conn)->hasTable('teacher_feedbacks')) {
                Schema::connection($conn)->create('teacher_feedbacks', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('teacher_id');
                    $table->string('category', 60)->default('other');
                    // e.g. schedule_clarity | workload_fairness | system_usability | other
                    $table->tinyInteger('rating')->unsigned()->default(3);        // 1-5
                    $table->text('message');
                    $table->string('status', 20)->default('submitted');
                    // submitted | reviewed | resolved
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->text('admin_response')->nullable();
                    $table->timestamps();

                    $table->index('teacher_id');
                    $table->index('status');
                });
            }

            // ---------------------------------------------------------------
            // 3. teacher_notifications
            //    System-generated notifications sent to individual teachers.
            //    teacher_id → users.id (main DB)
            //    sent_by → users.id (main DB, nullable — system-generated = null)
            // ---------------------------------------------------------------
            if (!Schema::connection($conn)->hasTable('teacher_notifications')) {
                Schema::connection($conn)->create('teacher_notifications', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('teacher_id');
                    $table->string('type', 60)->default('general');
                    // Types: schedule_update | load_assigned | adjustment_reviewed |
                    //        leave_reviewed | feedback_response | general
                    $table->string('title', 200);
                    $table->text('message');
                    $table->string('related_type', 60)->nullable();               // e.g. "schedule_adjustment_requests"
                    $table->unsignedBigInteger('related_id')->nullable();         // FK to the related record
                    $table->boolean('is_read')->default(false);
                    $table->timestamp('read_at')->nullable();
                    $table->unsignedBigInteger('sent_by')->nullable();            // null = system
                    $table->timestamps();

                    $table->index('teacher_id');
                    $table->index(['teacher_id', 'is_read']);
                    $table->index('type');
                });
            }

            // ---------------------------------------------------------------
            // 4. teacher_leave_requests
            //    Leave / absence requests submitted by teachers.
            //    teacher_id → users.id (main DB)
            //    reviewed_by → users.id (main DB)
            // ---------------------------------------------------------------
            if (!Schema::connection($conn)->hasTable('teacher_leave_requests')) {
                Schema::connection($conn)->create('teacher_leave_requests', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('teacher_id');
                    $table->enum('leave_type', [
                        'sick_leave',
                        'vacation_leave',
                        'emergency_leave',
                        'official_business',
                        'other',
                    ])->default('other');
                    $table->date('date_from');
                    $table->date('date_to');
                    $table->integer('total_days')->default(1);
                    $table->text('reason');
                    $table->string('supporting_document', 255)->nullable();       // file path if uploaded
                    $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->text('admin_notes')->nullable();
                    $table->timestamps();

                    $table->index('teacher_id');
                    $table->index('status');
                    $table->index(['teacher_id', 'status']);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->teacherConnections as $conn) {
            Schema::connection($conn)->dropIfExists('teacher_leave_requests');
            Schema::connection($conn)->dropIfExists('teacher_notifications');
            Schema::connection($conn)->dropIfExists('teacher_feedbacks');
            Schema::connection($conn)->dropIfExists('teacher_loading_schedules');
        }
    }
};
