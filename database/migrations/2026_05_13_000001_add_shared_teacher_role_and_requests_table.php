<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Uses the default (mysql / loading_scheduling) connection.
     */
    public function up(): void
    {
        // Insert the shared_teacher role if it doesn't already exist
        if (!DB::table('roles')->where('name', 'shared_teacher')->exists()) {
            DB::table('roles')->insert([
                'name'         => 'shared_teacher',
                'display_name' => 'Shared Teacher',
                'description'  => 'Teacher who teaches in both Grade School and Junior High School',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Create the schedule-request table for shared teachers (main DB)
        if (!Schema::hasTable('shared_teacher_schedule_requests')) {
            Schema::create('shared_teacher_schedule_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');          // FK → users.id (main DB)
                $table->string('school_level', 10);              // 'jh' or 'gs'
                $table->string('subject')->nullable();
                $table->string('grade_level')->nullable();
                $table->string('section_name')->nullable();
                $table->string('day_of_week', 20)->nullable();
                $table->time('preferred_start_time')->nullable();
                $table->time('preferred_end_time')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('pending');   // pending | approved | rejected
                $table->text('admin_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'school_level']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_teacher_schedule_requests');
        DB::table('roles')->where('name', 'shared_teacher')->delete();
    }
};
