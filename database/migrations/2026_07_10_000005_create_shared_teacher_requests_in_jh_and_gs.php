<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates a shared_teacher_requests table in both the JH and GS admin databases.
 *
 * This allows both JH and GS admins to track schedule/load requests
 * specifically for shared teachers within their own division.
 *
 * Columns:
 *   - faculty_id        : User ID of the shared teacher (ref to main DB users)
 *   - teacher_name      : Cached name for display
 *   - request_type      : 'schedule_request' | 'load_adjustment' | 'conflict_report' | 'other'
 *   - school_level      : 'junior_high' | 'grade_school'
 *   - subject           : Subject involved (optional)
 *   - grade_level       : Grade level (optional)
 *   - section_name      : Section (optional)
 *   - day_of_week       : Day involved (optional)
 *   - preferred_start_time / preferred_end_time : Requested time slot (optional)
 *   - description       : Free-text details
 *   - status            : 'pending' | 'approved' | 'rejected'
 *   - admin_notes       : Admin response notes
 *   - reviewed_by       : User ID of the admin who reviewed
 *   - reviewed_at       : Timestamp of review
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                Schema::connection($connection)->create('shared_teacher_requests', function (Blueprint $table) {
                    $table->id();

                    // Reference to main DB users table
                    $table->unsignedBigInteger('faculty_id');
                    $table->string('teacher_name', 150);

                    // Type of request
                    $table->enum('request_type', [
                        'schedule_request',
                        'load_adjustment',
                        'conflict_report',
                        'other',
                    ])->default('schedule_request');

                    // Which division this record belongs to
                    $table->enum('school_level', ['junior_high', 'grade_school']);

                    // Optional schedule details
                    $table->string('subject', 100)->nullable();
                    $table->string('grade_level', 30)->nullable();
                    $table->string('section_name', 50)->nullable();
                    $table->string('day_of_week', 20)->nullable();
                    $table->time('preferred_start_time')->nullable();
                    $table->time('preferred_end_time')->nullable();

                    // Free-text description from the requesting teacher/admin
                    $table->text('description')->nullable();

                    // Review workflow
                    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                    $table->text('admin_notes')->nullable();
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();

                    $table->timestamps();

                    $table->index(['faculty_id', 'school_level']);
                    $table->index('status');
                    $table->index('request_type');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            Schema::connection($connection)->dropIfExists('shared_teacher_requests');
        }
    }
};
