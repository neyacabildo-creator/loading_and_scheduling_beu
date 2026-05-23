<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates teacher_loading_schedules in both school databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('teacher_loading_schedules')) {
                Schema::connection($connection)->create('teacher_loading_schedules', function (Blueprint $table) {
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
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            Schema::connection($connection)->dropIfExists('teacher_loading_schedules');
        }
    }
};
