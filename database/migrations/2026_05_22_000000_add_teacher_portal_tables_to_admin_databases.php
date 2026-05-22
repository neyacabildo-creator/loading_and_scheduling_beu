<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher portal tables live on admin DBs (mysql_jh / mysql_gs) alongside class_schedules,
 * faculty_loads, and teacher_requests so teachers see the same data admins manage.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $pairs = [
        'mysql_jh' => 'mysql_jh_teacher',
        'mysql_gs' => 'mysql_gs_teacher',
    ];

    public function up(): void
    {
        foreach ($this->pairs as $adminConn => $legacyTeacherConn) {
            $this->ensureSubjectAssignments($adminConn);
            $this->ensureTeacherFeedbacks($adminConn);
            $this->ensureTeacherNotifications($adminConn);
            $this->ensureTeacherLeaveRequests($adminConn);
            $this->ensureTeacherLoadingSchedules($adminConn);

            $this->copyTableIfEmpty($legacyTeacherConn, $adminConn, 'subject_assignments');
            $this->copyTableIfEmpty($legacyTeacherConn, $adminConn, 'teacher_feedbacks', 'teacher_id', 'teacher_id');
            $this->copyTableIfEmpty($legacyTeacherConn, $adminConn, 'teacher_notifications', 'teacher_id', 'teacher_id');
            $this->copyTableIfEmpty($legacyTeacherConn, $adminConn, 'teacher_leave_requests', 'teacher_id', 'teacher_id');
            $this->copyTableIfEmpty($legacyTeacherConn, $adminConn, 'teacher_loading_schedules', 'faculty_id', 'faculty_id');
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->pairs) as $adminConn) {
            Schema::connection($adminConn)->dropIfExists('teacher_leave_requests');
            Schema::connection($adminConn)->dropIfExists('teacher_notifications');
            Schema::connection($adminConn)->dropIfExists('teacher_feedbacks');
            Schema::connection($adminConn)->dropIfExists('subject_assignments');
        }
    }

    private function ensureSubjectAssignments(string $conn): void
    {
        if (Schema::connection($conn)->hasTable('subject_assignments')) {
            return;
        }

        Schema::connection($conn)->create('subject_assignments', function (Blueprint $table) {
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
            $table->index('faculty_id');
        });
    }

    private function ensureTeacherFeedbacks(string $conn): void
    {
        if (Schema::connection($conn)->hasTable('teacher_feedbacks')) {
            return;
        }

        Schema::connection($conn)->create('teacher_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('category', 60)->default('other');
            $table->unsignedTinyInteger('rating')->default(3);
            $table->text('message');
            $table->string('status', 20)->default('submitted');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamps();
            $table->index('teacher_id');
            $table->index('status');
        });
    }

    private function ensureTeacherNotifications(string $conn): void
    {
        if (Schema::connection($conn)->hasTable('teacher_notifications')) {
            return;
        }

        Schema::connection($conn)->create('teacher_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('type', 60)->default('general');
            $table->string('title', 200);
            $table->text('message');
            $table->string('related_type', 60)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
            $table->index('teacher_id');
            $table->index(['teacher_id', 'is_read']);
        });
    }

    private function ensureTeacherLeaveRequests(string $conn): void
    {
        if (Schema::connection($conn)->hasTable('teacher_leave_requests')) {
            return;
        }

        Schema::connection($conn)->create('teacher_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->enum('leave_type', ['sick_leave', 'vacation_leave', 'emergency_leave', 'official_business', 'other'])->default('other');
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('total_days')->default(1);
            $table->text('reason');
            $table->string('supporting_document', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index('teacher_id');
            $table->index('status');
        });
    }

    private function ensureTeacherLoadingSchedules(string $conn): void
    {
        if (Schema::connection($conn)->hasTable('teacher_loading_schedules')) {
            return;
        }

        Schema::connection($conn)->create('teacher_loading_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id');
            $table->string('school_year', 20);
            $table->enum('semester', ['1st', '2nd', 'Summer'])->default('1st');
            $table->string('subject_code', 50)->nullable();
            $table->string('subject_name', 150);
            $table->string('grade_level', 50)->nullable();
            $table->string('section', 50)->nullable();
            $table->decimal('units', 5, 2)->default(0);
            $table->decimal('load_hours', 5, 2)->default(0);
            $table->string('day_of_week', 30)->nullable();
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
        });
    }

    private function copyTableIfEmpty(
        string $fromConn,
        string $toConn,
        string $table,
        ?string $fromKey = null,
        ?string $toKey = null
    ): void {
        if (! Schema::connection($fromConn)->hasTable($table) || ! Schema::connection($toConn)->hasTable($table)) {
            return;
        }

        if (DB::connection($toConn)->table($table)->exists()) {
            return;
        }

        $rows = DB::connection($fromConn)->table($table)->get();
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']);
            DB::connection($toConn)->table($table)->insert($data);
        }
    }
};
