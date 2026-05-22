<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates shared_teacher_schedule_requests in BOTH mysql_jh and mysql_gs databases.
 *
 * This table must NOT exist in the main loading_scheduling DB.
 * Each school-level admin manages requests in their own DB.
 *
 * teacher_name and teacher_email are stored (denormalized) so the admin views
 * can display teacher info without cross-DB joins.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (!Schema::connection($conn)->hasTable('shared_teacher_schedule_requests')) {
                Schema::connection($conn)->create('shared_teacher_schedule_requests', function (Blueprint $table) {
                    $table->id();

                    // Reference to main DB users table (no FK across DBs)
                    $table->unsignedBigInteger('user_id');

                    // Cached teacher info for display without cross-DB join
                    $table->string('teacher_name', 200)->nullable();
                    $table->string('teacher_email', 255)->nullable();

                    // Which school level this request belongs to ('jh' or 'gs')
                    $table->string('school_level', 10)->default('jh');

                    // Schedule details
                    $table->string('subject', 100)->nullable();
                    $table->string('grade_level', 50)->nullable();
                    $table->string('section_name', 100)->nullable();
                    $table->string('day_of_week', 20)->nullable();
                    $table->time('preferred_start_time')->nullable();
                    $table->time('preferred_end_time')->nullable();
                    $table->text('notes')->nullable();

                    // Review workflow
                    $table->string('status', 20)->default('pending'); // pending | approved | rejected
                    $table->text('admin_notes')->nullable();
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();

                    $table->timestamps();

                    $table->index(['user_id', 'status']);
                    $table->index('status');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            Schema::connection($conn)->dropIfExists('shared_teacher_schedule_requests');
        }
    }
};
