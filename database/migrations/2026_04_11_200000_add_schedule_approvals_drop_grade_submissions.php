<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds schedule_approvals table to both school databases and
 * removes the unused grade_submissions table.
 *
 * Applied to: mysql_jh (loading_scheduling_jh)
 *             mysql_gs (loading_scheduling_gs)
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            // ----------------------------------------------------------------
            // 1. Schedule Approvals — tracks pending/approved/rejected workflow
            //    for class schedules submitted by teachers.
            // ----------------------------------------------------------------
            if (!Schema::connection($conn)->hasTable('schedule_approvals')) {
                Schema::connection($conn)->create('schedule_approvals', function (Blueprint $table) {
                    $table->id();

                    // The schedule being reviewed
                    $table->unsignedBigInteger('schedule_id');

                    // Teacher (faculty) who submitted the schedule for review
                    $table->unsignedBigInteger('submitted_by');

                    // Overall approval status
                    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

                    // Admin who reviewed
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();

                    // Human-readable notes / feedback from admin
                    $table->text('admin_notes')->nullable();

                    // Reason supplied by the teacher when submitting
                    $table->text('submission_notes')->nullable();

                    // Tracks how many times this schedule was resubmitted
                    $table->unsignedSmallInteger('revision_count')->default(0);

                    $table->timestamps();

                    // One pending-or-approved approval record per schedule
                    $table->index('schedule_id');
                    $table->index('status');
                    $table->index('submitted_by');
                });
            }

            // ----------------------------------------------------------------
            // 2. Drop grade_submissions — not used in current system flow.
            // ----------------------------------------------------------------
            Schema::connection($conn)->dropIfExists('grade_submissions');
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            Schema::connection($conn)->dropIfExists('schedule_approvals');

            // Recreate grade_submissions on rollback
            if (!Schema::connection($conn)->hasTable('grade_submissions')) {
                Schema::connection($conn)->create('grade_submissions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');
                    $table->string('subject');
                    $table->string('grade_section');
                    $table->string('quarter');
                    $table->json('grades')->nullable();
                    $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
                    $table->timestamp('submitted_at')->nullable();
                    $table->timestamps();
                });
            }
        }
    }
};
