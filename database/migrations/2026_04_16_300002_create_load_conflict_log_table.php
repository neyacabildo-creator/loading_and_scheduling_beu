<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates `load_conflict_log` in both admin school databases.
 *
 * Solves BEU Problems:
 *  - Human Errors in Manual Loading: conflicts are written
 *    at the moment a load/schedule is saved, not discovered
 *    2–3 weeks later during manual review.
 *  - Long Time to Fix Schedules: admin can see all open
 *    conflicts in a list and resolve them immediately.
 */
return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $connection) {
            Schema::connection($connection)->create('load_conflict_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id')
                      ->comment('references users.id in loading_scheduling');
                $table->enum('conflict_type', [
                    'overload',
                    'designation_exceeded',
                    'time_overlap',
                    'shared_conflict',
                    'special_block_conflict',
                    'underload',
                ]);
                $table->text('description');
                $table->enum('severity', ['critical', 'warning', 'info'])->default('warning');
                $table->unsignedBigInteger('related_schedule_id')->nullable()
                      ->comment('class_schedules.id that triggered this conflict');
                $table->unsignedBigInteger('related_load_id')->nullable()
                      ->comment('faculty_loads.id that triggered this conflict');
                $table->timestamp('detected_at')->useCurrent();
                $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();

                $table->index('faculty_id', 'lcl_faculty_id_index');
                $table->index('conflict_type', 'lcl_conflict_type_idx');
                $table->index('status', 'lcl_status_index');
                $table->index('detected_at', 'lcl_detected_at_index');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $connection) {
            Schema::connection($connection)->dropIfExists('load_conflict_log');
        }
    }
};
