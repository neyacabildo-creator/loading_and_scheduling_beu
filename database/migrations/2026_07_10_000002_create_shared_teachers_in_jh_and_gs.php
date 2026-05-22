<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates a shared_teachers table in both the JH and GS admin databases.
 *
 * shared_teachers records which teachers are designated as "shared" —
 * meaning they teach at both Junior High and Grade School levels.
 * Each admin database keeps its own copy of the shared teachers relevant to it.
 *
 * This replaces the complex shared_teacher_blocks system with a simple registry.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasTable('shared_teachers')) {
                Schema::connection($connection)->create('shared_teachers', function (Blueprint $table) {
                    $table->id();

                    // Reference to the main DB users table (no FK constraint across DBs)
                    $table->unsignedBigInteger('faculty_id')->nullable();

                    // Name stored directly for display without cross-DB joins
                    $table->string('teacher_name', 150);
                    $table->string('email', 150)->nullable();

                    // Which school level this record belongs to
                    $table->enum('school_level', ['junior_high', 'grade_school']);

                    // Department / subject area
                    $table->string('department', 100)->nullable();

                    // Whether this teacher is currently active as a shared teacher
                    $table->boolean('is_active')->default(true);

                    // Notes from the admin (e.g. availability restrictions)
                    $table->text('notes')->nullable();

                    $table->timestamps();

                    $table->index(['faculty_id', 'school_level']);
                    $table->index('is_active');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            Schema::connection($connection)->dropIfExists('shared_teachers');
        }
    }
};
