<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the shared_teacher_blocks table from the main database.
 * This feature has been replaced by the simpler shared_teachers table
 * in the JH and GS admin databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shared_teacher_blocks');
    }

    public function down(): void
    {
        // Re-create the table if rolling back
        if (!Schema::hasTable('shared_teacher_blocks')) {
            \Illuminate\Support\Facades\Schema::create('shared_teacher_blocks', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id');
                $table->string('source_school', 50);
                $table->string('block_type', 50)->default('class');
                $table->string('day_of_week', 20);
                $table->time('start_time');
                $table->time('end_time');
                $table->string('school_year', 20)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
};
