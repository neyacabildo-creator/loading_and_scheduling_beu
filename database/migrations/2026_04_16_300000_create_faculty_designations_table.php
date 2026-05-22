<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates `faculty_designations` in both admin school databases.
 *
 * Solves BEU Problems:
 *  - Unequal Role Assignments: coordinator (max 3 classes) vs regular (max 6).
 *  - Imbalanced Faculty Load: system can compare actual load against this limit.
 */
return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $connection) {
            Schema::connection($connection)->create('faculty_designations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('faculty_id')->comment('references users.id in loading_scheduling');
                $table->string('school_year', 20)->default('2025-2026');
                $table->enum('designation_type', ['regular', 'coordinator', 'dept_head', 'shared', 'part_time'])
                      ->default('regular');
                $table->unsignedTinyInteger('max_classes')->default(6)
                      ->comment('coordinator=3, regular=6, dept_head=4');
                $table->decimal('max_load_hours', 5, 2)->default(24.00)
                      ->comment('maximum weekly teaching load hours');
                $table->string('department', 150)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable()
                      ->comment('admin who set this designation');
                $table->timestamps();

                $table->unique(['faculty_id', 'school_year'], 'fd_unique_faculty_year');
                $table->index('designation_type', 'fd_designation_index');
                $table->index('assigned_by', 'fd_assigned_by_index');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $connection) {
            Schema::connection($connection)->dropIfExists('faculty_designations');
        }
    }
};
