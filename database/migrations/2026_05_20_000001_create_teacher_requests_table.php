<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the teacher_requests table in the main (mysql) database.
 * Mirrors the structure of shared_teacher_requests (in mysql_jh/mysql_gs)
 * but is for regular GS/JH teachers requesting schedule changes.
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        if (!Schema::connection('mysql')->hasTable('teacher_requests')) {
            Schema::connection('mysql')->create('teacher_requests', function (Blueprint $table) {
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

                // Which school division this request belongs to
                $table->enum('school_level', ['junior_high', 'grade_school']);

                // Schedule details
                $table->string('subject', 100)->nullable();
                $table->string('grade_level', 50)->nullable();
                $table->string('section_name', 100)->nullable();
                $table->string('day_of_week', 20)->nullable();
                $table->time('preferred_start_time')->nullable();
                $table->time('preferred_end_time')->nullable();
                $table->text('description')->nullable();

                // Review flow
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('teacher_requests');
    }
};
