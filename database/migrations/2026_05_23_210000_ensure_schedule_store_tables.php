<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures tables/columns required by admin schedule store exist on school DBs.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $schoolConnections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConnections as $connection) {
            $this->ensureScheduleStoreArtifacts($connection);
        }
    }

    private function ensureScheduleStoreArtifacts(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            return;
        }

        Schema::connection($connection)->table('class_schedules', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('class_schedules', 'grade_level')) {
                $table->string('grade_level', 20)->nullable();
            }
            if (! Schema::connection($connection)->hasColumn('class_schedules', 'section_name')) {
                $table->string('section_name', 30)->nullable();
            }
            if (! Schema::connection($connection)->hasColumn('class_schedules', 'version')) {
                $table->integer('version')->default(1);
            }
            if (! Schema::connection($connection)->hasColumn('class_schedules', 'student_count')) {
                $table->unsignedInteger('student_count')->default(0)->nullable();
            }
        });

        if (! Schema::connection($connection)->hasTable('schedule_approvals')) {
            Schema::connection($connection)->create('schedule_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedBigInteger('submitted_by');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('submission_notes')->nullable();
                $table->unsignedSmallInteger('revision_count')->default(0);
                $table->timestamps();
                $table->index('schedule_id');
                $table->index('status');
                $table->index('submitted_by');
            });
        }

        if (! Schema::connection($connection)->hasTable('shared_teachers')) {
            Schema::connection($connection)->create('shared_teachers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id')->nullable();
                $table->string('teacher_name', 150);
                $table->string('email', 150)->nullable();
                $table->enum('school_level', ['junior_high', 'grade_school']);
                $table->string('department', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['faculty_id', 'school_level']);
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive.
    }
};
