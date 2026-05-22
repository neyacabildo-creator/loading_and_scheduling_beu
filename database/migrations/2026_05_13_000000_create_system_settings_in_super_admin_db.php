<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the system_settings table inside loading_scheduling_principal.
 * This gives the Principal (Principal) a central place to configure
 * global scheduling rules that apply across both school levels.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ensure the principal database exists before creating tables inside it
        $db = config('database.connections.mysql_principal.database');
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if (!Schema::connection('mysql_principal')->hasTable('system_settings')) {
            Schema::connection('mysql_principal')->create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->text('description')->nullable();
                $table->enum('type', ['text', 'number', 'boolean'])->default('text');
                $table->string('group', 50)->default('general');   // general | scheduling | system
                $table->boolean('is_editable')->default(true);     // false = read-only system constants
                $table->timestamps();
            });
        }

        // Seed default settings
        $now = now();
        DB::connection('mysql_principal')->table('system_settings')->insertOrIgnore([
            [
                'key'         => 'academic_year',
                'value'       => '2025-2026',
                'description' => 'Current academic year displayed across the system.',
                'type'        => 'text',
                'group'       => 'general',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'current_semester',
                'value'       => '2nd Semester',
                'description' => 'Current semester (e.g. 1st Semester, 2nd Semester, Summer).',
                'type'        => 'text',
                'group'       => 'general',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'max_teacher_hours_per_week',
                'value'       => '30',
                'description' => 'Maximum teaching hours per week before a teacher is flagged as overloaded.',
                'type'        => 'number',
                'group'       => 'scheduling',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'min_teacher_hours_per_week',
                'value'       => '6',
                'description' => 'Minimum teaching hours per week before a teacher is flagged as underutilized.',
                'type'        => 'number',
                'group'       => 'scheduling',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'allow_schedule_editing',
                'value'       => 'true',
                'description' => 'Allow admins to edit schedules that have already been approved.',
                'type'        => 'boolean',
                'group'       => 'scheduling',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'schedule_deadline',
                'value'       => '',
                'description' => 'Deadline date for schedule submissions (YYYY-MM-DD). Leave blank for no deadline.',
                'type'        => 'text',
                'group'       => 'scheduling',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'system_name',
                'value'       => 'BEU Faculty Loading System',
                'description' => 'Official name of this system as displayed in headers and reports.',
                'type'        => 'text',
                'group'       => 'general',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'system_version',
                'value'       => '1.0.0',
                'description' => 'Current system version (read-only; updated by developers).',
                'type'        => 'text',
                'group'       => 'system',
                'is_editable' => false,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'institution_name',
                'value'       => 'Saint Paul University Philippines',
                'description' => 'Name of the institution.',
                'type'        => 'text',
                'group'       => 'general',
                'is_editable' => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('mysql_principal')->dropIfExists('system_settings');
    }
};
