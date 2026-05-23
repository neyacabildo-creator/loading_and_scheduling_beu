<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent repair: recreate school operational tables on mysql_jh / mysql_gs
 * when they are missing (e.g. after DB recreate or incomplete migrate).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $schoolConnections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->schoolConnections as $connection) {
            $this->ensureSchoolTables($connection);
        }
    }

    private function ensureSchoolTables(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('rooms')) {
            Schema::connection($connection)->create('rooms', function (Blueprint $table) {
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

        if (! Schema::connection($connection)->hasTable('faculty_loads')) {
            Schema::connection($connection)->create('faculty_loads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');
                $table->string('subject')->nullable();
                $table->string('department')->nullable();
                $table->integer('classes_assigned')->default(0);
                $table->decimal('load_hours', 5, 2)->default(0);
                $table->enum('status', ['active', 'part-time', 'overloaded'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::connection($connection)->hasTable('faculty_loads')
            && ! Schema::connection($connection)->hasColumn('faculty_loads', 'grade_level')) {
            Schema::connection($connection)->table('faculty_loads', function (Blueprint $table) {
                $table->string('grade_level')->nullable()->after('subject');
            });
        }

        if (! Schema::connection($connection)->hasTable('class_schedules')) {
            Schema::connection($connection)->create('class_schedules', function (Blueprint $table) use ($connection) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');
                $table->string('subject');
                $table->string('grade_section')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->string('day_of_week')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->enum('status', ['pending', 'active', 'completed', 'deleted', 'rejected'])->default('pending');
                $table->boolean('admin_approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('last_modified_by_admin')->nullable();
                $table->text('change_log')->nullable();
                $table->date('schedule_date')->nullable();
                $table->timestamps();

                if (Schema::connection($connection)->hasTable('rooms')) {
                    $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
                }
            });
        }

        if (Schema::connection($connection)->hasTable('class_schedules')) {
            Schema::connection($connection)->table('class_schedules', function (Blueprint $table) use ($connection) {
                if (! Schema::connection($connection)->hasColumn('class_schedules', 'grade_level')) {
                    $table->string('grade_level', 20)->nullable()->after('subject');
                }
                if (! Schema::connection($connection)->hasColumn('class_schedules', 'section_name')) {
                    $table->string('section_name', 30)->nullable()->after('grade_level');
                }
                if (! Schema::connection($connection)->hasColumn('class_schedules', 'version')) {
                    $table->integer('version')->default(1)->after('approved_by');
                }
                if (! Schema::connection($connection)->hasColumn('class_schedules', 'student_count')) {
                    $table->unsignedInteger('student_count')->default(0)->nullable();
                }
            });
        }

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

        if (! Schema::connection($connection)->hasTable('export_logs')) {
            Schema::connection($connection)->create('export_logs', function (Blueprint $table) {
                $table->id();
                $table->string('format');
                $table->text('data_selected');
                $table->string('filename');
                $table->string('file_path')->nullable();
                $table->bigInteger('file_size')->nullable();
                $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive repair migration — no automatic drops.
    }
};
